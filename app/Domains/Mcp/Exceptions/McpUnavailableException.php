<?php

declare(strict_types=1);

namespace App\Domains\Mcp\Exceptions;

use RuntimeException;

final class McpUnavailableException extends RuntimeException
{
    public static function withMessage(string $message): self
    {
        return new self($message);
    }
}
