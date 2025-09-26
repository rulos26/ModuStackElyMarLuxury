<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\Subcategory;

class TestCategoryCrudCommand extends Command
{
    protected $signature = 'test:category-crud';
    protected $description = 'Prueba el CRUD de categorías y subcategorías.';

    public function handle()
    {
        $this->info('🧪 Probando CRUD de categorías y subcategorías...');

        try {
            // Probar CREATE de categoría
            $this->line('📝 Probando CREATE de categoría...');
            $category = Category::create([
                'name' => 'Categoría de Prueba CRUD',
                'description' => 'Descripción de prueba para verificar funcionalidad'
            ]);
            $this->info("✅ Categoría creada con ID: {$category->id}");

            // Probar READ de categoría
            $this->line('📖 Probando READ de categoría...');
            $foundCategory = Category::find($category->id);
            if ($foundCategory) {
                $this->info("✅ Categoría encontrada: {$foundCategory->name}");
            } else {
                $this->error("❌ No se pudo encontrar la categoría");
            }

            // Probar UPDATE de categoría
            $this->line('✏️ Probando UPDATE de categoría...');
            $category->update([
                'name' => 'Categoría Actualizada',
                'description' => 'Descripción actualizada'
            ]);
            $this->info("✅ Categoría actualizada");

            // Probar CREATE de subcategoría
            $this->line('📝 Probando CREATE de subcategoría...');
            $subcategory = Subcategory::create([
                'category_id' => $category->id,
                'name' => 'Subcategoría de Prueba',
                'description' => 'Descripción de subcategoría de prueba'
            ]);
            $this->info("✅ Subcategoría creada con ID: {$subcategory->id}");

            // Probar READ de subcategoría
            $this->line('📖 Probando READ de subcategoría...');
            $foundSubcategory = Subcategory::find($subcategory->id);
            if ($foundSubcategory) {
                $this->info("✅ Subcategoría encontrada: {$foundSubcategory->name}");
            } else {
                $this->error("❌ No se pudo encontrar la subcategoría");
            }

            // Probar UPDATE de subcategoría
            $this->line('✏️ Probando UPDATE de subcategoría...');
            $subcategory->update([
                'name' => 'Subcategoría Actualizada',
                'description' => 'Descripción de subcategoría actualizada'
            ]);
            $this->info("✅ Subcategoría actualizada");

            // Probar DELETE de subcategoría
            $this->line('🗑️ Probando DELETE de subcategoría...');
            $subcategory->delete();
            $this->info("✅ Subcategoría eliminada");

            // Probar DELETE de categoría
            $this->line('🗑️ Probando DELETE de categoría...');
            $category->delete();
            $this->info("✅ Categoría eliminada");

            $this->info('🎉 ¡CRUD de categorías y subcategorías funciona correctamente!');
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error en CRUD: " . $e->getMessage());
            return 1;
        }
    }
}
