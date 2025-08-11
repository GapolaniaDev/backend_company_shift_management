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
        Schema::create('shift_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shift_type_id')->constrained('shift_types')->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null');
            
            // Template basic info
            $table->string('name');
            $table->text('description')->nullable();
            
            // Recurrence rule (RRULE format) 
            $table->text('rrule'); // e.g., "FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR"
            
            // Time configuration
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('hours', 5, 2); // calculated duration
            
            // Capacity and assignment
            $table->integer('capacity')->default(1); // how many people can work this shift
            $table->boolean('auto_assign')->default(false); // auto-assign based on shift_configurations
            
            // Template lifecycle
            $table->date('effective_from'); // when template becomes active
            $table->date('effective_to')->nullable(); // when template expires
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->json('metadata')->nullable(); // extra configuration like break times, special requirements
            
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
            $table->index(['effective_from', 'effective_to']);
            $table->index('shift_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_templates');
    }
};