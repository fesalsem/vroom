<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\Registration;
use App\Models\RegistrationStatusLog;
use App\Models\User;

class StateMachineService
{
    /**
     * Transition a registration to a new status.
     *
     * @throws InvalidTransitionException
     */
    public function transition(Registration $registration, RegistrationStatus $newStatus, ?User $changedBy = null): Registration
    {
        $currentStatus = $registration->status;

        if (! $currentStatus->canTransitionTo($newStatus)) {
            throw new InvalidTransitionException($currentStatus, $newStatus);
        }

        $this->logTransition($registration, $currentStatus, $newStatus, $changedBy);

        $registration->update(['status' => $newStatus]);

        return $registration->fresh();
    }

    /**
     * Get all allowed next states for a registration.
     *
     * @return RegistrationStatus[]
     */
    public function getAllowedTransitions(Registration $registration): array
    {
        return $registration->status->allowedTransitions();
    }

    /**
     * Record the state transition in the audit log.
     */
    private function logTransition(
        Registration $registration,
        ?RegistrationStatus $from,
        RegistrationStatus $to,
        ?User $changedBy = null,
    ): void {
        RegistrationStatusLog::create([
            'registration_id' => $registration->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'changed_by' => $changedBy?->id,
        ]);
    }
}
