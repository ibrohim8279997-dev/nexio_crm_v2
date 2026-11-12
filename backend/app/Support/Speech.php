<?php

namespace App\Support;

/** Helpers for Groq's Orpheus TTS: it takes at most 200 characters per request and returns WAV. */
final class Speech
{
    public const MAX_CHARS = 200;
    public const MAX_CHUNKS = 3;

    /** Plain text split on sentence boundaries into pieces of at most $max characters (at most $limit pieces). @return string[] */
    public static function chunks(string $text, int $max = self::MAX_CHARS, int $limit = self::MAX_CHUNKS): array
    {
        $text = preg_replace('/[*_`#>~|]+/u', '', $text) ?? $text;               // markdown noise is not spoken
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        $out = [];
        $current = '';
        foreach (preg_split('/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $sentence) {
            while (mb_strlen($sentence) > $max) {                                 // one very long sentence: cut at a space
                $cut = mb_strrpos(mb_substr($sentence, 0, $max), ' ') ?: $max;
                if ($current !== '') { $out[] = $current; $current = ''; }
                $out[] = trim(mb_substr($sentence, 0, $cut));
                $sentence = trim(mb_substr($sentence, $cut));
            }
            if ($current !== '' && mb_strlen($current) + 1 + mb_strlen($sentence) > $max) { $out[] = $current; $current = ''; }
            $current = $current === '' ? $sentence : "{$current} {$sentence}";
        }
        if ($current !== '') $out[] = $current;
        return array_slice(array_values(array_filter($out, fn ($c) => $c !== '')), 0, $limit);
    }

    /** Joins WAV files that share one format into a single WAV. Returns the first file if any cannot be parsed. */
    public static function mergeWav(array $wavs): string
    {
        if (count($wavs) < 2) return (string) ($wavs[0] ?? '');
        $fmt = null;
        $pcm = '';
        foreach ($wavs as $wav) {
            $parsed = self::parse((string) $wav);
            if (!$parsed) return (string) $wavs[0];
            $fmt ??= $parsed[0];
            if ($parsed[0] !== $fmt) return (string) $wavs[0];
            $pcm .= $parsed[1];
        }
        return 'RIFF' . pack('V', 36 + strlen($pcm)) . 'WAVEfmt ' . pack('V', strlen($fmt)) . $fmt . 'data' . pack('V', strlen($pcm)) . $pcm;
    }

    /** @return array{0: string, 1: string}|null [fmt chunk body, data chunk body] */
    private static function parse(string $wav): ?array
    {
        if (strlen($wav) < 44 || substr($wav, 0, 4) !== 'RIFF' || substr($wav, 8, 4) !== 'WAVE') return null;
        $fmt = $data = null;
        for ($pos = 12; $pos + 8 <= strlen($wav);) {
            $id = substr($wav, $pos, 4);
            $size = unpack('V', substr($wav, $pos + 4, 4))[1];
            // Streamed WAVs may declare 0xFFFFFFFF as the data size: take everything that is left.
            $body = substr($wav, $pos + 8, $size === 0xFFFFFFFF ? null : $size);
            if ($id === 'fmt ') $fmt = $body;
            if ($id === 'data') { $data = $body; break; }
            $pos += 8 + $size + ($size & 1);
        }
        return $fmt !== null && $data !== null ? [$fmt, $data] : null;
    }
}
