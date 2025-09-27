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
        Schema::table('pieces', function (Blueprint $table) {
            // Índices para mejorar el rendimiento de consultas
            $table->index('category_id');           // Búsquedas por categoría
            $table->index('subcategory_id');        // Búsquedas por subcategoría
            $table->index('status');                // Filtros por estado
            $table->index(['category_id', 'status']); // Consultas combinadas
            $table->index('created_at');            // Ordenamiento por fecha
            $table->index('sale_price');            // Consultas por precio
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pieces', function (Blueprint $table) {
            // Eliminar índices agregados
            $table->dropIndex(['category_id']);
            $table->dropIndex(['subcategory_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['category_id', 'status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['sale_price']);
        });
    }
};
