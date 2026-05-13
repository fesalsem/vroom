<?php

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use App\Models\User;

beforeEach(function () {
    $this->agent = User::factory()->create();
});

test('agent can transition from registered to test drive scheduled', function () {
    $registration = Registration::factory()->create();

    $response = $this->actingAs($this->agent)
        ->patch(
            route('agent.registrations.update-status', $registration),
            ['status' => RegistrationStatus::TestDriveScheduled->value]
        );

    $response->assertRedirect();
    expect($registration->fresh()->status)->toBe(RegistrationStatus::TestDriveScheduled);
});

test('agent can transition from test drive scheduled to completed', function () {
    $registration = Registration::factory()->testDriveScheduled()->create();

    $response = $this->actingAs($this->agent)
        ->patch(
            route('agent.registrations.update-status', $registration),
            ['status' => RegistrationStatus::TestDriveCompleted->value]
        );

    $response->assertRedirect();
    expect($registration->fresh()->status)->toBe(RegistrationStatus::TestDriveCompleted);
});

test('agent can transition from completed to purchased', function () {
    $registration = Registration::factory()->testDriveCompleted()->create();

    $response = $this->actingAs($this->agent)
        ->patch(
            route('agent.registrations.update-status', $registration),
            ['status' => RegistrationStatus::Purchased->value]
        );

    $response->assertRedirect();
    expect($registration->fresh()->status)->toBe(RegistrationStatus::Purchased);
});

test('agent can cancel a registration at any non-terminal state', function () {
    $registration = Registration::factory()->testDriveScheduled()->create();

    $response = $this->actingAs($this->agent)
        ->patch(
            route('agent.registrations.update-status', $registration),
            ['status' => RegistrationStatus::Cancelled->value]
        );

    $response->assertRedirect();
    expect($registration->fresh()->status)->toBe(RegistrationStatus::Cancelled);
});

test('invalid transition returns error', function () {
    // Cannot go from Registered directly to Purchased
    $registration = Registration::factory()->create();

    $response = $this->actingAs($this->agent)
        ->patch(
            route('agent.registrations.update-status', $registration),
            ['status' => RegistrationStatus::Purchased->value]
        );

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($registration->fresh()->status)->toBe(RegistrationStatus::Registered);
});

test('cannot transition from purchased', function () {
    $registration = Registration::factory()->purchased()->create();

    $response = $this->actingAs($this->agent)
        ->patch(
            route('agent.registrations.update-status', $registration),
            ['status' => RegistrationStatus::Registered->value]
        );

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('cannot transition from cancelled', function () {
    $registration = Registration::factory()->cancelled()->create();

    $response = $this->actingAs($this->agent)
        ->patch(
            route('agent.registrations.update-status', $registration),
            ['status' => RegistrationStatus::Registered->value]
        );

    $response->assertRedirect();
    $response->assertSessionHas('error');
});
