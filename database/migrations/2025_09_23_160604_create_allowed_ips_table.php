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
        Schema::create('allowed_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45); // IPv6 compatible
            $table->enum('type', ['specific', 'cidr', 'blocked'])->default('specific');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['ip_address', 'status']);
            $table->index(['type', 'status']);
            $table->index('expires_at');
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
        Schema::dropIfExists('allowed_ips');
    }
};
