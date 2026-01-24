<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for the records table.
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
        Schema::create('records', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->unsignedMediumInteger('artist_id');
            // Title uses utf8_bin collation for case-sensitive matching
            $table->string('title', 150)->collation('utf8_bin');
            $table->year('year')->nullable();
            $table->string('format', 30)->nullable();

            $table->index('artist_id');
            $table->index('title');
            $table->index('year');
            $table->index('format');

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
        Schema::dropIfExists('records');
    }
};
