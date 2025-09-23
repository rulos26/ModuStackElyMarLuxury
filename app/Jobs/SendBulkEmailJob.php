<?php

namespace App\Jobs;

use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBulkEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $templateName;
    protected $recipients;
    protected $variables;
    protected $attachments;
    protected $batchSize;

    /**
     * Número de intentos antes de fallar
     */
    public $tries = 3;

    /**
     * Timeout en segundos (5 minutos para emails masivos)
     */
    public $timeout = 300;

    /**
     * Tiempo de espera entre reintentos (segundos)
     */
    public $backoff = [60, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $templateName,
        array $recipients,
        array $variables = [],
        array $attachments = [],
        int $batchSize = 50
    ) {
        $this->templateName = $templateName;
        $this->recipients = $recipients;
        $this->variables = $variables;
        $this->attachments = $attachments;
        $this->batchSize = $batchSize;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $emailService = app(EmailService::class);

            // Dividir destinatarios en lotes
            $batches = array_chunk($this->recipients, $this->batchSize);
            $totalBatches = count($batches);

            Log::info("Iniciando envío masivo de emails", [
                'template' => $this->templateName,
                'total_recipients' => count($this->recipients),
                'total_batches' => $totalBatches
            ]);

            $results = [
                'success' => 0,
                'failed' => 0,
                'errors' => []
            ];

            foreach ($batches as $batchIndex => $batch) {
                $batchResults = $emailService->sendBulk(
                    $this->templateName,
                    $batch,
                    $this->variables,
                    $this->attachments,
                    false // No usar cola para evitar recursión
                );

                $results['success'] += $batchResults['success'];
                $results['failed'] += $batchResults['failed'];
                $results['errors'] = array_merge($results['errors'], $batchResults['errors']);

                Log::info("Lote " . ($batchIndex + 1) . "/{$totalBatches} completado", [
                    'success' => $batchResults['success'],
                    'failed' => $batchResults['failed']
                ]);

                // Pequeña pausa entre lotes para no sobrecargar el servidor
                if ($batchIndex < $totalBatches - 1) {
                    sleep(1);
                }
            }

            Log::info("Envío masivo completado", $results);

        } catch (\Exception $e) {
            Log::error("Error en SendBulkEmailJob: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendBulkEmailJob falló definitivamente: " . $exception->getMessage(), [
            'template' => $this->templateName,
            'recipients_count' => count($this->recipients),
            'batch_size' => $this->batchSize,
            'attempts' => $this->attempts(),
            'max_tries' => $this->tries
        ]);

        // Aquí podrías agregar lógica adicional como:
        // - Notificar al administrador sobre el fallo masivo
        // - Guardar información sobre emails no enviados
        // - Programar reenvío para más tarde
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
            'bulk-email',
            'template:' . $this->templateName,
            'recipients:' . count($this->recipients),
            'batch-size:' . $this->batchSize
        ];
    }
}
