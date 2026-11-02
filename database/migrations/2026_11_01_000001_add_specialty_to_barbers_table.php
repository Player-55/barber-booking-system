<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('barbers', function (Blueprint $table) {
            // Short title shown on the booking page, e.g. "Senior Stylist"
            $table->string('specialty', 50)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('barbers', function (Blueprint $table) {
            $table->dropColumn('specialty');
        });
    }
};
