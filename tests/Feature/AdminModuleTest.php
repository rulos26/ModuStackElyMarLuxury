<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /**
     * Test que verifica que las rutas de administración requieren autenticación
     */
    public function test_admin_routes_require_authentication()
    {
        $routes = [
            'admin/users',
            'admin/roles',
            'admin/permissions'
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }

    /**
     * Test que verifica que las rutas de administración requieren permisos específicos
     */
    public function test_admin_routes_require_specific_permissions()
    {
        // Crear usuario sin permisos
        $user = User::factory()->create();

        $this->actingAs($user);

        // Test usuarios - requiere manage-users
        $response = $this->get('/admin/users');
        $response->assertStatus(403);

        // Test roles - requiere manage-roles
        $response = $this->get('/admin/roles');
        $response->assertStatus(403);

        // Test permisos - requiere manage-permissions
        $response = $this->get('/admin/permissions');
        $response->assertStatus(403);
    }

    /**
     * Test que verifica que el superadmin puede acceder a todas las rutas
     */
    public function test_superadmin_can_access_all_admin_routes()
    {
        $user = User::where('email', 'root@admin.com')->first();
        $this->actingAs($user);

        $routes = [
            'admin/users',
            'admin/roles',
            'admin/permissions'
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);
        }
    }

    /**
     * Test que verifica que se pueden crear usuarios desde el admin
     */
    public function test_can_create_user_from_admin()
    {
        $user = User::where('email', 'root@admin.com')->first();
        $this->actingAs($user);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => [2] // admin role
        ];

        $response = $this->post('/admin/users', $userData);
        $response->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
    }

    /**
     * Test que verifica que se pueden crear roles desde el admin
     */
    public function test_can_create_role_from_admin()
    {
        $user = User::where('email', 'root@admin.com')->first();
        $this->actingAs($user);

        $roleData = [
            'name' => 'test-role',
            'permissions' => [1, 2] // algunos permisos
        ];

        $response = $this->post('/admin/roles', $roleData);
        $response->assertRedirect('/admin/roles');

        $this->assertDatabaseHas('roles', [
            'name' => 'test-role'
        ]);
    }

    /**
     * Test que verifica que se pueden crear permisos desde el admin
     */
    public function test_can_create_permission_from_admin()
    {
        $user = User::where('email', 'root@admin.com')->first();
        $this->actingAs($user);

        $permissionData = [
            'name' => 'test-permission'
        ];

        $response = $this->post('/admin/permissions', $permissionData);
        $response->assertRedirect('/admin/permissions');

        $this->assertDatabaseHas('permissions', [
            'name' => 'test-permission'
        ]);
    }

    /**
     * Test que verifica que no se puede eliminar el usuario root
     */
    public function test_cannot_delete_root_user()
    {
        $user = User::where('email', 'root@admin.com')->first();
        $this->actingAs($user);

        $rootUser = User::where('email', 'root@admin.com')->first();

        $response = $this->delete("/admin/users/{$rootUser->id}");
        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('error', 'No se puede eliminar el usuario root.');

        $this->assertDatabaseHas('users', [
            'email' => 'root@admin.com'
        ]);
    }

    /**
     * Test que verifica que no se pueden eliminar roles del sistema
     */
    public function test_cannot_delete_system_roles()
    {
        $user = User::where('email', 'root@admin.com')->first();
        $this->actingAs($user);

        $superadminRole = Role::where('name', 'superadmin')->first();

        $response = $this->delete("/admin/roles/{$superadminRole->id}");
        $response->assertRedirect('/admin/roles');
        $response->assertSessionHas('error', 'No se puede eliminar este rol del sistema.');

        $this->assertDatabaseHas('roles', [
            'name' => 'superadmin'
        ]);
    }

    /**
     * Test que verifica que no se pueden eliminar permisos del sistema
     */
    public function test_cannot_delete_system_permissions()
    {
        $user = User::where('email', 'root@admin.com')->first();
        $this->actingAs($user);

        $permission = Permission::where('name', 'view-dashboard')->first();

        $response = $this->delete("/admin/permissions/{$permission->id}");
        $response->assertRedirect('/admin/permissions');
        $response->assertSessionHas('error', 'No se puede eliminar este permiso del sistema.');

        $this->assertDatabaseHas('permissions', [
            'name' => 'view-dashboard'
        ]);
    }

    /**
     * Test que verifica que las vistas usan el extends correcto
     */
    public function test_admin_views_use_correct_extends()
    {
        $adminViews = [
            'admin/users/index.blade.php',
            'admin/users/create.blade.php',
            'admin/users/edit.blade.php',
            'admin/users/show.blade.php',
            'admin/roles/index.blade.php',
            'admin/roles/create.blade.php',
            'admin/roles/edit.blade.php',
            'admin/roles/show.blade.php',
            'admin/permissions/index.blade.php',
            'admin/permissions/create.blade.php',
            'admin/permissions/edit.blade.php',
            'admin/permissions/show.blade.php'
        ];

        foreach ($adminViews as $view) {
            $viewPath = resource_path('views/' . $view);
            $this->assertTrue(file_exists($viewPath), "La vista {$view} no existe");

            $content = file_get_contents($viewPath);
            $this->assertStringContainsString("@extends('adminlte::page')", $content,
                "La vista {$view} no usa @extends('adminlte::page')");
        }
    }
}
