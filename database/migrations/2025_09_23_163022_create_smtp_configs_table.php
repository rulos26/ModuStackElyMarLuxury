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
        Schema::create('smtp_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('mailer')->default('smtp');
            $table->string('host')->nullable();
            $table->integer('port')->nullable();
            $table->string('encryption')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('timeout')->nullable();
            $table->string('local_domain')->nullable();
            $table->string('from_address');
            $table->string('from_name');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['is_active', 'is_default']);
            $table->index('name');
            $table->index('created_by');

            // Clave foránea
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smtp_configs');
    }
};
