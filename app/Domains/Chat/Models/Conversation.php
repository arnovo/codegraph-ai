<?php

declare(strict_types=1);

namespace App\Domains\Chat\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $title
 * @property string|null $primary_project_name
 * @property string|null $summary
 * @property int|null $summary_message_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class Conversation extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'primary_project_name',
        'summary',
        'summary_message_count',
    ];

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
