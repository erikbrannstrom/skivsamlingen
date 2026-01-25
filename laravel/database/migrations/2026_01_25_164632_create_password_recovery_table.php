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
        if (Schema::hasTable("password_recovery")) {
            return;
        }

        Schema::create('password_recovery', function (Blueprint $table) {
            $table->string('username', 24)->primary();
            $table->string('hash', 40);
            $table->integer('created_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_recovery');
    }
};
