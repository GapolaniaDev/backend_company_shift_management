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
        // Additional performance indexes for the new schema

        // Composite indexes for common queries
        Schema::table('shifts', function (Blueprint $table) {
            $table->index(['date_start', 'shift_status'], 'idx_shifts_date_status');
            $table->index(['shift_type_id', 'date_start'], 'idx_shifts_type_date');
        });

        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->index(['employee_id', 'status', 'assigned_at'], 'idx_assignments_emp_status_date');
            $table->index(['shift_id', 'assignment_type', 'status'], 'idx_assignments_shift_type_status');
        });

        Schema::table('shift_templates', function (Blueprint $table) {
            $table->index(['company_id', 'is_active', 'effective_from'], 'idx_templates_company_active_date');
            $table->index(['shift_type_id', 'is_active'], 'idx_templates_type_active');
        });

        Schema::table('replacement_requests', function (Blueprint $table) {
            $table->index(['status', 'urgency', 'requested_at'], 'idx_replacements_status_urgency_date');
        });

        Schema::table('replacement_bids', function (Blueprint $table) {
            $table->index(['replacement_request_id', 'bid_status', 'bid_at'], 'idx_bids_request_status_date');
        });

        Schema::table('shift_swaps', function (Blueprint $table) {
            $table->index(['status', 'requires_approval'], 'idx_swaps_status_approval');
        });

        // Add soft deletes support to key tables (for audit trail)
        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->softDeletes();
            $table->index('deleted_at');
        });

        Schema::table('replacement_requests', function (Blueprint $table) {
            $table->softDeletes();
            $table->index('deleted_at');
        });

        Schema::table('shift_swaps', function (Blueprint $table) {
            $table->softDeletes();
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove composite indexes
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('idx_shifts_date_status');
            $table->dropIndex('idx_shifts_type_date');
        });

        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->dropIndex('idx_assignments_emp_status_date');
            $table->dropIndex('idx_assignments_shift_type_status');
            $table->dropSoftDeletes();
        });

        Schema::table('shift_templates', function (Blueprint $table) {
            $table->dropIndex('idx_templates_company_active_date');
            $table->dropIndex('idx_templates_type_active');
        });

        Schema::table('replacement_requests', function (Blueprint $table) {
            $table->dropIndex('idx_replacements_status_urgency_date');
            $table->dropSoftDeletes();
        });

        Schema::table('replacement_bids', function (Blueprint $table) {
            $table->dropIndex('idx_bids_request_status_date');
        });

        Schema::table('shift_swaps', function (Blueprint $table) {
            $table->dropIndex('idx_swaps_status_approval');
            $table->dropSoftDeletes();
        });
    }
};
