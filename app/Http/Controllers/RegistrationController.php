<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Exceptions\InvalidTransitionException;
use App\Http\Requests\ListRegistrationRequest;
use App\Http\Requests\UpdateRegistrationRequest;
use App\Http\Requests\UpdateRegistrationStateRequest;
use App\Models\Registration;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrationService,
    ) {}

    /**
     * List all registrations with pagination and optional filters.
     *
     * GET /agent/registrations
     *
     * Supports:
     * - ?status=registered — filter by status
     * - ?search=john — search by name, email, or phone
     * - ?per_page=25 — pagination size (10–100)
     */
    public function index(ListRegistrationRequest $request): View
    {
        $registrations = $this->registrationService->listRegistrations(
            $request->validated()
        );

        $statuses = RegistrationStatus::cases();

        return view('agent.registrations.index', compact('registrations', 'statuses'));
    }

    /**
     * Show a single registration with full details.
     *
     * GET /agent/registrations/{registration}
     *
     * Includes:
     * - Customer information
     * - Current status and allowed transitions
     * - Status change audit log
     * - Promotion and loan information
     */
    public function show(Registration $registration): View
    {
        $registration->load('statusLogs.changedBy');

        $allowedTransitions = $registration->status->allowedTransitions();

        return view('agent.registrations.show', compact('registration', 'allowedTransitions'));
    }

    /**
     * Update registration status (state machine transition).
     *
     * PATCH /agent/registrations/{registration}/status
     *
     * Valid transitions are enforced by RegistrationStateService.
     * Invalid transitions return a friendly error message.
     */
    public function updateStatus(UpdateRegistrationStateRequest $request, Registration $registration): RedirectResponse
    {
        $newStatus = RegistrationStatus::from($request->validated('status'));

        try {
            $this->registrationService->transitionStatus(
                $registration,
                $newStatus,
                Auth::user(),
            );

            return $this->redirectToShow($registration, [
                'success' => "Status updated to '{$newStatus->label()}'.",
            ]);
        } catch (InvalidTransitionException $e) {
            return $this->redirectToShow($registration, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the down payment amount for a registration.
     *
     * PATCH /agent/registrations/{registration}/down-payment
     *
     * Also re-evaluates promotion eligibility and loan approval
     * since both depend on the down payment amount.
     */
    public function updateDownPayment(UpdateRegistrationRequest $request, Registration $registration): RedirectResponse
    {
        $this->registrationService->updateDownPayment(
            $registration,
            $request->validated('down_payment_cents'),
        );

        return $this->redirectToShow($registration, [
            'success' => 'Down payment updated successfully.',
        ]);
    }

    /**
     * Check if a registration qualifies for the CapBay Vroom promotion.
     *
     * POST /agent/registrations/{registration}/check-promotion
     *
     * Eligibility rules:
     * 1. Car model must be "CapBay Vroom"
     * 2. Must be among the first 10 registrants
     * 3. Must have ≥10% down payment
     * 4. Loan must be approved
     */
    public function checkPromotion(Registration $registration): RedirectResponse
    {
        $result = $this->registrationService->checkPromotionEligibility($registration);

        $message = $result['promotion_eligible']
            ? 'This customer is eligible for the promotion!'
            : 'This customer is NOT eligible for the promotion.';

        return $this->redirectToShow($registration, [
            'success' => $message,
        ]);
    }

    /**
     * Calculate the loan amount for a registration.
     *
     * POST /agent/registrations/{registration}/calculate-loan
     *
     * Formula: effective_price - down_payment
     * Where effective_price = discounted price if promotion applies.
     */
    public function calculateLoan(Registration $registration): RedirectResponse
    {
        $result = $this->registrationService->calculateLoan($registration);

        $loanAmount = number_format($result['loan_amount_cents'] / 100, 2);
        $status = $result['loan_approved'] ? 'approved' : 'not approved';

        return $this->redirectToShow($registration, [
            'success' => "Loan amount: RM {$loanAmount} ({$status}).",
        ]);
    }

    /**
     * Redirect back to the registration detail page with flash messages.
     *
     * @param  array<string, string>  $flash
     */
    private function redirectToShow(Registration $registration, array $flash): RedirectResponse
    {
        return redirect()
            ->route('agent.registrations.show', $registration)
            ->with($flash);
    }
}
