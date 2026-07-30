<?php

declare(strict_types=1);

namespace App\Domains\Chat\Enums;

enum MessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
    case Tool = 'tool';
}
