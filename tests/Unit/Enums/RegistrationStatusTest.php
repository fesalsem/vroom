<?php

use App\Enums\RegistrationStatus;

test('all statuses have correct string values', function () {
    expect(RegistrationStatus::Registered->value)->toBe('registered');
    expect(RegistrationStatus::TestDriveScheduled->value)->toBe('test_drive_scheduled');
    expect(RegistrationStatus::TestDriveCompleted->value)->toBe('test_drive_completed');
    expect(RegistrationStatus::Purchased->value)->toBe('purchased');
    expect(RegistrationStatus::Cancelled->value)->toBe('cancelled');
});

test('registered can transition to test drive scheduled or cancelled', function () {
    $status = RegistrationStatus::Registered;

    expect($status->canTransitionTo(RegistrationStatus::TestDriveScheduled))->toBeTrue();
    expect($status->canTransitionTo(RegistrationStatus::Cancelled))->toBeTrue();
    expect($status->canTransitionTo(RegistrationStatus::TestDriveCompleted))->toBeFalse();
    expect($status->canTransitionTo(RegistrationStatus::Purchased))->toBeFalse();
});

test('test drive scheduled can transition to completed or cancelled', function () {
    $status = RegistrationStatus::TestDriveScheduled;

    expect($status->canTransitionTo(RegistrationStatus::TestDriveCompleted))->toBeTrue();
    expect($status->canTransitionTo(RegistrationStatus::Cancelled))->toBeTrue();
    expect($status->canTransitionTo(RegistrationStatus::Registered))->toBeFalse();
    expect($status->canTransitionTo(RegistrationStatus::Purchased))->toBeFalse();
});

test('test drive completed can transition to purchased or cancelled', function () {
    $status = RegistrationStatus::TestDriveCompleted;

    expect($status->canTransitionTo(RegistrationStatus::Purchased))->toBeTrue();
    expect($status->canTransitionTo(RegistrationStatus::Cancelled))->toBeTrue();
    expect($status->canTransitionTo(RegistrationStatus::Registered))->toBeFalse();
    expect($status->canTransitionTo(RegistrationStatus::TestDriveScheduled))->toBeFalse();
});

test('purchased is a terminal state', function () {
    $status = RegistrationStatus::Purchased;

    expect($status->allowedTransitions())->toBeEmpty();
});

test('cancelled is a terminal state', function () {
    $status = RegistrationStatus::Cancelled;

    expect($status->allowedTransitions())->toBeEmpty();
});

test('labels are human readable', function () {
    expect(RegistrationStatus::Registered->label())->toBe('Registered');
    expect(RegistrationStatus::TestDriveScheduled->label())->toBe('Test Drive Scheduled');
    expect(RegistrationStatus::TestDriveCompleted->label())->toBe('Test Drive Completed');
    expect(RegistrationStatus::Purchased->label())->toBe('Purchased');
    expect(RegistrationStatus::Cancelled->label())->toBe('Cancelled');
});
