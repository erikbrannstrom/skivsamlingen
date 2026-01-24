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
        Schema::create('persistent_logins', function (Blueprint $table) {
            $table->smallInteger('user_id');
            $table->char('series', 40);
            $table->unsignedInteger('token');
            $table->primary(['user_id', 'series', 'token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persistent_logins');
    }
};
