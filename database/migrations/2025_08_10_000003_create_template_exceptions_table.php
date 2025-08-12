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
        Schema::create('template_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_template_id')->constrained('shift_templates')->onDelete('cascade');

            // Exception date
            $table->date('exception_date');

            // Exception type
            $table->enum('exception_type', ['cancel', 'modify', 'add']);

            // Modified shift details (for 'modify' and 'add' types)
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('hours', 5, 2)->nullable();
            $table->integer('capacity')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null');

            // Reason for exception
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['shift_template_id', 'exception_date']);
            $table->unique(['shift_template_id', 'exception_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_exceptions');
    }
};
