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
            $table->string('timezone_start', 64)->nullable()->after('clock_on_time')
                ->comment('Timezone where clock-on occurred (e.g., America/New_York)');
            $table->string('timezone_end', 64)->nullable()->after('clock_off_time')
                ->comment('Timezone where clock-off occurred (e.g., America/Los_Angeles)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['timezone_start', 'timezone_end']);
        });
    }
};
