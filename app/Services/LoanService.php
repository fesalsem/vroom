<?php

namespace App\Services;

use App\Models\Registration;

class LoanService
{
    private const MIN_DOWN_PAYMENT_PERCENT = 10;

    /**
     * Calculate the loan amount for a registration.
     *
     * Formula:
     *   effective_price = promotion applies ? discounted_price : car_price
     *   loan_amount = effective_price - down_payment
     *
     * All calculations in integer cents to avoid floating point errors.
     */
    public function calculateLoanAmount(Registration $registration, bool $promotionApplies): int
    {
        $effectivePrice = $promotionApplies
            ? app(PromotionEligibilityService::class)->getDiscountedPriceCents($registration)
            : $registration->car_price_cents;

        return max(0, $effectivePrice - $registration->down_payment_cents);
    }

    /**
     * Determine if the loan is approved.
     *
     * For this system, loan is approved if:
     * - Down payment is at least 10% of the car price
     *
     * In production, this would integrate with a bank API or credit check system.
     */
    public function isLoanApproved(Registration $registration, bool $promotionApplies): bool
    {
        $effectivePrice = $promotionApplies
            ? app(PromotionEligibilityService::class)->getDiscountedPriceCents($registration)
            : $registration->car_price_cents;

        $minDownPayment = (int) round($effectivePrice * self::MIN_DOWN_PAYMENT_PERCENT / 100);

        return $registration->down_payment_cents >= $minDownPayment;
    }
}
