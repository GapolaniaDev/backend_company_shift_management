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
        // Make company_id NOT NULL and add foreign key constraints
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::table('shift_types', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::table('shift_configurations', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::table('pay_periods', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key constraints and make company_id nullable
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreignId('company_id')->nullable()->change();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreignId('company_id')->nullable()->change();
        });

        Schema::table('shift_types', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreignId('company_id')->nullable()->change();
        });

        Schema::table('shift_configurations', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreignId('company_id')->nullable()->change();
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreignId('company_id')->nullable()->change();
        });

        Schema::table('pay_periods', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreignId('company_id')->nullable()->change();
        });
    }
};
