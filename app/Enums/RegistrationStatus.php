<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Registered = 'registered';
    case TestDriveScheduled = 'test_drive_scheduled';
    case TestDriveCompleted = 'test_drive_completed';
    case Purchased = 'purchased';
    case Cancelled = 'cancelled';

    /**
     * Get the valid next states from the current state.
     *
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Registered => [self::TestDriveScheduled, self::Cancelled],
            self::TestDriveScheduled => [self::TestDriveCompleted, self::Cancelled],
            self::TestDriveCompleted => [self::Purchased, self::Cancelled],
            self::Purchased => [],
            self::Cancelled => [],
        };
    }

    /**
     * Determine if transitioning to the given next state is allowed.
     */
    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * Check if this state is terminal (no further transitions allowed).
     */
    public function isTerminal(): bool
    {
        return $this === self::Purchased || $this === self::Cancelled;
    }

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Registered',
            self::TestDriveScheduled => 'Test Drive Scheduled',
            self::TestDriveCompleted => 'Test Drive Completed',
            self::Purchased => 'Purchased',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Get all values as an array of strings (useful for validation rules).
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
