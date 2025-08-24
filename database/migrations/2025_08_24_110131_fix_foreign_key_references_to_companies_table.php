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
        // Fix locations table foreign key
        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Fix shift_templates table foreign key
        Schema::table('shift_templates', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Fix schedule_runs table foreign key
        Schema::table('schedule_runs', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert locations table foreign key
        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Revert shift_templates table foreign key
        Schema::table('shift_templates', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Revert schedule_runs table foreign key
        Schema::table('schedule_runs', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
