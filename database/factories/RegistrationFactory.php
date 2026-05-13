<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    /**
     * Malaysian name prefixes for realistic data.
     */
    private const PREFIXES = ['Mr.', 'Ms.', 'Mrs.', 'Dr.', 'Ir.', 'Ts.'];

    /**
     * Malaysian car models with realistic prices (in cents).
     */
    private const CAR_MODELS = [
        'CapBay Vroom' => 200_000_00,
        'CapBay Sedan' => 150_000_00,
        'CapBay SUV'   => 180_000_00,
        'CapBay Hatchback' => 120_000_00,
        'CapBay EV'    => 250_000_00,
    ];

    public function definition(): array
    {
        $carModel = fake()->randomElement(array_keys(self::CAR_MODELS));
        $carPrice = self::CAR_MODELS[$carModel];

        return [
            'customer_name'       => fake()->randomElement(self::PREFIXES) . ' ' . fake()->name(),
            'customer_email'      => fake()->unique()->safeEmail(),
            'customer_phone'      => '+60' . fake()->numberBetween(10_000_000, 19_999_999),
            'car_model'           => $carModel,
            'car_price_cents'     => $carPrice,
            'down_payment_cents'  => 0,
            'status'              => RegistrationStatus::Registered,
            'promotion_eligible'  => null,
            'loan_amount_cents'   => null,
            'loan_approved'       => null,
            'notes'               => null,
        ];
    }

    // ─── Car Model States ───────────────────────────────────

    /**
     * CapBay Vroom — the promotion-eligible model.
     */
    public function capbayVroom(): static
    {
        return $this->state(fn () => [
            'car_model'       => 'CapBay Vroom',
            'car_price_cents' => 200_000_00,
        ]);
    }

    /**
     * A non-promotion car model.
     */
    public function nonPromotionCar(): static
    {
        return $this->state(fn () => [
            'car_model'       => fake()->randomElement(['CapBay Sedan', 'CapBay SUV', 'CapBay Hatchback', 'CapBay EV']),
            'car_price_cents' => self::CAR_MODELS[fake()->randomElement(['CapBay Sedan', 'CapBay SUV', 'CapBay Hatchback', 'CapBay EV'])],
        ]);
    }

    // ─── Down Payment States ────────────────────────────────

    /**
     * Set a specific down payment as a percentage of car price.
     */
    public function withDownPayment(int $percent): static
    {
        return $this->state(fn (array $attributes) => [
            'down_payment_cents' => (int) round(
                ($attributes['car_price_cents'] ?? 200_000_00) * min($percent, 100) / 100
            ),
        ]);
    }

    /**
     * A random realistic down payment (0%, 5%, 10%, 15%, 20%, 25%, 30%).
     */
    public function randomDownPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'down_payment_cents' => (int) round(
                ($attributes['car_price_cents'] ?? 200_000_00)
                * fake()->randomElement([0, 5, 10, 10, 15, 20, 25, 30])
                / 100
            ),
        ]);
    }

    // ─── Status States ──────────────────────────────────────

    public function registered(): static
    {
        return $this->state(fn () => ['status' => RegistrationStatus::Registered]);
    }

    public function testDriveScheduled(): static
    {
        return $this->state(fn () => ['status' => RegistrationStatus::TestDriveScheduled]);
    }

    public function testDriveCompleted(): static
    {
        return $this->state(fn () => ['status' => RegistrationStatus::TestDriveCompleted]);
    }

    public function purchased(): static
    {
        return $this->state(fn () => ['status' => RegistrationStatus::Purchased]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => RegistrationStatus::Cancelled]);
    }

    // ─── Loan States ────────────────────────────────────────

    /**
     * Mark loan as approved with a calculated amount.
     */
    public function loanApproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'loan_approved'    => true,
            'loan_amount_cents' => (int) round(
                ($attributes['car_price_cents'] ?? 200_000_00) - ($attributes['down_payment_cents'] ?? 0)
            ),
        ]);
    }

    /**
     * Mark loan as rejected.
     */
    public function loanRejected(): static
    {
        return $this->state(fn () => [
            'loan_approved'    => false,
            'loan_amount_cents' => 0,
        ]);
    }

    // ─── Promotion States ───────────────────────────────────

    public function promotionEligible(): static
    {
        return $this->state(fn () => ['promotion_eligible' => true]);
    }

    public function promotionNotEligible(): static
    {
        return $this->state(fn () => ['promotion_eligible' => false]);
    }

    // ─── Composite Convenience States ───────────────────────

    /**
     * A complete "purchased" scenario with down payment and loan.
     */
    public function fullyPurchased(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'             => RegistrationStatus::Purchased,
            'down_payment_cents' => (int) round(
                ($attributes['car_price_cents'] ?? 200_000_00) * fake()->randomElement([10, 15, 20]) / 100
            ),
            'loan_approved'      => true,
            'loan_amount_cents'  => (int) round(
                ($attributes['car_price_cents'] ?? 200_000_00) * fake()->randomElement([80, 85, 90]) / 100
            ),
            'promotion_eligible' => fake()->boolean(20) ? true : false,
        ]);
    }

    /**
     * A cancelled registration with optional notes.
     */
    public function fullyCancelled(): static
    {
        return $this->state(fn () => [
            'status' => RegistrationStatus::Cancelled,
            'notes'  => fake()->randomElement([
                'Customer changed mind.',
                'Found better deal elsewhere.',
                'Unable to secure financing.',
                'Requested cancellation via email.',
                null,
                null,
                null,
            ]),
        ]);
    }
}
