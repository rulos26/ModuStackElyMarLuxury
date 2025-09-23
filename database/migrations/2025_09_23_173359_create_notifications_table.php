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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info'); // info, success, warning, error
            $table->string('icon')->nullable(); // Icono para la notificación
            $table->string('url')->nullable(); // URL de acción
            $table->string('action_text')->nullable(); // Texto del botón de acción
            $table->json('data')->nullable(); // Datos adicionales
            $table->boolean('is_read')->default(false);
            $table->boolean('is_push_sent')->default(false); // Si se envió push
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Expiración de notificación
            $table->unsignedBigInteger('user_id')->nullable(); // Usuario específico (null = todos)
            $table->unsignedBigInteger('created_by')->nullable(); // Quien creó la notificación
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['type', 'is_push_sent']);
            $table->index('expires_at');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
