<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Sends an encrypted copy of the whole database to the platform operators in Telegram, so the data survives the loss
 * of the server. The dump is gzip -> AES-256-CBC (openssl, PBKDF2 200k) -> parts of at most 45 MB. A copy is never sent
 * unencrypted: without BACKUP_ENCRYPTION_KEY nothing is sent. Decrypting needs only the key and the openssl tool.
 */
final class TelegramBackup
{
    public const ITER = 200000;

    public static function statePath(): string
    {
        return storage_path('app/backup-telegram.json');
    }

    public static function lastSent(): ?array
    {
        $d = is_file(self::statePath()) ? json_decode((string) file_get_contents(self::statePath()), true) : null;
        return is_array($d) && isset($d['at']) ? $d : null;
    }

    public static function due(): bool
    {
        $last = self::lastSent();
        return !$last || time() - (int) $last['at'] >= (int) config('backup.telegram.every_minutes') * 60;
    }

    /** Operators' linked chats (when the database is reachable) plus the fixed chat ids from the environment. @return string[] */
    public static function recipients(): array
    {
        $ids = (array) config('backup.telegram.chat_ids');
        try {
            $rows = DB::table('user_telegram_links as l')->join('users as u', 'u.id', '=', 'l.user_id')
                ->where('u.is_platform_admin', 1)->whereNotNull('l.chat_id')->where('l.chat_id', '<>', '')->pluck('l.chat_id')->all();
            $ids = array_merge($ids, $rows);
        } catch (\Throwable) {
            // database down: the environment chats still receive alerts
        }
        return array_values(array_unique(array_map('strval', $ids)));
    }

    /** openssl enc -d/-e with the passphrase passed through the environment (never on the command line). */
    public static function openssl(bool $decrypt, string $in, string $out, string $key): void
    {
        $p = new Process(['openssl', 'enc', $decrypt ? '-d' : '-e', '-aes-256-cbc', '-pbkdf2', '-iter', (string) self::ITER, '-md', 'sha256', '-salt', '-in', $in, '-out', $out, '-pass', 'env:NEXIO_BACKUP_KEY'], null, ['NEXIO_BACKUP_KEY' => $key], null, 3600);
        $p->run();
        if (!$p->isSuccessful()) {
            @unlink($out);
            throw new \RuntimeException($decrypt ? 'Parolni (BACKUP_ENCRYPTION_KEY) tekshiring: shifrni ochib bo‘lmadi' : 'openssl shifrlashda xato: ' . substr(trim($p->getErrorOutput()), 0, 200));
        }
    }

    /** @return array{parts: int, bytes: int, chats: int, file: string} */
    public function run(): array
    {
        $key = (string) config('backup.telegram.key');
        if (strlen($key) < 16) throw new \RuntimeException('BACKUP_ENCRYPTION_KEY kamida 16 belgi bo‘lishi kerak: shifrlanmagan nusxa yuborilmaydi');
        $chats = self::recipients();
        if (!$chats) throw new \RuntimeException('Qabul qiluvchi yo‘q: superadmin Telegramni ulashi yoki BACKUP_TELEGRAM_CHAT_IDS berilishi kerak');

        $meta = BackupService::run(2, 'tgbackup');
        $plain = BackupService::dir() . '/' . $meta['file'];
        $enc = $plain . '.enc';
        try {
            self::openssl(false, $plain, $enc, $key);
        } finally {
            @unlink($plain);   // the unencrypted dump never stays next to the encrypted one
        }
        try {
            $size = (int) filesize($enc);
            if ($size > (int) config('backup.telegram.max_mb') * 1048576) throw new \RuntimeException('Nusxa Telegram uchun juda katta (' . round($size / 1048576) . ' MB): tashqi saqlashdan foydalaning');
            $sha = hash_file('sha256', $enc);
            $parts = $this->split($enc);
            $media = new TelegramMedia();
            $stamp = gmdate('Y-m-d H:i') . ' UTC';
            foreach ($chats as $chat) {
                foreach ($parts as $i => $part) {
                    $caption = "🗄 Nexio backup {$stamp}\n" . (count($parts) > 1 ? 'Qism ' . ($i + 1) . '/' . count($parts) . "\n" : '') . 'sha256: ' . substr($sha, 0, 16) . '… · ' . round($size / 1048576, 1) . ' MB';
                    if (!$media->sendDocument($chat, (string) file_get_contents($part), basename($part), $caption, 600)) {
                        throw new \RuntimeException('Telegramga yuborib bo‘lmadi (chat ' . substr($chat, 0, 3) . '…)');
                    }
                }
            }
            file_put_contents(self::statePath(), json_encode(['at' => time(), 'file' => basename($enc), 'bytes' => $size, 'parts' => count($parts), 'sha256' => $sha]));
            return ['parts' => count($parts), 'bytes' => $size, 'chats' => count($chats), 'file' => basename($enc)];
        } finally {
            foreach (glob($enc . '*') ?: [] as $f) @unlink($f);
        }
    }

    /** Tells the recipients a scheduled backup failed (it must never fail silently). */
    public static function alertFailure(string $reason): void
    {
        $media = new NotificationDispatcher();
        foreach (self::recipients() as $chat) $media->telegram($chat, "⚠️ Nexio backup yuborilmadi / не отправлен: {$reason}");
    }

    /** @return string[] file paths; a single file when it fits one part */
    private function split(string $file): array
    {
        $max = max(1000, (int) config('backup.telegram.part_bytes'));
        if (filesize($file) <= $max) return [$file];
        $parts = [];
        $in = fopen($file, 'rb');
        for ($n = 1; !feof($in); $n++) {
            $chunk = stream_get_contents($in, $max);
            if ($chunk === '' || $chunk === false) break;
            $path = sprintf('%s.part%03d', $file, $n);
            file_put_contents($path, $chunk);
            $parts[] = $path;
        }
        fclose($in);
        return $parts;
    }
}
