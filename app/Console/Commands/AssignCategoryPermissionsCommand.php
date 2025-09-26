<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignCategoryPermissionsCommand extends Command
{
    protected $signature = 'assign:category-permissions';
    protected $description = 'Asigna permisos de categorías a los roles de administrador.';

    public function handle()
    {
        $this->info('🔐 Asignando permisos de categorías...');

        // Crear permisos si no existen
        $permissions = [
            'manage-categories',
            'manage-subcategories'
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
            $this->line("✅ Permiso creado/verificado: {$permissionName}");
        }

        // Asignar permisos a roles de administrador
        $superadminRole = Role::where('name', 'superadmin')->first();
        $adminRole = Role::where('name', 'admin')->first();

        if ($superadminRole) {
            $superadminRole->givePermissionTo($permissions);
            $this->line("✅ Permisos asignados a superadmin");
        }

        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
            $this->line("✅ Permisos asignados a admin");
        }

        $this->info('🎉 Permisos de categorías asignados exitosamente.');
        return 0;
    }
}
