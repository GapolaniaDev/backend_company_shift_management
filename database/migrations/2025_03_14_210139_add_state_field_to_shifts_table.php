<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->tinyInteger('state')->default(0)->comment('0 = not_started, 1 = started, 2 = finished');
        });

        // Update existing shifts with the proper state based on current data
        DB::statement('
            UPDATE shifts 
            SET state = CASE 
                WHEN clock_off_time IS NOT NULL THEN 2 
                WHEN clock_on_time IS NOT NULL THEN 1 
                ELSE 0 
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};
