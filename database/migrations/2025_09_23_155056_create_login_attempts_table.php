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
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45); // IPv6 compatible
            $table->string('email', 255)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('attempted_at');
            $table->boolean('success')->default(false);
            $table->string('reason', 255)->nullable(); // Razón del fallo
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['ip_address', 'attempted_at']);
            $table->index(['email', 'attempted_at']);
            $table->index('attempted_at');
            $table->index('success');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
