<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationStatusLog extends Model
{
    protected $fillable = [
        'registration_id',
        'from_status',
        'to_status',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => RegistrationStatus::class,
            'to_status' => RegistrationStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get a human-readable description of this status change.
     */
    public function description(): string
    {
        $from = $this->from_status?->label() ?? 'Initial';
        $to = $this->to_status->label();

        return "{$from} → {$to}";
    }
}
