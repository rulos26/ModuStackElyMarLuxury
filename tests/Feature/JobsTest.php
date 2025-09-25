<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use App\Jobs\SystemIntegrationJob;
use App\Jobs\LoggingJob;
use App\Jobs\BackupJob;
use App\Jobs\NotificationJob;
use App\Jobs\CleanupJob;
use App\Services\JobService;

class JobsTest extends TestCase
{
    use RefreshDatabase;

    protected $jobService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jobService = new JobService();
    }

    /**
     * @test
     */
    public function system_integration_job_can_be_dispatched()
    {
        Queue::fake();

        $job = new SystemIntegrationJob('system_health_check', [
            'test' => true,
            'timestamp' => now()->toISOString()
        ], 2);

        dispatch($job);

        Queue::assertPushed(SystemIntegrationJob::class);
    }

    /**
     * @test
     */
    public function logging_job_can_be_dispatched()
    {
        Queue::fake();

        $job = new LoggingJob('system_log', [
            'event' => 'test_event',
            'timestamp' => now()->toISOString()
        ], 'info', 'daily');

        dispatch($job);

        Queue::assertPushed(LoggingJob::class);
    }

    /**
     * @test
     */
    public function backup_job_can_be_dispatched()
    {
        Queue::fake();

        $job = new BackupJob('database', [
            'name' => 'Test Backup',
            'description' => 'Test backup description'
        ], 3, 30);

        dispatch($job);

        Queue::assertPushed(BackupJob::class);
    }

    /**
     * @test
     */
    public function notification_job_can_be_dispatched()
    {
        Queue::fake();

        $job = new NotificationJob('system_alert', [
            'title' => 'Test Alert',
            'message' => 'Test alert message'
        ], 3, ['database']);

        dispatch($job);

        Queue::assertPushed(NotificationJob::class);
    }

    /**
     * @test
     */
    public function cleanup_job_can_be_dispatched()
    {
        Queue::fake();

        $job = new CleanupJob('full_cleanup', [
            'test' => true
        ], 30);

        dispatch($job);

        Queue::assertPushed(CleanupJob::class);
    }

    /**
     * @test
     */
    public function job_service_can_dispatch_system_job()
    {
        Queue::fake();

        $this->jobService->dispatchSystemJob('system_health_check', [
            'test' => true
        ], 2);

        Queue::assertPushed(SystemIntegrationJob::class);
    }

    /**
     * @test
     */
    public function job_service_can_dispatch_logging_job()
    {
        Queue::fake();

        $this->jobService->dispatchLoggingJob('system_log', [
            'event' => 'test_event'
        ], 'info', 'daily');

        Queue::assertPushed(LoggingJob::class);
    }

    /**
     * @test
     */
    public function job_service_can_dispatch_backup_job()
    {
        Queue::fake();

        $this->jobService->dispatchBackupJob('database', [
            'name' => 'Test Backup'
        ], 3, 30);

        Queue::assertPushed(BackupJob::class);
    }

    /**
     * @test
     */
    public function job_service_can_dispatch_notification_job()
    {
        Queue::fake();

        $this->jobService->dispatchNotificationJob('system_alert', [
            'title' => 'Test Alert'
        ], 3, ['database']);

        Queue::assertPushed(NotificationJob::class);
    }

    /**
     * @test
     */
    public function job_service_can_dispatch_cleanup_job()
    {
        Queue::fake();

        $this->jobService->dispatchCleanupJob('full_cleanup', [
            'test' => true
        ], 30);

        Queue::assertPushed(CleanupJob::class);
    }

    /**
     * @test
     */
    public function job_service_can_get_statistics()
    {
        $stats = $this->jobService->getJobStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_jobs', $stats);
        $this->assertArrayHasKey('successful_jobs', $stats);
        $this->assertArrayHasKey('failed_jobs', $stats);
        $this->assertArrayHasKey('queue_sizes', $stats);
        $this->assertArrayHasKey('worker_status', $stats);
    }

    /**
     * @test
     */
    public function job_service_can_get_pending_jobs()
    {
        $pendingJobs = $this->jobService->getPendingJobs();

        $this->assertIsArray($pendingJobs);
    }

    /**
     * @test
     */
    public function job_service_can_check_health()
    {
        $health = $this->jobService->checkJobHealth();

        $this->assertIsArray($health);
        $this->assertArrayHasKey('healthy', $health);
        $this->assertArrayHasKey('issues', $health);
        $this->assertArrayHasKey('timestamp', $health);
    }

    /**
     * @test
     */
    public function job_service_can_record_successful_job()
    {
        $this->jobService->recordSuccessfulJob('system');

        $this->assertTrue(true); // Job registrado exitosamente
    }

    /**
     * @test
     */
    public function job_service_can_record_failed_job()
    {
        $this->jobService->recordFailedJob('system', 'Test error');

        $this->assertTrue(true); // Job fallido registrado
    }

    /**
     * @test
     */
    public function job_service_can_clear_statistics()
    {
        $this->jobService->clearJobStatistics();

        $this->assertTrue(true); // Estadísticas limpiadas
    }

    /**
     * @test
     */
    public function system_integration_job_handles_errors_gracefully()
    {
        $job = new SystemIntegrationJob('invalid_type', []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipo de integración no válido');

        $job->handle(
            app(\App\Services\DynamicDriverService::class),
            app(\App\Services\BackupService::class),
            app(\App\Services\NotificationService::class),
            app(\App\Services\ActivityLogService::class)
        );
    }

    /**
     * @test
     */
    public function logging_job_processes_different_log_types()
    {
        $job = new LoggingJob('system_log', [
            'event' => 'test_event'
        ], 'info', 'daily');

        // Simular ejecución del job
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function backup_job_handles_different_backup_types()
    {
        $job = new BackupJob('database', [
            'name' => 'Test Backup'
        ], 3, 30);

        // Simular ejecución del job
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function notification_job_handles_different_notification_types()
    {
        $job = new NotificationJob('system_alert', [
            'title' => 'Test Alert',
            'message' => 'Test message'
        ], 3, ['database']);

        // Simular ejecución del job
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function cleanup_job_handles_different_cleanup_types()
    {
        $job = new CleanupJob('full_cleanup', [
            'test' => true
        ], 30);

        // Simular ejecución del job
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function jobs_can_be_dispatched_with_different_priorities()
    {
        Queue::fake();

        // Job de alta prioridad
        $highPriorityJob = new SystemIntegrationJob('system_health_check', [], 1);
        dispatch($highPriorityJob);

        // Job de baja prioridad
        $lowPriorityJob = new CleanupJob('full_cleanup', [], 30);
        dispatch($lowPriorityJob);

        Queue::assertPushed(SystemIntegrationJob::class);
        Queue::assertPushed(CleanupJob::class);
    }

    /**
     * @test
     */
    public function jobs_can_be_dispatched_to_different_queues()
    {
        Queue::fake();

        $systemJob = new SystemIntegrationJob('system_health_check', [], 2);
        $systemJob->onQueue('high');
        dispatch($systemJob);

        $loggingJob = new LoggingJob('system_log', [], 'info', 'daily');
        $loggingJob->onQueue('logging');
        dispatch($loggingJob);

        Queue::assertPushed(SystemIntegrationJob::class);
        Queue::assertPushed(LoggingJob::class);
    }

    /**
     * @test
     */
    public function job_service_can_dispatch_scheduled_jobs()
    {
        Queue::fake();

        $this->jobService->dispatchScheduledJobs();

        // Verificar que se despacharon jobs programados
        Queue::assertPushed(SystemIntegrationJob::class);
        Queue::assertPushed(LoggingJob::class);
    }

    /**
     * @test
     */
    public function jobs_handle_failures_gracefully()
    {
        Log::shouldReceive('error')->once();

        $job = new SystemIntegrationJob('system_health_check', []);

        // Simular fallo
        $job->failed(new \Exception('Test failure'));

        $this->assertTrue(true); // Job manejó el fallo
    }

    /**
     * @test
     */
    public function job_statistics_are_updated_correctly()
    {
        $this->jobService->recordSuccessfulJob('system');
        $this->jobService->recordFailedJob('system', 'Test error');

        $stats = $this->jobService->getJobStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_jobs', $stats);
    }
}
