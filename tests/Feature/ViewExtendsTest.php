<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\File;

class ViewExtendsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que verifica que las vistas usan @extends('layouts.app')
     */
    public function test_views_use_correct_extends()
    {
        $viewsPath = resource_path('views');
        $bladeFiles = $this->getBladeFiles($viewsPath);

        $incorrectExtends = [];

        foreach ($bladeFiles as $file) {
            $content = File::get($file);

            // Verificar que no use @extends('dashboard.app')
            if (strpos($content, "@extends('dashboard.app')") !== false) {
                $incorrectExtends[] = $file;
            }
        }

        $this->assertEmpty($incorrectExtends,
            'Las siguientes vistas usan @extends(\'dashboard.app\') en lugar de @extends(\'layouts.app\'): ' .
            implode(', ', $incorrectExtends)
        );
    }

    /**
     * Test que verifica que las vistas principales existen
     */
    public function test_main_views_exist()
    {
        $requiredViews = [
            'home.blade.php',
            'welcome.blade.php',
            'layouts/app.blade.php'
        ];

        foreach ($requiredViews as $view) {
            $viewPath = resource_path('views/' . $view);
            $this->assertTrue(File::exists($viewPath), "La vista {$view} no existe");
        }
    }

    /**
     * Test que verifica que las vistas de autenticación existen
     */
    public function test_auth_views_exist()
    {
        $authViewsPath = resource_path('views/auth');
        $this->assertTrue(File::exists($authViewsPath), 'La carpeta de vistas de autenticación no existe');

        $authViews = [
            'login.blade.php',
            'register.blade.php',
            'passwords/email.blade.php',
            'passwords/reset.blade.php',
            'passwords/confirm.blade.php'
        ];

        foreach ($authViews as $view) {
            $viewPath = resource_path('views/auth/' . $view);
            $this->assertTrue(File::exists($viewPath), "La vista de autenticación {$view} no existe");
        }
    }

    /**
     * Test que verifica que las vistas de AdminLTE existen
     */
    public function test_adminlte_views_exist()
    {
        $adminlteViewsPath = resource_path('views/vendor/adminlte');
        $this->assertTrue(File::exists($adminlteViewsPath), 'La carpeta de vistas de AdminLTE no existe');

        $adminlteViews = [
            'master.blade.php',
            'auth/login.blade.php',
            'auth/register.blade.php',
            'partials/common/preloader.blade.php',
            'partials/common/brand-logo-xs.blade.php',
            'partials/common/brand-logo-xl.blade.php'
        ];

        foreach ($adminlteViews as $view) {
            $viewPath = resource_path('views/vendor/adminlte/' . $view);
            $this->assertTrue(File::exists($viewPath), "La vista de AdminLTE {$view} no existe");
        }
    }

    /**
     * Obtiene todos los archivos .blade.php de un directorio recursivamente
     */
    private function getBladeFiles($directory)
    {
        $files = [];

        if (File::exists($directory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php' &&
                    strpos($file->getFilename(), '.blade.php') !== false) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
