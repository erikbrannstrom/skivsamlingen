<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for the artists table.
 *
 * This migration matches the exact structure of the existing CodeIgniter database
 * to ensure compatibility during the migration period.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('artists', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->string('name', 64);

            $table->index('name');

            $table->engine = 'MyISAM';
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artists');
    }
};
