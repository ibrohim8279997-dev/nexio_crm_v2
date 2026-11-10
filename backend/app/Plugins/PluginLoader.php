<?php
namespace App\Plugins;

use App\Plugins\Contracts\OfficialPlugin;
use App\Support\ApiException;

/**
 * Loads OFFICIAL plugin code only.
 *
 * Security decisions encoded here:
 *  - only directories listed in config('nexio.plugins.paths') are ever scanned;
 *  - the resolved path must stay inside its allowlisted root (realpath containment), so a symlink
 *    or a `..` in a manifest cannot make the platform include an arbitrary file;
 *  - the entry file is syntax-checked before inclusion;
 *  - the class must exist and must implement OfficialPlugin;
 *  - uploaded packages are NEVER included — this class has no code path that reads user uploads.
 *
 * `integrity()` returns a deterministic checksum of the plugin directory, used to detect a file
 * changing after review.
 */
class PluginLoader
{
    /** @var array<string,OfficialPlugin> */
    private static array $instances = [];
    /** @var array<string,array>|null */
    private static ?array $manifests = null;
    /** @var array<int,string>|null */
    private static ?array $errors = null;

    /** @return string[] Absolute, real, allowlisted plugin roots. */
    public static function roots(): array
    {
        $roots = [];
        foreach ((array) config('nexio.plugins.paths', []) as $path) {
            $real = realpath((string) $path);
            if ($real !== false && is_dir($real)) {
                $roots[] = $real;
            }
        }
        return $roots;
    }

    /**
     * Every official plugin found on disk, keyed by plugin id.
     *
     * @return array<string,array>
     */
    public static function all(): array
    {
        if (self::$manifests !== null) {
            return self::$manifests;
        }
        self::$manifests = [];
        self::$errors = [];

        foreach (self::roots() as $root) {
            foreach ((array) glob($root . '/*/manifest.json') ?: [] as $manifestPath) {
                $dir = dirname($manifestPath);
                if (!self::isInside($dir, $root)) {
                    self::$errors[] = "Каталог плагина вне разрешённого корня: {$dir}";
                    continue;
                }
                $raw = @file_get_contents($manifestPath);
                $decoded = is_string($raw) ? json_decode($raw, true) : null;
                if (!is_array($decoded)) {
                    self::$errors[] = "Некорректный manifest.json: {$manifestPath}";
                    continue;
                }
                $check = Manifest::validate($decoded);
                if (!$check['ok']) {
                    self::$errors[] = "Манифест {$manifestPath} не прошёл валидацию: " . implode('; ', $check['errors']);
                    continue;
                }
                $id = $check['manifest']['plugin_id'];
                self::$manifests[$id] = [...$check['manifest'], '_dir' => $dir, '_raw' => $decoded];
            }
        }

        ksort(self::$manifests);
        return self::$manifests;
    }

    /** Problems found while scanning the plugin directory (surfaced in the admin UI). */
    public static function loadErrors(): array
    {
        self::all();
        return self::$errors ?? [];
    }

    public static function manifest(string $pluginId): ?array
    {
        return self::all()[$pluginId] ?? null;
    }

    public static function exists(string $pluginId): bool
    {
        return isset(self::all()[$pluginId]);
    }

    /** Reset the in-process cache (used by tests and by the integrity re-check command). */
    public static function reset(): void
    {
        self::$manifests = null;
        self::$errors = null;
        self::$instances = [];
    }

