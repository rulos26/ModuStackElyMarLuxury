<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailService;

class SendSimpleEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:simple {email : Email de destino}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar email simple de prueba';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info("📧 Enviando email simple de prueba a: {$email}");

        try {
            $emailService = app(EmailService::class);

            $this->line('');
            $this->info('🚀 Enviando email directo...');

            $result = $emailService->sendDirect(
                $email,
                'Prueba SMTP - ' . config('app.name'),
                'Este es un email de prueba para verificar que la configuración SMTP está funcionando correctamente.',
                'Usuario de Prueba',
                [],
                false, // No usar cola
                false  // Texto plano
            );

            if ($result) {
                $this->info('✅ ¡Email enviado exitosamente!');
                $this->line('');
                $this->line('📊 Detalles del envío:');
                $this->line('   Destinatario: ' . $email);
                $this->line('   Asunto: Prueba SMTP - ' . config('app.name'));
                $this->line('   Fecha: ' . now()->format('d/m/Y H:i:s'));
                $this->line('');
                $this->info('🎉 ¡El sistema de emails está funcionando perfectamente!');

            } else {
                $this->error('❌ Error enviando email');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error enviando email de prueba: ' . $e->getMessage());
            $this->error('Detalles: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
