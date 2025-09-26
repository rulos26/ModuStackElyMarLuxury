<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ListUsersCommand extends Command
{
    protected $signature = 'users:list';
    protected $description = 'List all registered users';

    public function handle()
    {
        $this->info('👥 USUARIOS REGISTRADOS EN EL SISTEMA');
        $this->info('=====================================');

        $users = User::all(['id', 'name', 'email', 'email_verified_at', 'created_at']);

        if ($users->isEmpty()) {
            $this->warn('No hay usuarios registrados en el sistema.');
            return;
        }

        $table = [];
        foreach ($users as $user) {
            $table[] = [
                $user->id,
                $user->name,
                $user->email,
                $user->email_verified_at ? '✅ Verificado' : '❌ No verificado',
                $user->created_at->format('d/m/Y H:i:s')
            ];
        }

        $this->table(['ID', 'Nombre', 'Email', 'Estado', 'Fecha Registro'], $table);

        $this->info("\n📊 Resumen:");
        $this->info("Total usuarios: " . $users->count());
        $this->info("Usuarios verificados: " . $users->where('email_verified_at', '!=', null)->count());
        $this->info("Usuarios sin verificar: " . $users->where('email_verified_at', null)->count());
    }
}



