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
            // Add new fields for refactored system
            $table->foreignId('shift_template_id')->nullable()->constrained('shift_templates')->onDelete('set null')->after('shift_type_id');
            $table->foreignId('schedule_run_id')->nullable()->constrained('schedule_runs')->onDelete('set null')->after('shift_template_id');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null')->after('schedule_run_id');
            
            // Make employee_id nullable (assignments will be in shift_assignments table)
            $table->foreignId('employee_id')->nullable()->change();
            
            // Add capacity support
            $table->integer('capacity')->default(1)->after('total_hours');
            
            // Add shift status
            $table->enum('shift_status', ['draft', 'published', 'cancelled'])->default('published')->after('capacity');
            
            // Keep existing location fields for backward compatibility but mark as deprecated
            // These will be removed in a future migration once data is fully migrated to locations table
        });
        
        // Add indexes for new fields
        Schema::table('shifts', function (Blueprint $table) {
            $table->index('shift_template_id');
            $table->index('schedule_run_id');
            $table->index('location_id');
            $table->index(['date_start', 'date_end']);
            $table->index('shift_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Remove indexes first
            $table->dropIndex(['shifts_shift_template_id_index']);
            $table->dropIndex(['shifts_schedule_run_id_index']);
            $table->dropIndex(['shifts_location_id_index']);
            $table->dropIndex(['shifts_date_start_date_end_index']);
            $table->dropIndex(['shifts_shift_status_index']);
            
            // Remove new columns
            $table->dropForeign(['shift_template_id']);
            $table->dropForeign(['schedule_run_id']);
            $table->dropForeign(['location_id']);
            
            $table->dropColumn([
                'shift_template_id',
                'schedule_run_id', 
                'location_id',
                'capacity',
                'shift_status'
            ]);
            
            // Restore employee_id as required
            $table->foreignId('employee_id')->nullable(false)->change();
        });
    }
};