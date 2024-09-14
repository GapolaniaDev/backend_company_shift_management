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
            $table->integer('status')->default(0)->comment('0 not confirmed, 1 confirmed, 2 rejected');
            $table->date('date_start_employee')->nullable();
            $table->date('date_finish_employee')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('date_start_employee');
            $table->dropColumn('date_finish_employee');
        });
    }
};
