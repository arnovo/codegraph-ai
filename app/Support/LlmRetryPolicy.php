<?php

declare(strict_types=1);

namespace App\Support;

use Throwable;

final class LlmRetryPolicy
{
    public static function isRetryable(Throwable $throwable): bool
    {
        $message = strtolower($throwable->getMessage());

        $needles = [
            'quota',
            'exceeded your current quota',
            'free_tier',
            'rate limit',
            'too many requests',
            '429',
            '503',
            '502',
            '504',
            'overloaded',
            'unavailable',
            'resource exhausted',
            'model not found',
            '404',
            'does not exist',
            'no available',
            'empty response',
        ];

        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
