<?php

namespace App\Jobs;

use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emailData;
    protected $isTemplate;

    /**
     * Número de intentos antes de fallar
     */
    public $tries = 3;

    /**
     * Timeout en segundos
     */
    public $timeout = 120;

    /**
     * Tiempo de espera entre reintentos (segundos)
     */
    public $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(array $emailData, bool $isTemplate = true)
    {
        $this->emailData = $emailData;
        $this->isTemplate = $isTemplate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $emailService = app(EmailService::class);

            if ($this->isTemplate) {
                $success = $emailService->sendTemplate(
                    $this->emailData['template_name'],
                    $this->emailData['to_email'],
                    $this->emailData['variables'] ?? [],
                    $this->emailData['to_name'] ?? null,
                    $this->emailData['attachments'] ?? [],
                    false // No usar cola para evitar recursión
                );
            } else {
                $success = $emailService->sendDirect(
                    $this->emailData['to_email'],
                    $this->emailData['subject'],
                    $this->emailData['body'],
                    $this->emailData['to_name'] ?? null,
                    $this->emailData['attachments'] ?? [],
                    false, // No usar cola para evitar recursión
                    $this->emailData['is_html'] ?? true
                );
            }

            if (!$success) {
                Log::warning("Job de email falló: " . json_encode($this->emailData));
                $this->fail(new \Exception('Error enviando email desde job'));
            }

        } catch (\Exception $e) {
            Log::error("Error en SendEmailJob: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendEmailJob falló definitivamente: " . $exception->getMessage(), [
            'email_data' => $this->emailData,
            'is_template' => $this->isTemplate,
            'attempts' => $this->attempts(),
            'max_tries' => $this->tries
        ]);

        // Aquí podrías agregar lógica adicional como:
        // - Enviar notificación al administrador
        // - Guardar en una tabla de emails fallidos
        // - Intentar método alternativo de envío
    }

    /**
     * Determinar si el job debe ser reintentado
     */
    public function shouldRetry(\Throwable $exception): bool
    {
        // No reintentar para ciertos tipos de errores
        if ($exception instanceof \InvalidArgumentException) {
            return false;
        }

        return $this->attempts() < $this->tries;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'email',
            'send',
            'template:' . ($this->isTemplate ? 'yes' : 'no'),
            'to:' . ($this->emailData['to_email'] ?? 'unknown')
        ];
    }
}
