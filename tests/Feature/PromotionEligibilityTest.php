<?php

use App\Models\Registration;
use App\Models\User;

beforeEach(function () {
    $this->agent = User::factory()->create();
});

test('Customer A (1st, 20% down) is eligible for promotion', function () {
    $registration = Registration::factory()->capbayVroom()->withDownPayment(20)->create([
        'customer_name' => 'Customer A',
        'loan_approved' => true,
    ]);

    $this->actingAs($this->agent)
        ->post(route('agent.registrations.check-promotion', $registration));

    expect($registration->fresh()->promotion_eligible)->toBeTrue();
});

test('Customer B (2nd, cancelled) is not eligible', function () {
    Registration::factory()->capbayVroom()->create(); // 1st
    $customerB = Registration::factory()->capbayVroom()->cancelled()->create([
        'customer_name' => 'Customer B',
    ]);

    $this->actingAs($this->agent)
        ->post(route('agent.registrations.check-promotion', $customerB));

    // Customer B cancelled, so they never purchased → not eligible
    expect($customerB->fresh()->promotion_eligible)->toBeFalse();
});

test('Customer C (11th, 10% down) is not eligible because slots are full', function () {
    // Create 10 CapBay Vroom registrations to fill slots
    Registration::factory()->capbayVroom()->count(10)->create();

    $customerC = Registration::factory()->capbayVroom()->withDownPayment(10)->create([
        'customer_name' => 'Customer C',
        'loan_approved' => true,
    ]);

    $this->actingAs($this->agent)
        ->post(route('agent.registrations.check-promotion', $customerC));

    expect($customerC->fresh()->promotion_eligible)->toBeFalse();
});

test('cancelled registrations do not free up promotion slots', function () {
    // 1st - normal
    Registration::factory()->capbayVroom()->create();
    // 2nd - cancelled
    Registration::factory()->capbayVroom()->cancelled()->create();
    // 3rd-10th - normal
    Registration::factory()->capbayVroom()->count(8)->create();

    // 11th registration - should NOT be eligible even though 2nd is cancelled
    $eleventh = Registration::factory()->capbayVroom()->withDownPayment(10)->create([
        'customer_name' => 'Eleventh Customer',
        'loan_approved' => true,
    ]);

    $this->actingAs($this->agent)
        ->post(route('agent.registrations.check-promotion', $eleventh));

    expect($eleventh->fresh()->promotion_eligible)->toBeFalse();
});
