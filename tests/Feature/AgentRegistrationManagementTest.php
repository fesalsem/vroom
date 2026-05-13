<?php

use App\Models\Registration;
use App\Models\User;

beforeEach(function () {
    $this->agent = User::factory()->create();
});

test('unauthenticated users cannot access agent routes', function () {
    $this->get(route('agent.registrations.index'))
        ->assertRedirect(route('login'));

    $this->get(route('agent.registrations.show', 1))
        ->assertRedirect(route('login'));
});

test('agent can view paginated registration list', function () {
    Registration::factory()->count(20)->create();

    $response = $this->actingAs($this->agent)
        ->get(route('agent.registrations.index'));

    $response->assertStatus(200);
    $response->assertSee('Registrations');
});

test('agent can view registration detail', function () {
    $registration = Registration::factory()->create([
        'customer_name' => 'Test Customer',
    ]);

    $response = $this->actingAs($this->agent)
        ->get(route('agent.registrations.show', $registration));

    $response->assertStatus(200);
    $response->assertSee('Test Customer');
});

test('agent can filter registrations by status', function () {
    Registration::factory()->count(5)->create();
    Registration::factory()->cancelled()->count(3)->create();

    $response = $this->actingAs($this->agent)
        ->get(route('agent.registrations.index', ['status' => 'cancelled']));

    $response->assertStatus(200);
});

test('agent can search registrations by name', function () {
    Registration::factory()->create(['customer_name' => 'Unique Name Search']);
    Registration::factory()->count(10)->create();

    $response = $this->actingAs($this->agent)
        ->get(route('agent.registrations.index', ['search' => 'Unique Name']));

    $response->assertStatus(200);
    $response->assertSee('Unique Name Search');
});

test('agent can update down payment', function () {
    $registration = Registration::factory()->capbayVroom()->create();

    $response = $this->actingAs($this->agent)
        ->patch(
            route('agent.registrations.update-down-payment', $registration),
            ['down_payment_cents' => 5_000_000]
        );

    $response->assertRedirect();
    $this->assertEquals(5_000_000, $registration->fresh()->down_payment_cents);
});

test('agent can check promotion eligibility', function () {
    $registration = Registration::factory()->capbayVroom()->withDownPayment(20)->create([
        'loan_approved' => true,
    ]);

    $response = $this->actingAs($this->agent)
        ->post(route('agent.registrations.check-promotion', $registration));

    $response->assertRedirect();
    expect($registration->fresh()->promotion_eligible)->toBeTrue();
});

test('agent can calculate loan', function () {
    $registration = Registration::factory()->capbayVroom()->withDownPayment(20)->create();

    $response = $this->actingAs($this->agent)
        ->post(route('agent.registrations.calculate-loan', $registration));

    $response->assertRedirect();
    expect($registration->fresh()->loan_amount_cents)->not->toBeNull();
});
