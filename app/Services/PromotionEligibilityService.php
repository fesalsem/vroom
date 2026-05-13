<?php

namespace App\Services;

use App\Models\Registration;

class PromotionEligibilityService
{
    private const PROMOTION_CAR_MODEL = 'CapBay Vroom';
    private const PROMOTION_DISCOUNT_PERCENT = 15;
    private const PROMOTION_MAX_SLOTS = 10;
    private const MIN_DOWN_PAYMENT_PERCENT = 10;

    /**
     * Check if a registration is eligible for the promotion.
     *
     * Rules:
     * 1. Car model must be "CapBay Vroom"
     * 2. Customer must be among the first 10 who registered for this model
     * 3. Customer must have paid >= 10% down payment
     * 4. Customer must have loan approved
     *
     * KEY DECISION — Cancellation handling:
     * If a customer who was in the first 10 cancels, their slot is NOT freed.
     * Customer C (11th registrant) does NOT become eligible.
     *
     * Reasoning: The promotion says "first 10 customers" — this refers to
     * chronological registration order. A slot is consumed when a customer
     * registers, not when they purchase. This is simpler to implement,
     * easier to explain to customers, and avoids complex recalculation
     * every time a registration is cancelled.
     */
    public function isEligible(Registration $registration): bool
    {
        // Rule 1: Must be the correct car model
        if ($registration->car_model !== self::PROMOTION_CAR_MODEL) {
            return false;
        }

        // Rule 2: Must be among the first 10 registrations for this model
        $position = $this->getQueuePosition($registration);
        if ($position > self::PROMOTION_MAX_SLOTS) {
            return false;
        }

        // Rule 3: Must have paid at least 10% down payment
        $minDownPayment = (int) round($registration->car_price_cents * self::MIN_DOWN_PAYMENT_PERCENT / 100);
        if ($registration->down_payment_cents < $minDownPayment) {
            return false;
        }

        // Rule 4: Loan must be approved
        if ($registration->loan_approved !== true) {
            return false;
        }

        return true;
    }

    /**
     * Get the registration's position in the queue for this car model.
     * Position is determined by created_at order among ALL registrations
     * for this model (cancelled ones still count toward the slot).
     */
    public function getQueuePosition(Registration $registration): int
    {
        return Registration::query()
            ->where('car_model', self::PROMOTION_CAR_MODEL)
            ->where(function ($query) use ($registration) {
                $query->where('created_at', '<', $registration->created_at)
                    ->orWhere(function ($query) use ($registration) {
                        $query->where('created_at', '=', $registration->created_at)
                            ->where('id', '<=', $registration->id);
                    });
            })
            ->count();
    }

    /**
     * Count how many promotion slots are still available.
     */
    public function getRemainingSlots(): int
    {
        $registeredCount = Registration::query()
            ->where('car_model', self::PROMOTION_CAR_MODEL)
            ->count();

        return max(0, self::PROMOTION_MAX_SLOTS - $registeredCount);
    }

    /**
     * Get the discounted price in cents (car price - 15%).
     */
    public function getDiscountedPriceCents(Registration $registration): int
    {
        $discount = (int) round($registration->car_price_cents * self::PROMOTION_DISCOUNT_PERCENT / 100);

        return $registration->car_price_cents - $discount;
    }
}
