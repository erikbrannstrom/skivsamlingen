<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for the users table.
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
        Schema::create('users', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('username', 24)->unique();
            $table->char('password', 64);
            $table->string('name', 50)->nullable();
            $table->string('email', 64)->nullable();
            $table->boolean('public_email')->default(false);
            $table->enum('sex', ['m', 'f', 'x'])->default('x');
            $table->date('birth')->nullable();
            $table->text('about')->nullable();
            $table->smallInteger('per_page')->default(100);
            // Unused: user permission level (1=normal user, higher=admin)
            $table->tinyInteger('level')->default(1);
            $table->timestamp('registered')->useCurrent();
            // Unused: timestamp of last Discogs import
            $table->integer('last_import')->nullable();

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
        Schema::dropIfExists('users');
    }
};
