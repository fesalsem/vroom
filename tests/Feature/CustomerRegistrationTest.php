<?php

use App\Models\Registration;

test('customer can view the registration form', function () {
    $response = $this->get(route('test-drive.create'));

    $response->assertStatus(200);
    $response->assertSee('Book a Test Drive');
    $response->assertSee('CapBay Vroom');
});

test('customer can submit valid registration data', function () {
    $response = $this->post(route('test-drive.store'), [
        'customer_name' => 'John Doe',
        'customer_email' => 'john@example.com',
        'customer_phone' => '0123456789',
    ]);

    $response->assertRedirect(route('test-drive.thank-you'));

    $this->assertDatabaseHas('registrations', [
        'customer_name' => 'John Doe',
        'customer_email' => 'john@example.com',
        'customer_phone' => '0123456789',
        'car_model' => 'CapBay Vroom',
        'car_price_cents' => 20_000_000,
    ]);
});

test('customer cannot submit with missing required fields', function () {
    $response = $this->post(route('test-drive.store'), [
        'customer_name' => '',
        'customer_email' => '',
        'customer_phone' => '',
    ]);

    $response->assertSessionHasErrors(['customer_name', 'customer_email', 'customer_phone']);
});

test('customer is redirected to thank-you page after successful registration', function () {
    $response = $this->post(route('test-drive.store'), [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '9876543210',
    ]);

    $response->assertRedirect(route('test-drive.thank-you'));

    $this->get(route('test-drive.thank-you'))
        ->assertSee('Thank You')
        ->assertSee('test drive registration has been received');
});

test('duplicate email is allowed for different registrations', function () {
    $this->post(route('test-drive.store'), [
        'customer_name' => 'First Registration',
        'customer_email' => 'same@example.com',
        'customer_phone' => '0111111111',
    ]);

    $response = $this->post(route('test-drive.store'), [
        'customer_name' => 'Second Registration',
        'customer_email' => 'same@example.com',
        'customer_phone' => '0222222222',
    ]);

    $response->assertRedirect(route('test-drive.thank-you'));
    expect(Registration::where('customer_email', 'same@example.com')->count())->toBe(2);
});
