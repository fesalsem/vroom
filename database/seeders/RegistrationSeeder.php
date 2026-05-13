<?php

namespace Database\Seeders;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use Illuminate\Database\Seeder;

class RegistrationSeeder extends Seeder
{
    /**
     * Total registrations to generate (excluding the 15 CapBay Vroom seeds).
     */
    private const BULK_COUNT = 50_000;

    /**
     * Batch size for memory-efficient inserts.
     */
    private const BATCH_SIZE = 500;

    /**
     * Car models for non-promotion registrations.
     */
    private const OTHER_MODELS = [
        ['model' => 'CapBay Sedan',     'price' => 150_000_00],
        ['model' => 'CapBay SUV',       'price' => 180_000_00],
        ['model' => 'CapBay Hatchback', 'price' => 120_000_00],
        ['model' => 'CapBay EV',        'price' => 250_000_00],
    ];

    /**
     * Weighted status distribution for bulk registrations.
     *
     * Key   = status value
     * Value = weight (higher = more likely)
     */
    private const STATUS_WEIGHTS = [
        'registered'           => 40,
        'test_drive_scheduled' => 20,
        'test_drive_completed' => 15,
        'purchased'            => 10,
        'cancelled'            => 15,
    ];

    /**
     * Seed 50,015 registrations for performance and scalability testing.
     *
     * Distribution:
     * - 15 CapBay Vroom (promotion test scenarios)
     * - 50,000 bulk registrations with weighted statuses
     *
     * Memory strategy:
     * - Builds rows in an array, inserts in batches of 500
     * - Only one batch in memory at a time
     * - No model hydration for bulk data (uses insert)
     */
    public function run(): void
    {
        $this->command?->info('Seeding CapBay Vroom promotion test scenarios...');

        $this->seedPromotionScenarios();

        $this->command?->info('Seeding ' . number_format(self::BULK_COUNT) . ' bulk registrations...');

        $this->seedBulkRegistrations();

        $this->command?->info('Seeding complete!');
    }

    // ─── Promotion Test Scenarios ───────────────────────────

    /**
     * Create 15 CapBay Vroom registrations to test promotion logic.
     *
     * - Customer A (slot 1): eligible (first, ≥10% down payment)
     * - Customer B (slot 2): cancelled — does NOT free up slot
     * - Customers 3-10: fill slots 3-10 (various statuses)
     * - Customer C (slot 11): NOT eligible (beyond 10 slots)
     * - Customers 12-15: also NOT eligible
     */
    private function seedPromotionScenarios(): void
    {
        $now = now();

        // Customer A — 1st CapBay Vroom, eligible
        Registration::factory()->capbayVroom()->withDownPayment(20)->create([
            'customer_name'       => 'Customer A',
            'customer_email'      => 'customer.a@example.com',
            'down_payment_cents'  => 4_000_000, // 20% of RM 200,000
            'created_at'          => $now->subDays(14),
        ]);

        // Customer B — 2nd, cancelled (does NOT free up slot)
        Registration::factory()->capbayVroom()->cancelled()->create([
            'customer_name'       => 'Customer B',
            'customer_email'      => 'customer.b@example.com',
            'down_payment_cents'  => 2_000_000,
            'notes'               => 'Cancelled — requested via phone.',
            'created_at'          => $now->subDays(13),
        ]);

        // Customers 3-10 — fill promotion slots
        $statuses = [
            RegistrationStatus::Registered,
            RegistrationStatus::TestDriveScheduled,
            RegistrationStatus::TestDriveCompleted,
            RegistrationStatus::Purchased,
            RegistrationStatus::Registered,
            RegistrationStatus::TestDriveScheduled,
            RegistrationStatus::TestDriveCompleted,
            RegistrationStatus::Registered,
        ];

        foreach ($statuses as $i => $status) {
            $number = $i + 3;
            Registration::factory()->capbayVroom()->create([
                'customer_name'       => "Customer {$number}",
                'customer_email'      => "customer.{$number}@example.com",
                'status'              => $status,
                'down_payment_cents'  => fake()->randomElement([0, 1_000_000, 2_000_000, 3_000_000]),
                'created_at'          => $now->subDays(12 - $i),
            ]);
        }

        // Customer C — 11th, NOT eligible (beyond 10 slots)
        Registration::factory()->capbayVroom()->withDownPayment(10)->create([
            'customer_name'       => 'Customer C',
            'customer_email'      => 'customer.c@example.com',
            'down_payment_cents'  => 2_000_000,
            'created_at'          => $now->subDays(3),
        ]);

        // Customers 12-15 — also NOT eligible
        for ($i = 12; $i <= 15; $i++) {
            Registration::factory()->capbayVroom()->create([
                'customer_name'       => "Customer {$i}",
                'customer_email'      => "customer.{$i}@example.com",
                'down_payment_cents'  => fake()->randomElement([0, 1_000_000, 2_000_000]),
                'created_at'          => $now->subDays(2),
            ]);
        }
    }

    // ─── Bulk Registration Seeding ──────────────────────────

    /**
     * Seed 50,000 registrations with weighted status distribution.
     *
     * Uses raw inserts in batches to avoid memory exhaustion from
     * model hydration. Each batch of 500 is built, inserted, and
     * discarded before the next batch.
     */
    private function seedBulkRegistrations(): void
    {
        $batch = [];
        $now = now();
        $bar = $this->command?->getOutput()->createProgressBar(self::BULK_COUNT);
        $bar?->start();

        for ($i = 0; $i < self::BULK_COUNT; $i++) {
            $car = fake()->randomElement(self::OTHER_MODELS);
            $status = $this->weightedRandomStatus();
            $downPayment = fake()->randomElement([0, 0, 500_000, 1_000_000, 1_500_000, 2_000_000, 3_000_000]);

            $batch[] = [
                'customer_name'      => fake()->randomElement(['Mr.', 'Ms.', 'Mrs.']) . ' ' . fake()->name(),
                'customer_email'     => fake()->unique()->safeEmail(),
                'customer_phone'     => '+60' . fake()->numberBetween(10_000_000, 19_999_999),
                'car_model'          => $car['model'],
                'car_price_cents'    => $car['price'],
                'down_payment_cents' => $downPayment,
                'status'             => $status,
                'promotion_eligible' => null,
                'loan_amount_cents'  => null,
                'loan_approved'      => null,
                'notes'              => null,
                'created_at'         => $now->subMinutes(fake()->numberBetween(1, 60 * 24 * 90)),
                'updated_at'         => $now,
            ];

            if (count($batch) >= self::BATCH_SIZE) {
                Registration::insert($batch);
                $batch = [];
                $bar?->advance(self::BATCH_SIZE);
            }
        }

        // Insert remaining records
        if (! empty($batch)) {
            Registration::insert($batch);
            $bar?->advance(count($batch));
        }

        $bar?->finish();
        $this->command?->info("\n");
    }

    // ─── Helpers ────────────────────────────────────────────

    /**
     * Select a status using weighted random distribution.
     */
    private function weightedRandomStatus(): string
    {
        $total = array_sum(self::STATUS_WEIGHTS);
        $random = fake()->numberBetween(1, $total);
        $cumulative = 0;

        foreach (self::STATUS_WEIGHTS as $status => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $status;
            }
        }

        return RegistrationStatus::Registered->value;
    }
}
