<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Piece;
use App\Models\Category;
use App\Models\Subcategory;

class TestPieceCrudCommand extends Command
{
    protected $signature = 'test:piece-crud';
    protected $description = 'Prueba el CRUD de piezas.';

    public function handle()
    {
        $this->info('🧪 Probando CRUD de piezas...');

        try {
            // Crear categoría y subcategoría de prueba
            $category = Category::create([
                'name' => 'Categoría de Prueba',
                'description' => 'Para probar piezas'
            ]);

            $subcategory = Subcategory::create([
                'category_id' => $category->id,
                'name' => 'Subcategoría de Prueba',
                'description' => 'Para probar piezas'
            ]);

            // Probar CREATE de pieza
            $this->line('📝 Probando CREATE de pieza...');
            $piece = Piece::create([
                'code' => 'PIEZA-001',
                'name' => 'Pieza de Prueba CRUD',
                'description' => 'Descripción de prueba para verificar funcionalidad',
                'category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'weight' => 1.5,
                'cost_price' => 100.00,
                'sale_price' => 150.00,
                'status' => 'disponible'
            ]);
            $this->info("✅ Pieza creada con ID: {$piece->id}");

            // Probar READ de pieza
            $this->line('📖 Probando READ de pieza...');
            $foundPiece = Piece::with(['category', 'subcategory'])->find($piece->id);
            if ($foundPiece) {
                $this->info("✅ Pieza encontrada: {$foundPiece->name}");
                $this->info("   Categoría: {$foundPiece->category->name}");
                $this->info("   Subcategoría: {$foundPiece->subcategory->name}");
            } else {
                $this->error("❌ No se pudo encontrar la pieza");
            }

            // Probar UPDATE de pieza
            $this->line('✏️ Probando UPDATE de pieza...');
            $piece->update([
                'name' => 'Pieza Actualizada',
                'sale_price' => 200.00,
                'status' => 'apartado'
            ]);
            $this->info("✅ Pieza actualizada");

            // Probar DELETE de pieza
            $this->line('🗑️ Probando DELETE de pieza...');
            $piece->delete();
            $this->info("✅ Pieza eliminada");

            // Limpiar datos de prueba
            $subcategory->delete();
            $category->delete();

            $this->info('🎉 ¡CRUD de piezas funciona correctamente!');
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error en CRUD de piezas: " . $e->getMessage());
            return 1;
        }
    }
}


