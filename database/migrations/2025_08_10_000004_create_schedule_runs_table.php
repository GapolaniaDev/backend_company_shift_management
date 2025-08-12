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
        Schema::create('schedule_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('users')->onDelete('cascade');

            // Schedule generation period
            $table->date('period_start');
            $table->date('period_end');
            $table->string('period_name')->nullable(); // e.g., "Week 32 2025"

            // Generation status
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft');

            // Timestamps for workflow
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('published_by')->nullable()->constrained('users')->onDelete('set null');

            // Generation metadata
            $table->json('generation_summary')->nullable(); // stats: shifts created, templates used, exceptions applied
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['period_start', 'period_end']);
            $table->unique(['company_id', 'period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_runs');
    }
};
