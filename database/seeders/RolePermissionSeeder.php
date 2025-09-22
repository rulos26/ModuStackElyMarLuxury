<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles
        $superadminRole = Role::create(['name' => 'superadmin']);
        $adminRole = Role::create(['name' => 'admin']);

        // Crear permisos básicos
        $permissions = [
            'view-dashboard',
            'manage-users',
            'manage-roles',
            'manage-permissions',
            'view-reports',
            'manage-settings'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Asignar todos los permisos al superadmin
        $superadminRole->givePermissionTo(Permission::all());

        // Asignar permisos específicos al admin
        $adminRole->givePermissionTo([
            'view-dashboard',
            'view-reports',
            'manage-users'
        ]);

        // Crear usuario root (superadmin)
        $rootUser = User::create([
            'name' => 'root',
            'email' => 'root@admin.com',
            'password' => Hash::make('root'),
            'email_verified_at' => now(),
        ]);
        $rootUser->assignRole('superadmin');

        // Crear usuario admin
        $adminUser = User::create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin'),
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('admin');

        $this->command->info('Roles, permisos y usuarios creados exitosamente:');
        $this->command->info('- Usuario root (superadmin): root@admin.com / root');
        $this->command->info('- Usuario admin: admin@admin.com / admin');
    }
}
