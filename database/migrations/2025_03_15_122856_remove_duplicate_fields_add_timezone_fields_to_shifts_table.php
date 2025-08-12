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
            // Eliminar campos duplicados
            $table->dropColumn([
                'status',
                'date_start_employee',
                'date_finish_employee',
            ]);

            // Añadir nuevos campos para timezones
            $table->string('date_start_timezone', 100)->nullable()->after('date_start')
                ->comment('Timezone name (e.g., America/New_York) calculated from coordinates for shift start');
            $table->string('date_end_timezone', 100)->nullable()->after('date_end')
                ->comment('Timezone name (e.g., America/New_York) calculated from coordinates for shift end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Restaurar campos eliminados
            $table->integer('status')->default(0)->comment('0 not confirmed, 1 confirmed, 2 rejected');
            $table->dateTime('date_start_employee')->nullable();
            $table->dateTime('date_finish_employee')->nullable();

            // Eliminar los nuevos campos
            $table->dropColumn([
                'date_start_timezone',
                'date_end_timezone',
            ]);
        });
    }
};
