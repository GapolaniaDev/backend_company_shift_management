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
        Schema::create('shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');

            // Assignment status
            $table->enum('status', ['assigned', 'accepted', 'declined', 'completed', 'no_show'])->default('assigned');

            // Assignment type and priority
            $table->enum('assignment_type', ['primary', 'backup', 'replacement'])->default('primary');
            $table->integer('priority')->default(1); // for multiple assignments to same shift

            // Replacement tracking
            $table->foreignId('replaced_by_assignment_id')->nullable()->constrained('shift_assignments')->onDelete('set null');
            $table->foreignId('replaces_assignment_id')->nullable()->constrained('shift_assignments')->onDelete('set null');

            // Assignment timestamps
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Assignment metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // additional assignment-specific data

            $table->timestamps();

            $table->index(['shift_id', 'status']);
            $table->index(['employee_id', 'status']);
            $table->index(['assigned_at', 'status']);

            // Ensure no duplicate primary assignments for same shift
            $table->unique(['shift_id', 'employee_id', 'assignment_type'], 'unique_shift_employee_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_assignments');
    }
};
