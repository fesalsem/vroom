<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RegistrationService
{
    public function __construct(
        private readonly PromotionEligibilityService $promotionService,
        private readonly LoanCalculationService $loanService,
        private readonly RegistrationStateService $stateMachine,
    ) {}

    /**
     * Create a new registration from customer form data.
     */
    public function createRegistration(array $data): Registration
    {
        $registration = Registration::create([
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'car_model' => $data['car_model'],
            'car_price_cents' => $data['car_price_cents'],
            'status' => RegistrationStatus::Registered,
        ]);

        // Log the initial status (no "from" status since this is the initial creation)
        $registration->statusLogs()->create([
            'from_status' => null,
            'to_status' => RegistrationStatus::Registered->value,
        ]);

        return $registration->fresh();
    }

    /**
     * List registrations with pagination and optional filters.
     */
    public function listRegistrations(array $filters = []): LengthAwarePaginator
    {
        $query = Registration::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find a registration by ID.
     */
    public function findRegistration(int $id): Registration
    {
        return Registration::findOrFail($id);
    }

    /**
     * Transition a registration to a new status.
     */
    public function transitionStatus(Registration $registration, RegistrationStatus $newStatus, ?User $user = null): Registration
    {
        return $this->stateMachine->transition($registration, $newStatus, $user);
    }

    /**
     * Update the down payment and re-evaluate promotion eligibility and loan.
     */
    public function updateDownPayment(Registration $registration, int $downPaymentCents): Registration
    {
        $registration->update([
            'down_payment_cents' => $downPaymentCents,
        ]);

        $registration = $registration->fresh();

        // Re-evaluate promotion and loan
        $this->evaluatePromotionAndLoan($registration);

        return $registration->fresh();
    }

    /**
     * Check promotion eligibility for a registration.
     *
     * @return array{promotion_eligible: bool, queue_position: int, remaining_slots: int, discounted_price_cents: int|null}
     */
    public function checkPromotionEligibility(Registration $registration): array
    {
        $eligible = $this->promotionService->isEligible($registration);

        $registration->update([
            'promotion_eligible' => $eligible,
        ]);

        return [
            'promotion_eligible' => $eligible,
            'queue_position' => $this->promotionService->getQueuePosition($registration),
            'remaining_slots' => $this->promotionService->getRemainingSlots(),
            'discounted_price_cents' => $eligible
                ? $this->promotionService->getDiscountedPriceCents($registration)
                : null,
        ];
    }

    /**
     * Calculate loan amount for a registration.
     *
     * @return array{loan_amount_cents: int, loan_approved: bool, effective_price_cents: int}
     */
    public function calculateLoan(Registration $registration): array
    {
        $promotionApplies = $this->promotionService->isEligible($registration);

        $loanAmount = $this->loanService->calculateLoanAmount($registration, $promotionApplies);
        $loanApproved = $this->loanService->isLoanApproved($registration, $promotionApplies);

        $effectivePrice = $promotionApplies
            ? $this->promotionService->getDiscountedPriceCents($registration)
            : $registration->car_price_cents;

        $registration->update([
            'loan_amount_cents' => $loanAmount,
            'loan_approved' => $loanApproved,
        ]);

        return [
            'loan_amount_cents' => $loanAmount,
            'loan_approved' => $loanApproved,
            'effective_price_cents' => $effectivePrice,
        ];
    }

    /**
     * Re-evaluate both promotion eligibility and loan after a down payment update.
     */
    private function evaluatePromotionAndLoan(Registration $registration): void
    {
        $this->checkPromotionEligibility($registration);
        $this->calculateLoan($registration);
    }
}