    /** Instantiate (and cache) the plugin entry class. Returns null when it cannot be loaded. */
    public static function load(string $pluginId): ?OfficialPlugin
    {
        if (isset(self::$instances[$pluginId])) {
            return self::$instances[$pluginId];
        }

        $manifest = self::manifest($pluginId);
        if ($manifest === null) {
            return null;
        }

        $dir = (string) $manifest['_dir'];
        $entry = $manifest['entry'] ?? null;
        $class = null;

        if (is_string($entry) && $entry !== '') {
            // runtime.entry may be "Nexio\Telephony\Plugin" or "Nexio\\Telephony\\Plugin@Plugin.php".
            [$class, $file] = array_pad(explode('@', $entry, 2), 2, null);
            $relative = $file ?: 'Plugin.php';
            $candidate = realpath($dir . '/' . ltrim($relative, '/'));
            if ($candidate === false || !self::isInside($candidate, $dir)) {
                throw new ApiException(500, "Точка входа плагина {$pluginId} вне каталога плагина", 'plugin_invalid');
            }
            if (!is_file($candidate) || strtolower(pathinfo($candidate, PATHINFO_EXTENSION)) !== 'php') {
                throw new ApiException(500, "Точка входа плагина {$pluginId} не найдена", 'plugin_invalid');
            }
            self::assertSyntax($candidate);
            require_once $candidate;
        }

        if ($class === null || !class_exists($class)) {
            // Fall back to a conventional class name so a manifest can omit runtime.entry.
            $guess = 'Nexio\\Plugins\\' . str_replace(' ', '', ucwords(str_replace('.', ' ', $pluginId))) . '\\Plugin';
            if (class_exists($guess)) {
                $class = $guess;
            }
        }

        if ($class === null || !class_exists($class)) {
            throw new ApiException(500, "Класс плагина {$pluginId} не найден", 'plugin_invalid');
        }
        if (!in_array(OfficialPlugin::class, class_implements($class) ?: [], true)) {
            throw new ApiException(500, "Класс плагина {$pluginId} не реализует OfficialPlugin", 'plugin_invalid');
        }

        /** @var OfficialPlugin $instance */
        $instance = new $class();
        if ($instance->id() !== $pluginId) {
            throw new ApiException(500, "Плагин {$pluginId} сообщает несовпадающий id: {$instance->id()}", 'plugin_invalid');
        }

        self::$instances[$pluginId] = $instance;
        return $instance;
    }

    /**
     * Deterministic checksum of a plugin directory: sorted relative paths plus content hashes.
     * Stored as the version checksum for official plugins so a post-review edit is detectable.
     */
    public static function integrity(string $pluginId): ?array
    {
        $manifest = self::manifest($pluginId);
        if ($manifest === null) {
            return null;
        }
        $dir = (string) $manifest['_dir'];
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getRealPath();
            if ($path === false || !self::isInside($path, $dir)) {
                continue;
            }
            $relative = ltrim(str_replace($dir, '', $path), '/');
            $files[$relative] = hash_file('sha256', $path) ?: '';
        }
        ksort($files);

        $manifestHash = hash('sha256', json_encode($files, JSON_UNESCAPED_SLASHES) ?: '');

        return [
            'plugin_id' => $pluginId,
            'dir' => $dir,
            'files' => $files,
            'checksum' => 'sha256:' . $manifestHash,
            'file_count' => count($files),
        ];
    }

    /** Throws when a plugin's files no longer match the checksum recorded at publication. */
    public static function assertIntegrity(string $pluginId, ?string $expected): void
    {
        $integrity = self::integrity($pluginId);
        if ($integrity === null || $expected === null || $expected === '') {
            return;
        }
        if (!hash_equals($expected, $integrity['checksum'])) {
            throw new ApiException(
                500,
                "Файлы плагина {$pluginId} изменились после публикации. Загрузка остановлена.",
                'plugin_integrity_failed',
                ['expected' => $expected, 'actual' => $integrity['checksum']]
            );
        }
    }

    private static function assertSyntax(string $file): void
    {
        // A syntax error must surface as a clean error, not as a fatal that takes the worker down.
        $check = self::phpLint($file);
        if ($check !== true) {
            throw new ApiException(500, 'Файл плагина содержит синтаксическую ошибку: ' . basename($file), 'plugin_invalid');
        }
    }

    private static function phpLint(string $file): bool
    {
        $php = self::cliBinary();
        if ($php === null || !function_exists('exec')) {
            return true; // No usable CLI to lint with; the require will surface a real syntax error.
        }
        $output = [];
        $code = 0;
        @exec(escapeshellarg($php) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);
        // Only a genuine parse failure rejects the plugin. A tool that cannot lint (wrong binary,
        // sandboxed exec) must not make every plugin look broken.
        $text = implode("\n", $output);
        return !($code !== 0 && (str_contains($text, 'Parse error') || str_contains($text, 'Errors parsing') || str_contains($text, 'syntax error')));
    }

    /** Under PHP-FPM, PHP_BINARY is php-fpm, which has no -l flag; use the CLI binary instead. */
    private static function cliBinary(): ?string
    {
        if (PHP_SAPI === 'cli' && defined('PHP_BINARY') && PHP_BINARY !== '') {
            return PHP_BINARY;
        }
        foreach ([PHP_BINDIR . '/php', PHP_BINDIR . '/php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION] as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    private static function isInside(string $path, string $root): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $root = rtrim(str_replace('\\', '/', $root), '/');
        return $path === $root || str_starts_with($path, $root . '/');
    }
}
