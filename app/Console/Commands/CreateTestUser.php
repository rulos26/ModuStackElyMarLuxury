<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear usuario de prueba para testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('👤 Creando usuario de prueba...');

        try {
            // Verificar si ya existe
            $existingUser = User::where('email', 'test@example.com')->first();

            if ($existingUser) {
                $this->info("✅ Usuario de prueba ya existe: ID {$existingUser->id}");
                $this->line("   Nombre: {$existingUser->name}");
                $this->line("   Email: {$existingUser->email}");
                return 0;
            }

            // Crear usuario
            $user = User::create([
                'name' => 'Usuario de Prueba',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

            $this->info("✅ Usuario de prueba creado exitosamente:");
            $this->line("   ID: {$user->id}");
            $this->line("   Nombre: {$user->name}");
            $this->line("   Email: {$user->email}");
            $this->line("   Contraseña: password");

        } catch (\Exception $e) {
            $this->error('❌ Error creando usuario: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
