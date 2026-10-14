<?php
namespace App\Plugins;

use App\Support\ApiException;
use Illuminate\Support\Facades\Storage;

/**
 * Safe handling of uploaded plugin packages.
 *
 * This class NEVER extracts an archive to a reachable location and NEVER includes uploaded PHP.
 * Uploaded code is inert data: the platform stores the archive, records its checksum and reads
 * its manifest. Only official plugins — which ship inside the repository and are reviewed like
 * any other platform code — are ever loaded (see PluginLoader).
 *
 * Guards implemented: traversal, absolute paths, symlinks, null bytes, nested archives,
 * entry count, uncompressed size, compression ratio, extension allowlist, checksum verification.
 */
class PackageStore
{
    private const ARCHIVE_EXT = ['zip', 'tar', 'gz', 'tgz', 'bz2', 'xz', '7z', 'rar'];

    private static function cfg(string $key, mixed $default = null): mixed
    {
        return config("nexio.plugins.package.{$key}", $default);
    }

    /**
     * Inspect an archive without extracting it.
     *
     * @return array{ok:bool,errors:string[],entries:int,uncompressed_bytes:int,checksum:string,contains_code:bool,manifest:?array}
     */
    public static function inspect(string $path): array
    {
        $errors = [];
        if (!is_file($path)) {
            return self::fail(['Пакет не найден']);
        }

        $size = (int) filesize($path);
        $maxBytes = (int) self::cfg('max_bytes', 20971520);
        if ($size <= 0) {
            return self::fail(['Пакет пуст']);
        }
        if ($size > $maxBytes) {
            return self::fail(["Пакет превышает допустимый размер ({$this->human($maxBytes)})"]);
        }

        $checksum = hash_file('sha256', $path) ?: '';

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return self::fail(['Пакет повреждён или не является корректным ZIP-архивом'], $checksum);
        }

        $entries = $zip->numFiles;
        $uncompressed = 0;
        $containsCode = false;
        $allowed = (array) self::cfg('allowed_extensions', []);
        $manifest = null;

        if ($entries > (int) self::cfg('max_entries', 500)) {
            $errors[] = 'Архив содержит слишком много файлов';
        }

        for ($i = 0; $i < $entries; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                $errors[] = 'Не удалось прочитать запись архива';
                break;
            }
            $name = (string) $stat['name'];
            $norm = str_replace('\\', '/', $name);

            // ── path safety ────────────────────────────────────────────────────────
            if (str_contains($name, "\0")) {
                $errors[] = 'Архив содержит недопустимые имена файлов';
                continue;
            }
            if (str_starts_with($norm, '/') || preg_match('#^[A-Za-z]:#', $norm)) {
                $errors[] = "Абсолютный путь в архиве: {$norm}";
                continue;
            }
            $segments = array_filter(explode('/', $norm), fn ($s) => $s !== '' && $s !== '.');
            foreach ($segments as $seg) {
                if ($seg === '..') {
                    $errors[] = "Обход каталога в архиве: {$norm}";
                    break;
                }
            }

            // ── symlink / hardlink detection (unix external attributes 0120000) ────
            $mode = ($stat['external_attributes'] ?? 0) >> 16;
            if ($mode !== 0 && (($mode & 0xF000) === 0xA000)) {
                $errors[] = "Символическая ссылка в архиве: {$norm}";
                continue;
            }

            if (str_ends_with($norm, '/')) {
                continue;
            }

            $ext = strtolower(pathinfo($norm, PATHINFO_EXTENSION));
            if ($ext !== '' && !in_array($ext, $allowed, true)) {
                $errors[] = "Недопустимый тип файла в архиве: .{$ext}";
            }
            if (in_array($ext, self::ARCHIVE_EXT, true)) {
                $errors[] = "Вложенный архив запрещён: {$norm}";
            }
            if (in_array($ext, ['php', 'phtml', 'phar', 'cgi', 'sh', 'bat', 'exe', 'so', 'dll'], true)) {
                $containsCode = true;
                if (in_array($ext, ['phar', 'cgi', 'sh', 'bat', 'exe', 'so', 'dll'], true)) {
                    $errors[] = "Исполняемый файл запрещён в пакете: {$norm}";
                }
            }

