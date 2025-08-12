<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dateTime('clock_on_time')->nullable()->after('clock_off_lng'); // Hora del clock on
            $table->dateTime('clock_off_time')->nullable()->after('clock_on_time'); // Hora del clock off
            $table->decimal('radius', 8, 2)->nullable()->after('clock_off_time'); // Radio para ubicación
            $table->decimal('zoom', 8, 2)->nullable()->after('radius'); // Zoom del mapa o vista
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['clock_on_time', 'clock_off_time', 'radius', 'zoom']);
        });

    }
};
