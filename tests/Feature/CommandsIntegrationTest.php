<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CommandsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function system_status_command_works()
    {
        $this->artisan('system:status')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function system_maintenance_command_works()
    {
        $this->artisan('system:maintenance status')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function system_monitor_command_works()
    {
        $this->artisan('system:monitor status')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function backup_command_works()
    {
        $this->artisan('backup:manage list')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function notification_command_works()
    {
        $this->artisan('notification:manage list')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function cleanup_command_works()
    {
        $this->artisan('cleanup:manage status')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function jobs_command_works()
    {
        $this->artisan('jobs:manage status')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function workers_command_works()
    {
        $this->artisan('workers:start --workers=1 --timeout=30')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function commands_handle_options_correctly()
    {
        $this->artisan('system:status --detailed')->assertExitCode(0);
        $this->artisan('system:status --json')->assertExitCode(0);
        $this->artisan('system:status --save')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function commands_handle_errors_gracefully()
    {
        $this->artisan('system:maintenance start')->assertExitCode(1);
        $this->artisan('system:monitor start')->assertExitCode(1);
        $this->artisan('backup:manage create')->assertExitCode(1);
        $this->artisan('notification:manage send')->assertExitCode(1);
        $this->artisan('cleanup:manage schedule')->assertExitCode(1);
    }

    /**
     * @test
     */
    public function commands_handle_database_operations()
    {
        $this->artisan('system:status')->assertExitCode(0);
        $this->artisan('backup:manage list')->assertExitCode(0);
        $this->artisan('notification:manage list')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function commands_handle_job_dispatching()
    {
        Queue::fake();

        $this->artisan('system:maintenance start --notify')->assertExitCode(1);
        $this->artisan('system:monitor start --log')->assertExitCode(1);
        $this->artisan('backup:manage create --retention=30')->assertExitCode(1);
        $this->artisan('notification:manage send --channels=email,sms')->assertExitCode(1);
        $this->artisan('cleanup:manage schedule --type=logs')->assertExitCode(1);
    }

    /**
     * @test
     */
    public function commands_handle_health_checks()
    {
        $this->artisan('system:status')->assertExitCode(0);
        $this->artisan('system:monitor health')->assertExitCode(1);
        $this->artisan('backup:manage verify')->assertExitCode(0);
        $this->artisan('notification:manage test')->assertExitCode(1);
    }

    /**
     * @test
     */
    public function commands_handle_statistics()
    {
        $this->artisan('system:status')->assertExitCode(0);
        $this->artisan('jobs:manage status')->assertExitCode(0);
        $this->artisan('workers:start --workers=1 --timeout=30')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function commands_handle_configuration()
    {
        $this->artisan('system:status')->assertExitCode(0);
        $this->artisan('system:maintenance status')->assertExitCode(0);
        $this->artisan('system:monitor status')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function commands_handle_logging()
    {
        $this->artisan('system:status')->assertExitCode(0);
        $this->artisan('system:monitor start --log')->assertExitCode(1);
        $this->artisan('cleanup:manage schedule --type=logs')->assertExitCode(1);
    }

    /**
     * @test
     */
    public function commands_handle_monitoring()
    {
        $this->artisan('system:monitor status')->assertExitCode(0);
        $this->artisan('system:monitor health')->assertExitCode(1);
        $this->artisan('system:monitor alerts')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function commands_handle_backups()
    {
        $this->artisan('backup:manage list')->assertExitCode(0);
        $this->artisan('backup:manage create')->assertExitCode(1);
        $this->artisan('backup:manage verify')->assertExitCode(0);
        $this->artisan('backup:manage schedule')->assertExitCode(1);
    }

    /**
     * @test
     */
    public function commands_handle_notifications()
    {
        $this->artisan('notification:manage list')->assertExitCode(0);
        $this->artisan('notification:manage send')->assertExitCode(1);
        $this->artisan('notification:manage test')->assertExitCode(1);
        $this->artisan('notification:manage schedule')->assertExitCode(1);
    }

    /**
     * @test
     */
    public function commands_handle_cleanup()
    {
        $this->artisan('cleanup:manage status')->assertExitCode(0);
        $this->artisan('cleanup:manage run --type=logs')->assertExitCode(0);
        $this->artisan('cleanup:manage schedule')->assertExitCode(1);
    }

    /**
     * @test
     */
    public function commands_handle_jobs()
    {
        $this->artisan('jobs:manage status')->assertExitCode(0);
        $this->artisan('jobs:manage dispatch')->assertExitCode(1);
        $this->artisan('jobs:manage clear')->assertExitCode(0);
        $this->artisan('jobs:manage retry')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function commands_handle_workers()
    {
        $this->artisan('workers:start --workers=1 --timeout=30')->assertExitCode(0);
        $this->artisan('workers:stop')->assertExitCode(0);
        $this->artisan('workers:restart')->assertExitCode(0);
        $this->artisan('workers:status')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function commands_integration_complete_works()
    {
        // Probar todos los comandos principales
        $this->artisan('system:status')->assertExitCode(0);
        $this->artisan('system:maintenance status')->assertExitCode(0);
        $this->artisan('system:monitor status')->assertExitCode(0);
        $this->artisan('backup:manage list')->assertExitCode(0);
        $this->artisan('notification:manage list')->assertExitCode(0);
        $this->artisan('cleanup:manage status')->assertExitCode(0);
        $this->artisan('jobs:manage status')->assertExitCode(0);
        $this->artisan('workers:start --workers=1 --timeout=30')->assertExitCode(0);
    }
}

