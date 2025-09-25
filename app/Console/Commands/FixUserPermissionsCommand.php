<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class FixUserPermissionsCommand extends Command
{
    protected $signature = 'fix:user-permissions';
    protected $description = 'Fix user permissions and roles';

    public function handle()
    {
        $this->info('Verificando usuarios y permisos...');

        // Verificar usuarios existentes
        $users = User::all();
        $this->info('Usuarios encontrados: ' . $users->count());

        foreach ($users as $user) {
            $this->info("- Usuario: {$user->name} ({$user->email})");
            $this->info("  Roles: " . implode(', ', $user->roles->pluck('name')->toArray()));
            $this->info("  Permisos: " . implode(', ', $user->getAllPermissions()->pluck('name')->toArray()));
        }

        // Verificar si hay usuarios sin roles
        $usersWithoutRoles = User::doesntHave('roles')->get();
        if ($usersWithoutRoles->count() > 0) {
            $this->info('Usuarios sin roles encontrados: ' . $usersWithoutRoles->count());

            // Asignar rol superadmin a usuarios sin roles
            $superadminRole = Role::where('name', 'superadmin')->first();
            if ($superadminRole) {
                foreach ($usersWithoutRoles as $user) {
                    $user->assignRole($superadminRole);
                    $this->info("  Asignado rol superadmin a: {$user->name}");
                }
            }
        }

        $this->info('Verificación completada.');
        return 0;
    }
}
