<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendEmailJob;
use App\Jobs\SendBulkEmailJob;
use App\Services\EmailService;
use App\Models\EmailTemplate;

class TestEmailQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-queue {email : Email de destino} {--count=5 : Número de emails a enviar} {--bulk : Probar envío masivo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar sistema de colas enviando emails de prueba';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $count = (int) $this->option('count');
        $useBulk = $this->option('bulk');

        $this->info("📧 Probando sistema de colas con {$count} emails a: {$email}");
        $this->line('');

        try {
            // Crear plantilla de prueba si no existe
            $this->createTestTemplate();

            if ($useBulk) {
                $this->testBulkEmailQueue($email, $count);
            } else {
                $this->testIndividualEmailQueue($email, $count);
            }

            $this->showQueueStatus();

        } catch (\Exception $e) {
            $this->error('❌ Error probando colas: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Crear plantilla de prueba
     */
    protected function createTestTemplate()
    {
        $template = EmailTemplate::getTemplateByName('queue-test');

        if (!$template) {
            $this->info('📝 Creando plantilla de prueba...');

            EmailTemplate::create([
                'name' => 'queue-test',
                'subject' => 'Prueba de Cola - Email {{email_number}}',
                'body_html' => '
                    <h1>Prueba del Sistema de Colas</h1>
                    <p>Este es el email número <strong>{{email_number}}</strong> de una prueba del sistema de colas.</p>
                    <p>Si recibes este email, significa que el sistema de colas está funcionando correctamente.</p>
                    <hr>
                    <p><small>Enviado el {{current_date}} a las {{current_time}}</small></p>
                    <p><small>Job ID: {{job_id}}</small></p>
                ',
                'body_text' => '
                    Prueba del Sistema de Colas

                    Este es el email número {{email_number}} de una prueba del sistema de colas.

                    Si recibes este email, significa que el sistema de colas está funcionando correctamente.

                    Enviado el {{current_date}} a las {{current_time}}
                    Job ID: {{job_id}}
                ',
                'variables' => [
                    'email_number' => 'Número del email en la secuencia',
                    'job_id' => 'ID único del job'
                ],
                'category' => 'system',
                'description' => 'Plantilla para pruebas del sistema de colas'
            ]);

            $this->info('✅ Plantilla de prueba creada');
        }
    }

    /**
     * Probar cola de emails individuales
     */
    protected function testIndividualEmailQueue(string $email, int $count)
    {
        $this->info("🚀 Enviando {$count} emails individuales a la cola...");

        for ($i = 1; $i <= $count; $i++) {
            $jobId = uniqid('test_', true);

            $emailData = [
                'template_name' => 'queue-test',
                'to_email' => $email,
                'to_name' => 'Usuario de Prueba',
                'variables' => [
                    'email_number' => $i,
                    'job_id' => $jobId
                ],
                'sent_at' => now()
            ];

            SendEmailJob::dispatch($emailData, true)->onQueue('emails');

            $this->line("   📤 Email {$i} enviado a la cola (Job ID: {$jobId})");
        }

        $this->info("✅ {$count} emails individuales enviados a la cola");
    }

    /**
     * Probar cola de emails masivos
     */
    protected function testBulkEmailQueue(string $email, int $count)
    {
        $this->info("🚀 Enviando {$count} emails masivos a la cola...");

        // Crear lista de destinatarios
        $recipients = [];
        for ($i = 1; $i <= $count; $i++) {
            $recipients[] = [
                'email' => $email,
                'name' => "Usuario de Prueba {$i}"
            ];
        }

        $variables = [
            'email_number' => 'Variable dinámica',
            'job_id' => uniqid('bulk_test_', true)
        ];

        SendBulkEmailJob::dispatch(
            'queue-test',
            $recipients,
            $variables,
            [],
            10 // Tamaño de lote
        )->onQueue('bulk-emails');

        $this->info("✅ Job de envío masivo enviado a la cola");
        $this->line("   📤 {$count} emails programados para envío masivo");
    }

    /**
     * Mostrar estado de la cola
     */
    protected function showQueueStatus()
    {
        $this->line('');
        $this->info('📊 Estado de la Cola:');

        try {
            $pendingJobs = \DB::table('jobs')->count();
            $failedJobs = \DB::table('failed_jobs')->count();

            $this->line("   Jobs pendientes: {$pendingJobs}");
            $this->line("   Jobs fallidos: {$failedJobs}");

            if ($pendingJobs > 0) {
                $this->line('');
                $this->info('💡 Para procesar la cola:');
                $this->line('   php artisan queue:work --queue=emails,bulk-emails');
                $this->line('   php artisan email:process-queue');
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Error obteniendo estado: ' . $e->getMessage());
        }

        $this->line('');
        $this->info('🎉 ¡Sistema de colas probado exitosamente!');
    }
}
