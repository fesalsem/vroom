<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registration extends Model
{
    /** @use HasFactory<RegistrationFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'customer_email',
        'customer_phone',
        'car_model',
        'car_price_cents',
        'down_payment_cents',
        'status',
        'promotion_eligible',
        'loan_amount_cents',
        'loan_approved',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'promotion_eligible' => 'boolean',
            'loan_approved' => 'boolean',
            'car_price_cents' => 'integer',
            'down_payment_cents' => 'integer',
            'loan_amount_cents' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function statusLogs(): HasMany
    {
        return $this->hasMany(RegistrationStatusLog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to filter by registration status.
     */
    public function scopeWhereStatus(Builder $query, RegistrationStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope to filter by car model.
     */
    public function scopeWhereCarModel(Builder $query, string $carModel): Builder
    {
        return $query->where('car_model', $carModel);
    }

    /**
     * Scope to search by customer name or email.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('customer_name', 'like', "%{$term}%")
              ->orWhere('customer_email', 'like', "%{$term}%")
              ->orWhere('customer_phone', 'like', "%{$term}%");
        });
    }

    /**
     * Scope to filter promotion-eligible registrations.
     */
    public function scopePromotionEligible(Builder $query): Builder
    {
        return $query->where('promotion_eligible', true);
    }

    /**
     * Scope to filter loan-approved registrations.
     */
    public function scopeLoanApproved(Builder $query): Builder
    {
        return $query->where('loan_approved', true);
    }

    /**
     * Scope to order by most recent first.
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get the effective car price, accounting for promotion discount.
     */
    public function effectivePriceCents(): int
    {
        if ($this->promotion_eligible) {
            return (int) round($this->car_price_cents * 0.85);
        }

        return $this->car_price_cents;
    }

    /**
     * Check if the registration is in a terminal state (cannot transition further).
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            RegistrationStatus::Purchased,
            RegistrationStatus::Cancelled,
        ], true);
    }

    /**
     * Check if the registration is for the promotion car model.
     */
    public function isCapBayVroom(): bool
    {
        return $this->car_model === 'CapBay Vroom';
    }
}
