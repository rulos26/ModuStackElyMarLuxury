<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class TestGmailConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smtp:test-gmail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar conexión Gmail directamente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Probando conexión Gmail directamente...');

        try {
            // Configurar Gmail manualmente
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp', [
                'transport' => 'smtp',
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'rulos26@gmail.com',
                'password' => 'imltkpfnvehflplt',
                'timeout' => 30,
                'local_domain' => 'localhost',
            ]);
            Config::set('mail.from', [
                'address' => 'rulos26@gmail.com',
                'name' => 'ModuStackElyMarLuxury'
            ]);

            $this->line('');
            $this->line('📧 Configuración aplicada:');
            $this->line('   Host: smtp.gmail.com:587');
            $this->line('   Encriptación: TLS');
            $this->line('   Usuario: rulos26@gmail.com');
            $this->line('   Remitente: rulos26@gmail.com');
            $this->line('');

            // Probar envío simple
            $this->info('🚀 Enviando email de prueba...');

            Mail::raw('Este es un email de prueba para verificar la conexión SMTP con Gmail.', function ($message) {
                $message->to('rulos26@gmail.com')
                        ->subject('Prueba SMTP - ' . config('app.name'));
            });

            $this->info('✅ ¡Email enviado exitosamente!');
            $this->line('');
            $this->info('🎉 La conexión Gmail está funcionando correctamente!');

        } catch (\Exception $e) {
            $this->error('❌ Error conectando con Gmail: ' . $e->getMessage());
            $this->line('');

            // Sugerencias específicas para Gmail
            $this->warn('💡 Posibles soluciones:');
            $this->line('   1. Verifica que la contraseña sea una "Contraseña de aplicación" de Google');
            $this->line('   2. Asegúrate de que la autenticación de 2 factores esté activada');
            $this->line('   3. Ve a: https://myaccount.google.com/security');
            $this->line('   4. En "Contraseñas de aplicaciones", genera una nueva');
            $this->line('   5. Usa esa contraseña de 16 caracteres (sin espacios)');
            $this->line('');
            $this->line('🔧 Para generar contraseña de aplicación:');
            $this->line('   - Ve a tu cuenta de Google');
            $this->line('   - Seguridad → Verificación en 2 pasos');
            $this->line('   - Contraseñas de aplicaciones');
            $this->line('   - Selecciona "Correo" y "Otro (nombre personalizado)"');
            $this->line('   - Escribe "ModuStackElyMarLuxury"');
            $this->line('   - Copia la contraseña de 16 caracteres');

            return 1;
        }

        return 0;
    }
}
