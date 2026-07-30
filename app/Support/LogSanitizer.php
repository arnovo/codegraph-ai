<?php

declare(strict_types=1);

namespace App\Support;

final class LogSanitizer
{
    private const REDACT_PATTERNS = [
        '/sk-[a-zA-Z0-9]{10,}/',
        '/Bearer\s+[a-zA-Z0-9._\-]+/i',
        '/api[_-]?key["\']?\s*[:=]\s*["\']?[a-zA-Z0-9._\-]+/i',
    ];

    public static function redact(string $value): string
    {
        foreach (self::REDACT_PATTERNS as $pattern) {
            $value = (string) preg_replace($pattern, '[REDACTED]', $value);
        }

        return $value;
    }

    public static function truncate(?string $value, int $max = 200): ?string
    {
        if ($value === null) {
            return null;
        }

        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max).'…';
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return list<array<string, mixed>>
     */
    public static function sanitizeMessages(array $messages): array
    {
        return array_map(function (array $message): array {
            if (isset($message['content']) && is_string($message['content'])) {
                $message['content'] = self::truncate(self::redact($message['content']), 400);
            }

            return $message;
        }, $messages);
    }
}
