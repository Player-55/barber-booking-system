<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. Services & Pricing
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->integer('duration_minutes')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Barbers Management
        Schema::create('barbers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Bookings & RM5 Deposit Management
       Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('barber_id')->constrained()->onDelete('cascade');
            $table->date('booking_date')->index();
            $table->string('booking_time', 5); // start, e.g. "14:00"
            $table->string('end_time', 5);     // end, e.g. "14:30"
            $table->decimal('total_price', 8, 2);
            $table->decimal('deposit_amount', 8, 2)->default(5.00);
            $table->string('status')->default('pending')->index();
            $table->string('payment_status')->default('unpaid');
            $table->timestamps();

            // Speeds up the double-booking check
            $table->index(['barber_id', 'booking_date']);
        });
        
        // 4. Shop Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('barbers');
        Schema::dropIfExists('services');
        Schema::dropIfExists('settings');
    }
};