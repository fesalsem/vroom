<?php

use App\Enums\RegistrationStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\Registration;
use App\Models\RegistrationStatusLog;
use App\Models\User;
use App\Services\RegistrationStateService;

beforeEach(function () {
    $this->service = app(RegistrationStateService::class);
    $this->registration = Registration::factory()->create();
});

test('can transition to a valid next state', function () {
    $result = $this->service->transition(
        $this->registration,
        RegistrationStatus::TestDriveScheduled,
    );

    expect($result->status)->toBe(RegistrationStatus::TestDriveScheduled);
});

test('throws exception for invalid transition', function () {
    $this->service->transition(
        $this->registration,
        RegistrationStatus::Purchased, // Cannot go from Registered to Purchased directly
    );
})->throws(InvalidTransitionException::class);

test('cannot transition from purchased', function () {
    $registration = Registration::factory()->purchased()->create();

    expect(fn () => $this->service->transition(
        $registration,
        RegistrationStatus::Registered,
    ))->toThrow(InvalidTransitionException::class);
});

test('cannot transition from cancelled', function () {
    $registration = Registration::factory()->cancelled()->create();

    expect(fn () => $this->service->transition(
        $registration,
        RegistrationStatus::Registered,
    ))->toThrow(InvalidTransitionException::class);
});

test('status log is created on transition', function () {
    $this->service->transition(
        $this->registration,
        RegistrationStatus::TestDriveScheduled,
    );

    expect(RegistrationStatusLog::count())->toBe(1);
    expect(RegistrationStatusLog::first()->to_status)->toBe(RegistrationStatus::TestDriveScheduled);
});

test('status log records the user who made the change', function () {
    $user = User::factory()->create();

    $this->service->transition(
        $this->registration,
        RegistrationStatus::TestDriveScheduled,
        $user,
    );

    expect(RegistrationStatusLog::first()->changed_by)->toBe($user->id);
});

test('getAllowedTransitions returns correct states', function () {
    $transitions = $this->service->getAllowedTransitions($this->registration);

    expect($transitions)->toHaveCount(2);
    expect($transitions)->toContain(RegistrationStatus::TestDriveScheduled);
    expect($transitions)->toContain(RegistrationStatus::Cancelled);
});
