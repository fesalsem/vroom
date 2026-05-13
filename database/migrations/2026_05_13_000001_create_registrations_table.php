<?php

use App\Enums\RegistrationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->string('car_model');
            $table->bigInteger('car_price_cents');
            $table->bigInteger('down_payment_cents')->default(0);
            $table->string('status')->default(RegistrationStatus::Registered->value);
            $table->boolean('promotion_eligible')->nullable();
            $table->bigInteger('loan_amount_cents')->nullable();
            $table->boolean('loan_approved')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance at 50k+ records
            $table->index('customer_email');
            $table->index('status');
            $table->index('created_at');
            $table->index(['car_model', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
