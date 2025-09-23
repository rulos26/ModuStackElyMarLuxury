<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SmtpConfig;

class CreateSmtpConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smtp:create-gmail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear configuración SMTP de Gmail para rulos26@gmail.com';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creando configuración SMTP de Gmail...');

        try {
            $config = SmtpConfig::create([
                'name' => 'Gmail - rulos26@gmail.com',
                'mailer' => 'smtp',
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'rulos26@gmail.com',
                'password' => 'imltkpfnvehflplt',
                'timeout' => 30,
                'local_domain' => 'localhost',
                'from_address' => 'rulos26@gmail.com',
                'from_name' => 'ModuStackElyMarLuxury',
                'description' => 'Configuración Gmail para rulos26@gmail.com',
                'is_active' => true,
                'is_default' => true
            ]);

            $this->info('✅ Configuración SMTP creada exitosamente!');
            $this->line('');
            $this->line('📧 Detalles de la configuración:');
            $this->line('   ID: ' . $config->id);
            $this->line('   Nombre: ' . $config->name);
            $this->line('   Host: ' . $config->host);
            $this->line('   Puerto: ' . $config->port);
            $this->line('   Encriptación: ' . strtoupper($config->encryption));
            $this->line('   Usuario: ' . $config->username);
            $this->line('   Remitente: ' . $config->from_address);
            $this->line('   Nombre: ' . $config->from_name);
            $this->line('   Estado: ' . ($config->is_active ? 'Activa' : 'Inactiva'));
            $this->line('   Por defecto: ' . ($config->is_default ? 'Sí' : 'No'));
            $this->line('');
            $this->info('🚀 La configuración está lista para usar!');

        } catch (\Exception $e) {
            $this->error('❌ Error creando configuración SMTP: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
