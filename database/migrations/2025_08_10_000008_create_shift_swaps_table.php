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
        Schema::create('shift_swaps', function (Blueprint $table) {
            $table->id();
            
            // Swap participants and their shifts
            $table->foreignId('requestor_assignment_id')->constrained('shift_assignments')->onDelete('cascade');
            $table->foreignId('target_assignment_id')->constrained('shift_assignments')->onDelete('cascade');
            
            // Swap status and type
            $table->enum('swap_type', ['direct', 'three_way', 'multiple'])->default('direct');
            $table->enum('status', ['proposed', 'pending_approval', 'approved', 'rejected', 'completed', 'cancelled'])->default('proposed');
            
            // Swap timeline
            $table->timestamp('proposed_at')->useCurrent();
            $table->timestamp('target_responded_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Approval workflow
            $table->foreignId('approved_by')->nullable()->constrained('employees')->onDelete('set null');
            $table->boolean('requires_approval')->default(true);
            
            // Swap terms and conditions
            $table->text('requestor_message')->nullable();
            $table->text('target_response')->nullable();
            $table->text('approval_notes')->nullable();
            $table->decimal('compensation_amount', 8, 2)->nullable(); // if one shift is "worth more"
            $table->json('swap_terms')->nullable(); // additional terms like partial swaps, conditions
            
            $table->timestamps();
            
            $table->index(['status', 'proposed_at']);
            $table->index('requestor_assignment_id');
            $table->index('target_assignment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_swaps');
    }
};