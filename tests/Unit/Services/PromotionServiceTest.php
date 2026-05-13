<?php

use App\Models\Registration;
use App\Services\PromotionEligibilityService;

beforeEach(function () {
    $this->service = app(PromotionEligibilityService::class);
});

test('customer with wrong car model is not eligible', function () {
    $registration = Registration::factory()->create([
        'car_model' => 'CapBay Sedan',
        'car_price_cents' => 15_000_000,
        'down_payment_cents' => 3_000_000,
        'loan_approved' => true,
    ]);

    expect($this->service->isEligible($registration))->toBeFalse();
});

test('customer with insufficient down payment is not eligible', function () {
    $registration = Registration::factory()->capbayVroom()->create([
        'down_payment_cents' => 1_000_000, // 5% of RM 200,000
        'loan_approved' => true,
    ]);

    expect($this->service->isEligible($registration))->toBeFalse();
});

test('customer without loan approval is not eligible', function () {
    $registration = Registration::factory()->capbayVroom()->withDownPayment(20)->create([
        'loan_approved' => false,
    ]);

    expect($this->service->isEligible($registration))->toBeFalse();
});

test('first customer with 20% down payment and loan approved is eligible', function () {
    $registration = Registration::factory()->capbayVroom()->withDownPayment(20)->create([
        'loan_approved' => true,
    ]);

    expect($this->service->isEligible($registration))->toBeTrue();
});

test('eleventh customer is not eligible even with sufficient down payment', function () {
    // Create 10 registrations first
    Registration::factory()->capbayVroom()->count(10)->create();

    $registration = Registration::factory()->capbayVroom()->withDownPayment(10)->create([
        'loan_approved' => true,
    ]);

    expect($this->service->isEligible($registration))->toBeFalse();
});

test('cancelled registrations do not free up promotion slots', function () {
    // Create 10 registrations, 2nd one is cancelled
    Registration::factory()->capbayVroom()->create(); // 1st
    Registration::factory()->capbayVroom()->cancelled()->create(); // 2nd - cancelled
    Registration::factory()->capbayVroom()->count(8)->create(); // 3rd-10th

    // 11th registration
    $registration = Registration::factory()->capbayVroom()->withDownPayment(10)->create([
        'loan_approved' => true,
    ]);

    // Even though 2nd is cancelled, the 11th is still not eligible
    // because cancelled registrations count toward the 10-slot limit
    expect($this->service->isEligible($registration))->toBeFalse();
});

test('getQueuePosition returns correct position', function () {
    Registration::factory()->capbayVroom()->create(['created_at' => now()->subDays(3)]);
    Registration::factory()->capbayVroom()->create(['created_at' => now()->subDays(2)]);

    $third = Registration::factory()->capbayVroom()->create(['created_at' => now()->subDay()]);

    expect($this->service->getQueuePosition($third))->toBe(3);
});

test('getDiscountedPriceCents returns 85% of car price', function () {
    $registration = Registration::factory()->capbayVroom()->make();

    $discounted = $this->service->getDiscountedPriceCents($registration);

    expect($discounted)->toBe(17_000_000); // 85% of RM 200,000
});
