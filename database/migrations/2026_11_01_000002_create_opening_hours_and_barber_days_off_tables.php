<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Shop opening hours - one row per day of the week
        Schema::create('opening_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_week')->unique(); // 0 = Sunday ... 6 = Saturday
            $table->boolean('is_closed')->default(false);
            $table->string('open_time', 5)->default('10:00');     // "HH:MM"
            $table->string('close_time', 5)->default('22:00');
            $table->string('break_start', 5)->nullable();         // e.g. "19:00"
            $table->string('break_end', 5)->nullable();           // e.g. "20:00"
            $table->timestamps();
        });

        // Single dates a barber is away (MC, cuti, holiday)
        Schema::create('barber_days_off', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('reason', 100)->nullable();
            $table->timestamps();

            $table->unique(['barber_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barber_days_off');
        Schema::dropIfExists('opening_hours');
    }
};
