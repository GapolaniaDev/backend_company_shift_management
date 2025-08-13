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
        Schema::create('replacement_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('replacement_request_id')->constrained('replacement_requests')->onDelete('cascade');
            $table->foreignId('bidder_employee_id')->constrained('employees')->onDelete('cascade');

            // Bid details
            $table->enum('bid_status', ['pending', 'accepted', 'rejected', 'withdrawn'])->default('pending');
            $table->decimal('bid_amount', 8, 2)->nullable(); // optional extra pay offered/requested
            $table->text('message')->nullable(); // optional message from bidder

            // Bid lifecycle
            $table->timestamp('bid_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('employees')->onDelete('set null');
            $table->text('response_notes')->nullable();

            // Priority/ranking
            $table->integer('priority_rank')->nullable(); // can be set by manager for ranking bids

            $table->timestamps();

            $table->index(['replacement_request_id', 'bid_status']);
            $table->index(['bidder_employee_id', 'bid_status']);
            $table->unique(['replacement_request_id', 'bidder_employee_id'], 'replacement_bids_request_employee_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replacement_bids');
    }
};
