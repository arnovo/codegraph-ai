<?php

declare(strict_types=1);

namespace App\Support;

final class LlmErrorFormatter
{
    /**
     * @param  array<string, mixed>|null  $context
     */
    public static function forUser(string $message, ?array $context = null): string
    {
        $normalized = self::stripPrefix($message);

        if (self::isQuotaError($normalized)) {
            return self::formatLimitedMessage(
                headline: self::extractRetrySeconds($normalized) !== null
                    ? 'Cuota de Gemini agotada (plan gratuito). Espera unos '.self::extractRetrySeconds($normalized).'s e inténtalo de nuevo, o usa otra API key / plan de pago.'
                    : 'Cuota de Gemini agotada (plan gratuito). Espera un minuto e inténtalo de nuevo, o usa otra API key / plan de pago.',
                raw: $normalized,
                context: $context,
            );
        }

        if (self::isRateLimit($normalized)) {
            return self::formatLimitedMessage(
                headline: 'Demasiadas peticiones al LLM. Espera unos segundos e inténtalo de nuevo.',
                raw: $normalized,
                context: $context,
            );
        }

        if (str_contains(strtolower($normalized), 'invalid api key') || str_contains(strtolower($normalized), 'api key not valid')) {
            return self::formatLimitedMessage(
                headline: 'API key de LLM inválida. Revisa LLM_API_KEY en .env.',
                raw: $normalized,
                context: $context,
            );
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    private static function formatLimitedMessage(string $headline, string $raw, ?array $context): string
    {
        $lines = [$headline, '', 'Trazado LLM:'];

        if ($context !== null) {
            $baseUrl = rtrim((string) ($context['base_url'] ?? ''), '/');
            $lines[] = '• Driver: '.((string) ($context['driver'] ?? '—'));
            $lines[] = '• Modelo (.env): '.((string) ($context['model'] ?? '—'));
            $lines[] = '• URL: '.$baseUrl.'/chat/completions';
            $lines[] = '• API key: '.self::truncateSecret((string) ($context['api_key'] ?? ''));
            $lines[] = '• Tool iterations máx: '.((string) ($context['max_tool_iterations'] ?? '—'));
        }

        $modelFromError = self::extractModelFromError($raw);
        if ($modelFromError !== null) {
            $lines[] = '• Modelo (respuesta API): '.$modelFromError;
        }

        $metric = self::extractQuotaMetric($raw);
        if ($metric !== null) {
            $lines[] = '• Métrica cuota: '.$metric;
        }

        $lines[] = '';
        $lines[] = 'Detalle: '.self::truncate(self::singleLine($raw), 280);

        return implode("\n", $lines);
    }

    public static function truncateSecret(string $secret): string
    {
        $secret = trim($secret);

        if ($secret === '') {
            return '(vacía)';
        }

        if (mb_strlen($secret) <= 12) {
            return str_repeat('•', mb_strlen($secret));
        }

        return mb_substr($secret, 0, 8).'…'.mb_substr($secret, -4);
    }

    private static function stripPrefix(string $message): string
    {
        return preg_replace('/^LLM request failed:\s*/i', '', trim($message)) ?? trim($message);
    }

    private static function isQuotaError(string $message): bool
    {
        $haystack = strtolower($message);

        return str_contains($haystack, 'quota')
            || str_contains($haystack, 'exceeded your current quota')
            || str_contains($haystack, 'free_tier');
    }

    private static function isRateLimit(string $message): bool
    {
        $haystack = strtolower($message);

        return str_contains($haystack, 'rate limit')
            || str_contains($haystack, 'too many requests')
            || str_contains($haystack, '429');
    }

    private static function extractRetrySeconds(string $message): ?int
    {
        if (preg_match('/retry in (\d+(?:\.\d+)?)\s*s/i', $message, $matches) === 1) {
            return max(1, (int) ceil((float) $matches[1]));
        }

        return null;
    }

    private static function extractModelFromError(string $message): ?string
    {
        if (preg_match('/model:\s*([^\s,\]]+)/i', $message, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    private static function extractQuotaMetric(string $message): ?string
    {
        if (preg_match('/metric:\s*([^,\n]+)/i', $message, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    private static function singleLine(string $message): string
    {
        return preg_replace('/\s+/', ' ', trim($message)) ?? trim($message);
    }

    private static function truncate(string $value, int $max): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max).'…';
    }
}
