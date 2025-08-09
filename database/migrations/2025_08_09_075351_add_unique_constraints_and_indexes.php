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
        // Drop existing unique constraint on users.email
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            // Add composite unique constraint: company_id + email
            $table->unique(['company_id', 'email']);
        });

        // Add unique constraints and indexes
        Schema::table('employees', function (Blueprint $table) {
            $table->unique(['company_id', 'email']);
        });

        Schema::table('shift_types', function (Blueprint $table) {
            $table->unique(['company_id', 'name']);
        });

        Schema::table('pay_periods', function (Blueprint $table) {
            $table->unique(['company_id', 'start_date', 'end_date']);
        });

        // Add suggested indexes
        Schema::table('shifts', function (Blueprint $table) {
            $table->index(['company_id', 'employee_id']);
            $table->index(['company_id', 'shift_type_id']);
            $table->index(['company_id', 'date_start']);
        });

        Schema::table('shift_configurations', function (Blueprint $table) {
            $table->index(['company_id', 'employee_id', 'shift_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'employee_id']);
            $table->dropIndex(['company_id', 'shift_type_id']);
            $table->dropIndex(['company_id', 'date_start']);
        });

        Schema::table('shift_configurations', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'employee_id', 'shift_type_id']);
        });

        // Remove unique constraints
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'email']);
            $table->unique('email');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'email']);
        });

        Schema::table('shift_types', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'name']);
        });

        Schema::table('pay_periods', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'start_date', 'end_date']);
        });
    }
};
