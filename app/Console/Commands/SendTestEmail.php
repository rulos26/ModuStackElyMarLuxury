<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailService;
use App\Models\EmailTemplate;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email : Email de destino}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar email de prueba usando la configuración SMTP actual';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info("📧 Enviando email de prueba a: {$email}");

        try {
            // Verificar que existe una plantilla de bienvenida
            $template = EmailTemplate::getTemplateByName('welcome');

            if (!$template) {
                $this->warn('⚠️  No existe plantilla de bienvenida, creando una...');

                $template = EmailTemplate::create([
                    'name' => 'welcome',
                    'subject' => 'Bienvenido a {{app_name}} - Email de Prueba',
                    'body_html' => '
                        <h1>¡Hola {{user_name}}!</h1>
                        <p>Este es un email de prueba del sistema <strong>{{app_name}}</strong>.</p>
                        <p>Si recibes este email, significa que la configuración SMTP está funcionando correctamente.</p>
                        <hr>
                        <p><small>Email enviado el {{current_date}} a las {{current_time}}</small></p>
                    ',
                    'body_text' => '
                        ¡Hola {{user_name}}!

                        Este es un email de prueba del sistema {{app_name}}.

                        Si recibes este email, significa que la configuración SMTP está funcionando correctamente.

                        Email enviado el {{current_date}} a las {{current_time}}
                    ',
                    'variables' => [
                        'user_name' => 'Nombre del usuario'
                    ],
                    'category' => 'auth',
                    'description' => 'Plantilla de bienvenida para nuevos usuarios'
                ]);

                $this->info('✅ Plantilla de bienvenida creada');
            }

            $this->line('');
            $this->info('🚀 Enviando email con plantilla...');

            $emailService = app(EmailService::class);

            $result = $emailService->sendTemplate(
                'welcome',
                $email,
                [
                    'user_name' => 'Usuario de Prueba'
                ],
                'Usuario de Prueba',
                [],
                false // No usar cola para prueba inmediata
            );

            if ($result) {
                $this->info('✅ ¡Email enviado exitosamente!');
                $this->line('');
                $this->line('📊 Detalles del envío:');
                $this->line('   Destinatario: ' . $email);
                $this->line('   Plantilla: welcome');
                $this->line('   Asunto: Bienvenido a ' . config('app.name') . ' - Email de Prueba');
                $this->line('   Fecha: ' . now()->format('d/m/Y H:i:s'));
                $this->line('');
                $this->info('🎉 ¡El sistema de emails está funcionando perfectamente!');

            } else {
                $this->error('❌ Error enviando email');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error enviando email de prueba: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
