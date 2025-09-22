<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /**
     * Test que verifica que los roles se crean correctamente
     */
    public function test_roles_are_created_successfully()
    {
        // Verificar que los roles existen
        $this->assertTrue(Role::where('name', 'superadmin')->exists());
        $this->assertTrue(Role::where('name', 'admin')->exists());
    }

    /**
     * Test que verifica que los permisos se crean correctamente
     */
    public function test_permissions_are_created_successfully()
    {
        $expectedPermissions = [
            'view-dashboard',
            'manage-users',
            'manage-roles',
            'manage-permissions',
            'view-reports',
            'manage-settings'
        ];

        foreach ($expectedPermissions as $permission) {
            $this->assertTrue(Permission::where('name', $permission)->exists());
        }
    }

    /**
     * Test que verifica que el usuario root tiene el rol superadmin
     */
    public function test_root_user_has_superadmin_role()
    {
        $user = User::where('email', 'root@admin.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('superadmin'));
    }

    /**
     * Test que verifica que el usuario admin tiene el rol admin
     */
    public function test_admin_user_has_admin_role()
    {
        $user = User::where('email', 'admin@admin.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('admin'));
    }

    /**
     * Test que verifica que el superadmin tiene todos los permisos
     */
    public function test_superadmin_has_all_permissions()
    {
        $user = User::where('email', 'root@admin.com')->first();
        $permissions = Permission::all();

        foreach ($permissions as $permission) {
            $this->assertTrue($user->can($permission->name));
        }
    }

    /**
     * Test que verifica que el admin tiene permisos limitados
     */
    public function test_admin_has_limited_permissions()
    {
        $user = User::where('email', 'admin@admin.com')->first();

        // Debe tener estos permisos
        $this->assertTrue($user->can('view-dashboard'));
        $this->assertTrue($user->can('view-reports'));
        $this->assertTrue($user->can('manage-users'));

        // No debe tener estos permisos
        $this->assertFalse($user->can('manage-roles'));
        $this->assertFalse($user->can('manage-permissions'));
        $this->assertFalse($user->can('manage-settings'));
    }

    /**
     * Test que verifica que los usuarios pueden hacer login
     */
    public function test_users_can_login()
    {
        // Test login del usuario root
        $response = $this->post('/login', [
            'email' => 'root@admin.com',
            'password' => 'root',
        ]);

        $response->assertRedirect('/home');

        // Test login del usuario admin
        $response = $this->post('/login', [
            'email' => 'admin@admin.com',
            'password' => 'admin',
        ]);

        $response->assertRedirect('/home');
    }

    /**
     * Test que verifica que las credenciales incorrectas fallan
     */
    public function test_invalid_credentials_fail()
    {
        $response = $this->post('/login', [
            'email' => 'root@admin.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * Test que verifica que la ruta raíz redirige al login
     */
    public function test_root_route_redirects_to_login()
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    /**
     * Test que verifica que las rutas protegidas requieren autenticación
     */
    public function test_protected_routes_require_authentication()
    {
        $response = $this->get('/home');
        $response->assertRedirect('/login');
    }
}
