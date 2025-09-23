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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->index(); // Nombre del log (auth, system, api, etc.)
            $table->text('description'); // Descripción de la actividad
            $table->string('subject_type')->nullable(); // Tipo del modelo (User, etc.)
            $table->unsignedBigInteger('subject_id')->nullable(); // ID del modelo
            $table->string('causer_type')->nullable(); // Tipo del causante
            $table->unsignedBigInteger('causer_id')->nullable(); // ID del causante
            $table->json('properties')->nullable(); // Propiedades adicionales
            $table->string('event')->nullable(); // Tipo de evento (created, updated, deleted, etc.)
            $table->string('level')->default('info'); // Nivel del log (debug, info, warning, error, critical)
            $table->string('ip_address', 45)->nullable(); // Dirección IP
            $table->string('user_agent')->nullable(); // User Agent
            $table->string('method')->nullable(); // Método HTTP (GET, POST, etc.)
            $table->string('url')->nullable(); // URL de la petición
            $table->integer('status_code')->nullable(); // Código de estado HTTP
            $table->integer('execution_time')->nullable(); // Tiempo de ejecución en ms
            $table->integer('memory_usage')->nullable(); // Uso de memoria en bytes
            $table->string('session_id')->nullable(); // ID de sesión
            $table->string('request_id')->nullable(); // ID único de la petición
            $table->timestamps();

            // Índices para optimización
            $table->index(['log_name', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
            $table->index(['level', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['method', 'status_code']);
            $table->index('request_id');
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
