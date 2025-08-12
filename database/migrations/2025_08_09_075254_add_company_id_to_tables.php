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
        // Add company_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id');
        });

        // Add company_id to employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id');
        });

        // Add company_id to shift_types table
        Schema::table('shift_types', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id');
        });

        // Add company_id to shift_configurations table
        Schema::table('shift_configurations', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id');
        });

        // Add company_id to shifts table
        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id');
        });

        // Add company_id to pay_periods table
        Schema::table('pay_periods', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });

        Schema::table('shift_types', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });

        Schema::table('shift_configurations', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });

        Schema::table('pay_periods', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};