            $uncompressed += (int) ($stat['size'] ?? 0);

            // ── manifest (read from memory, never from disk) ────────────────────────
            if ($manifest === null && in_array($norm, ['manifest.json', './manifest.json'], true)) {
                $raw = $zip->getFromIndex($i, 262144);
                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $manifest = $decoded;
                    } else {
                        $errors[] = 'manifest.json не является корректным JSON';
                    }
                }
            }
        }
        $zip->close();

        if (($intEntries = $entries) > 0 && $size > 0) {
            $ratio = $uncompressed / $size;
            if ($ratio > (float) self::cfg('max_compression_ratio', 120)) {
                $errors[] = 'Подозрительно высокая степень сжатия архива';
            }
        }
        if ($uncompressed > (int) self::cfg('max_uncompressed_bytes', 83886080)) {
            $errors[] = 'Распакованный размер архива превышает допустимый';
        }
        if ($manifest === null) {
            $errors[] = 'В архиве отсутствует manifest.json';
        }

        return [
            'ok' => $errors === [],
            'errors' => array_values(array_unique($errors)),
            'entries' => $intEntries,
            'uncompressed_bytes' => $uncompressed,
            'checksum' => $checksum,
            'contains_code' => $containsCode,
            'manifest' => $manifest,
        ];
    }

    /**
     * Persist an inspected package. The archive is copied verbatim; nothing is extracted.
     *
     * @return array{path:string,checksum:string,size:int,name:string}
     */
    public static function store(string $sourcePath, string $pluginId, string $version, string $originalName): array
    {
        self::assertSafeId($pluginId);
        if (!Semver::isValid($version)) {
            throw new ApiException(400, 'Некорректная версия пакета', 'bad_request');
        }

        $relative = 'plugins/' . $pluginId . '/' . Semver::normalise($version) . '/package.zip';
        $disk = Storage::disk('local');
        $stream = fopen($sourcePath, 'rb');
        if ($stream === false) {
            throw new ApiException(500, 'Не удалось прочитать загруженный пакет', 'internal');
        }
        $disk->put($relative, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        $absolute = $disk->path($relative);
        @chmod(dirname($absolute), 0700);
        @chmod($absolute, 0600);

        return [
            'path' => $relative,
            'checksum' => hash_file('sha256', $absolute) ?: '',
            'size' => (int) filesize($absolute),
            'name' => mb_substr($originalName, 0, 190),
        ];
    }

    public static function verifyChecksum(string $relativePath, ?string $expected): bool
    {
        if (!$expected) {
            return false;
        }
        $absolute = Storage::disk('local')->path($relativePath);
        if (!is_file($absolute)) {
            return false;
        }
        return hash_equals($expected, (string) hash_file('sha256', $absolute));
    }

    public static function delete(string $relativePath): void
    {
        if ($relativePath !== '' && str_starts_with($relativePath, 'plugins/')) {
            Storage::disk('local')->delete($relativePath);
        }
    }

    /** Plugin ids are used in filesystem paths, so they are validated before any write. */
    public static function assertSafeId(string $pluginId): void
    {
        if (!preg_match('/^[a-z0-9][a-z0-9\-]*(\.[a-z0-9][a-z0-9\-]*)+$/', $pluginId) || strlen($pluginId) > 64) {
            throw new ApiException(400, 'Недопустимый идентификатор плагина', 'bad_request');
        }
    }

    /** @return array{ok:bool,errors:string[],entries:int,uncompressed_bytes:int,checksum:string,contains_code:bool,manifest:?array} */
    private static function fail(array $errors, string $checksum = ''): array
    {
        return [
            'ok' => false,
            'errors' => $errors,
            'entries' => 0,
            'uncompressed_bytes' => 0,
            'checksum' => $checksum,
            'contains_code' => false,
            'manifest' => null,
        ];
    }

    private function human(int $bytes): string
    {
        return round($bytes / 1048576, 1) . ' МБ';
    }
}
