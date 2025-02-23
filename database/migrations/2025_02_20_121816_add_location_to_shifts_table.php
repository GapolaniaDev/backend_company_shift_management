<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocationToShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Shift location in Google Maps
            $table->decimal('location_lat', 18, 15)->nullable()->comment('Latitude of the shift location in Google Maps');
            $table->decimal('location_lng', 18, 15)->nullable()->comment('Longitude of the shift location in Google Maps');

            // Clock On location
            $table->decimal('clock_on_lat', 18, 15)->nullable()->comment('Latitude where the user performed Clock On');
            $table->decimal('clock_on_lng', 18, 15)->nullable()->comment('Longitude where the user performed Clock On');

            // Clock Off location
            $table->decimal('clock_off_lat', 18, 15)->nullable()->comment('Latitude where the user performed Clock Off');
            $table->decimal('clock_off_lng', 18, 15)->nullable()->comment('Longitude where the user performed Clock Off');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn([
                'location_lat',
                'location_lng',
                'clock_on_lat',
                'clock_on_lng',
                'clock_off_lat',
                'clock_off_lng'
            ]);
        });
    }
}
