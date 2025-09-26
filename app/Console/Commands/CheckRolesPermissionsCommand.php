<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class CheckRolesPermissionsCommand extends Command
{
    protected $signature = 'check:roles-permissions';
    protected $description = 'Revisa el estado de roles y permisos del sistema.';

    public function handle()
    {
        $this->info('🔐 Revisando roles y permisos del sistema...');
        $this->line('');

        // Revisar roles
        $this->info('📋 ROLES:');
        $roles = Role::with('permissions')->get();

        if ($roles->isEmpty()) {
            $this->warn('⚠️ No se encontraron roles');
        } else {
            foreach ($roles as $role) {
                $permissionCount = $role->permissions->count();
                $this->line("✅ {$role->name} - {$permissionCount} permisos");

                if ($permissionCount > 0) {
                    $permissions = $role->permissions->pluck('name')->toArray();
                    $this->line("   Permisos: " . implode(', ', $permissions));
                }
            }
        }

        $this->line('');

        // Revisar permisos
        $this->info('🔑 PERMISOS:');
        $permissions = Permission::all();

        if ($permissions->isEmpty()) {
            $this->warn('⚠️ No se encontraron permisos');
        } else {
            foreach ($permissions as $permission) {
                $this->line("✅ {$permission->name}");
            }
        }

        $this->line('');

        // Revisar usuarios y sus roles
        $this->info('👥 USUARIOS Y SUS ROLES:');
        $users = User::with('roles')->get();

        if ($users->isEmpty()) {
            $this->warn('⚠️ No se encontraron usuarios');
        } else {
            foreach ($users as $user) {
                $roles = $user->getRoleNames()->toArray();
                $allPermissions = $user->getAllPermissions()->pluck('name')->toArray();

                $this->line("👤 {$user->name} ({$user->email})");
                $this->line("   Roles: " . (empty($roles) ? 'Sin roles' : implode(', ', $roles)));
                $this->line("   Permisos: " . (empty($allPermissions) ? 'Sin permisos' : implode(', ', $allPermissions)));
                $this->line('');
            }
        }

        // Verificar permisos específicos para categorías
        $this->info('🎯 VERIFICACIÓN ESPECÍFICA:');

        $categoryPermissions = ['manage-categories', 'manage-subcategories'];
        foreach ($categoryPermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission) {
                $this->line("✅ Permiso '{$permissionName}' existe");
            } else {
                $this->error("❌ Permiso '{$permissionName}' NO existe");
            }
        }

        $this->line('');
        $this->info('🎉 Revisión completada.');
        return 0;
    }
}


