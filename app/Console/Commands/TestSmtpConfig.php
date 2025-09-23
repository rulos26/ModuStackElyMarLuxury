<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SmtpConfig;
use App\Services\SmtpConfigService;
use App\Services\EmailService;

class TestSmtpConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smtp:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar configuración SMTP actual';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Probando configuración SMTP...');

        try {
            // Obtener configuración por defecto
            $config = SmtpConfig::getActiveDefault();

            if (!$config) {
                $this->error('❌ No hay configuración SMTP activa por defecto');
                return 1;
            }

            $this->line('');
            $this->line('📧 Configuración encontrada:');
            $this->line('   Nombre: ' . $config->name);
            $this->line('   Host: ' . $config->host . ':' . $config->port);
            $this->line('   Encriptación: ' . strtoupper($config->encryption));
            $this->line('   Usuario: ' . $config->username);
            $this->line('   Remitente: ' . $config->from_address);
            $this->line('');

            // Probar configuración
            $this->info('🧪 Probando conexión SMTP...');

            $smtpService = app(SmtpConfigService::class);
            $result = $smtpService->testConfiguration($config);

            if ($result['success']) {
                $this->info('✅ ¡Conexión SMTP exitosa!');
                $this->line('');
                $this->line('📊 Detalles de la conexión:');
                $this->line('   Host: ' . $result['details']['host']);
                $this->line('   Puerto: ' . $result['details']['port']);
                $this->line('   Encriptación: ' . strtoupper($result['details']['encryption']));
                $this->line('   Usuario: ' . $result['details']['username']);
                $this->line('   Remitente: ' . $result['details']['from_address']);
                $this->line('   Nombre: ' . $result['details']['from_name']);

                if ($result['test_email_sent']) {
                    $this->line('');
                    $this->info('📨 Email de prueba enviado exitosamente!');
                }

                $this->line('');
                $this->info('🚀 La configuración SMTP está funcionando correctamente!');

            } else {
                $this->error('❌ Error en la conexión SMTP:');
                $this->error('   ' . $result['error']);
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error probando configuración SMTP: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
