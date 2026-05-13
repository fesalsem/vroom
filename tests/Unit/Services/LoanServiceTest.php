<?php

use App\Models\Registration;
use App\Services\LoanCalculationService;

beforeEach(function () {
    $this->service = app(LoanCalculationService::class);
});

test('loan amount without promotion is car price minus down payment', function () {
    $registration = Registration::factory()->capbayVroom()->withDownPayment(20)->make();

    $loanAmount = $this->service->calculateLoanAmount($registration, false);

    // RM 200,000 - RM 40,000 = RM 160,000
    expect($loanAmount)->toBe(16_000_000);
});

test('loan amount with promotion is discounted price minus down payment', function () {
    $registration = Registration::factory()->capbayVroom()->withDownPayment(20)->make();

    $loanAmount = $this->service->calculateLoanAmount($registration, true);

    // RM 170,000 (85% of 200k) - RM 40,000 = RM 130,000
    expect($loanAmount)->toBe(13_000_000);
});

test('loan amount is never negative', function () {
    $registration = Registration::factory()->capbayVroom()->create([
        'down_payment_cents' => 25_000_000, // More than car price
    ]);

    $loanAmount = $this->service->calculateLoanAmount($registration, false);

    expect($loanAmount)->toBe(0);
});

test('loan is approved with at least 10% down payment without promotion', function () {
    $registration = Registration::factory()->capbayVroom()->withDownPayment(10)->make();

    expect($this->service->isLoanApproved($registration, false))->toBeTrue();
});

test('loan is not approved with less than 10% down payment', function () {
    $registration = Registration::factory()->capbayVroom()->withDownPayment(5)->make();

    expect($this->service->isLoanApproved($registration, false))->toBeFalse();
});

test('loan approval threshold uses discounted price when promotion applies', function () {
    // 10% of RM 170,000 (discounted) = RM 17,000
    // So RM 17,000 down payment is enough with promotion
    $registration = Registration::factory()->capbayVroom()->create([
        'down_payment_cents' => 1_700_000, // 8.5% of original price, but 10% of discounted
    ]);

    // Without promotion: 1.7M < 2M (10% of 200k) → not approved
    expect($this->service->isLoanApproved($registration, false))->toBeFalse();

    // With promotion: 1.7M >= 1.7M (10% of 170k) → approved
    expect($this->service->isLoanApproved($registration, true))->toBeTrue();
});
