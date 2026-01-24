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
        Schema::create('ci_sessions', function (Blueprint $table) {
            $table->string('session_id', 40)->primary();
            $table->string('ip_address', 45)->default('0');
            $table->string('user_agent', 120)->nullable();
            $table->unsignedInteger('last_activity')->default(0)->index();
            $table->text('user_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ci_sessions');
    }
};
