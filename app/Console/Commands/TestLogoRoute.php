<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class TestLogoRoute extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logo:test-route';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar la ruta del logo y verificar acceso';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Probando rutas de logo...');

        // Verificar archivos
        $storagePath = storage_path('app/public/logos/app-logo.svg');
        $publicPath = public_path('logos/app-logo.svg');

        $this->info('📁 Archivos:');
        $this->info('- Storage: ' . ($storagePath . ' - ' . (file_exists($storagePath) ? '✅ Existe' : '❌ No existe')));
        $this->info('- Public: ' . ($publicPath . ' - ' . (file_exists($publicPath) ? '✅ Existe' : '❌ No existe')));

        // Verificar configuración
        $logoConfig = config('adminlte.logo_img');
        $this->info('⚙️ Configuración AdminLTE:');
        $this->info('- logo_img: ' . $logoConfig);

        // Generar URLs
        $assetUrl = asset('/storage/logos/app-logo.svg');
        $this->info('🌐 URLs generadas:');
        $this->info('- asset(): ' . $assetUrl);

        // Probar acceso directo
        $this->info('🔗 Probando acceso directo...');

        // Simular petición HTTP
        try {
            $response = \Illuminate\Support\Facades\Http::get($assetUrl);
            $this->info('- Status: ' . $response->status());
            if ($response->successful()) {
                $this->info('- Content-Type: ' . $response->header('Content-Type'));
                $this->info('- Size: ' . strlen($response->body()) . ' bytes');
                $this->info('✅ Logo accesible vía HTTP');
            } else {
                $this->error('❌ Logo no accesible vía HTTP');
            }
        } catch (\Exception $e) {
            $this->error('❌ Error al acceder: ' . $e->getMessage());
        }

        // Verificar rutas registradas
        $this->info('🛣️ Rutas registradas:');
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            return str_contains($route->uri(), 'storage');
        });

        foreach ($routes as $route) {
            $this->info('- ' . $route->methods()[0] . ' ' . $route->uri());
        }

        $this->info('🎉 Prueba completada');
    }
}
