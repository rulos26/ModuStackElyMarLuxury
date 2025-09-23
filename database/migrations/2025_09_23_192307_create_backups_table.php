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
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del backup
            $table->string('type')->default('full'); // full, database, files, incremental
            $table->string('status')->default('pending'); // pending, in_progress, completed, failed
            $table->string('storage_type')->default('local'); // local, s3, ftp, etc.
            $table->string('file_path')->nullable(); // Ruta del archivo de backup
            $table->string('file_name'); // Nombre del archivo
            $table->bigInteger('file_size')->nullable(); // Tamaño en bytes
            $table->string('file_hash')->nullable(); // Hash del archivo para verificación
            $table->json('options')->nullable(); // Opciones de configuración del backup
            $table->text('description')->nullable(); // Descripción del backup
            $table->text('error_message')->nullable(); // Mensaje de error si falla
            $table->json('metadata')->nullable(); // Metadatos adicionales
            $table->timestamp('started_at')->nullable(); // Cuándo comenzó
            $table->timestamp('completed_at')->nullable(); // Cuándo terminó
            $table->integer('execution_time')->nullable(); // Tiempo de ejecución en segundos
            $table->boolean('is_encrypted')->default(false); // Si está encriptado
            $table->boolean('is_compressed')->default(true); // Si está comprimido
            $table->integer('retention_days')->default(30); // Días de retención
            $table->timestamp('expires_at')->nullable(); // Cuándo expira
            $table->unsignedBigInteger('created_by')->nullable(); // Quien creó el backup
            $table->timestamps();

            // Índices para optimización
            $table->index(['type', 'status']);
            $table->index(['storage_type', 'status']);
            $table->index(['created_at', 'status']);
            $table->index(['expires_at']);
            $table->index('file_hash');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
