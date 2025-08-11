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
        Schema::create('replacement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_assignment_id')->constrained('shift_assignments')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('employees')->onDelete('cascade');
            
            // Request details
            $table->enum('request_type', ['find_replacement', 'call_out', 'swap_request'])->default('find_replacement');
            $table->enum('status', ['pending', 'approved', 'rejected', 'fulfilled', 'cancelled'])->default('pending');
            $table->enum('urgency', ['low', 'medium', 'high', 'emergency'])->default('medium');
            
            // Request reason and timeline
            $table->text('reason');
            $table->timestamp('needed_by')->nullable(); // deadline for finding replacement
            $table->timestamp('requested_at')->useCurrent();
            
            // Approval workflow
            $table->foreignId('reviewed_by')->nullable()->constrained('employees')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            
            // Fulfillment tracking
            $table->foreignId('fulfilled_by_assignment_id')->nullable()->constrained('shift_assignments')->onDelete('set null');
            $table->timestamp('fulfilled_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['status', 'urgency']);
            $table->index(['requested_at', 'status']);
            $table->index('shift_assignment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replacement_requests');
    }
};