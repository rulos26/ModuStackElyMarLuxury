<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SmtpConfig;

class VerifySmtpCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smtp:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar credenciales SMTP configuradas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando credenciales SMTP...');

        try {
            // Obtener configuración por defecto
            $config = SmtpConfig::getActiveDefault();

            if (!$config) {
                $this->error('❌ No hay configuración SMTP activa por defecto');
                return 1;
            }

            $this->line('');
            $this->line('📧 Configuración encontrada:');
            $this->line('   ID: ' . $config->id);
            $this->line('   Nombre: ' . $config->name);
            $this->line('   Mailer: ' . $config->mailer);
            $this->line('   Host: ' . $config->host);
            $this->line('   Puerto: ' . $config->port);
            $this->line('   Encriptación: ' . ($config->encryption ? strtoupper($config->encryption) : 'Ninguna'));
            $this->line('   Usuario: ' . $config->username);
            $this->line('   Contraseña: ' . (strlen($config->password) > 0 ? str_repeat('*', strlen($config->password)) : 'No configurada'));
            $this->line('   Remitente: ' . $config->from_address);
            $this->line('   Nombre: ' . $config->from_name);
            $this->line('   Timeout: ' . ($config->timeout ?? 'No configurado'));
            $this->line('   Dominio Local: ' . ($config->local_domain ?? 'No configurado'));
            $this->line('');

            // Validar configuración
            $this->info('🧪 Validando configuración...');
            $validation = $config->validate();

            if ($validation['valid']) {
                $this->info('✅ Configuración válida');
            } else {
                $this->error('❌ Configuración inválida:');
                foreach ($validation['errors'] as $error) {
                    $this->error('   - ' . $error);
                }
            }

            if (!empty($validation['warnings'])) {
                $this->warn('⚠️  Advertencias:');
                foreach ($validation['warnings'] as $warning) {
                    $this->warn('   - ' . $warning);
                }
            }

            $this->line('');
            $this->line('🔧 Configuración Laravel generada:');
            $laravelConfig = $config->toLaravelConfig();
            foreach ($laravelConfig as $key => $value) {
                if ($key === 'password') {
                    $this->line('   ' . $key . ': ' . (strlen($value) > 0 ? str_repeat('*', strlen($value)) : 'No configurada'));
                } else {
                    $this->line('   ' . $key . ': ' . ($value ?? 'No configurado'));
                }
            }

            $this->line('');
            $this->info('💡 Notas importantes:');
            $this->line('   - Para Gmail, asegúrate de usar una contraseña de aplicación');
            $this->line('   - La autenticación de 2 factores debe estar activada');
            $this->line('   - Verifica que el puerto 587 esté abierto');

        } catch (\Exception $e) {
            $this->error('❌ Error verificando credenciales: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
