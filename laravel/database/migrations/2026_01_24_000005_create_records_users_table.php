<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for the records_users junction table.
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
        Schema::create('records_users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('user_id')->nullable();
            $table->unsignedMediumInteger('record_id')->default(0);
            $table->tinyText('comment')->nullable();

            $table->index('user_id');
            $table->index('record_id');

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
        Schema::dropIfExists('records_users');
    }
};
