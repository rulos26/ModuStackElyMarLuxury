<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\LoginAttempt;
use App\Services\BlockedIpService;
use Illuminate\Support\Facades\Cache;

class BlockedIpServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $blockedIpService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->blockedIpService = new BlockedIpService();
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
    }

    /**
     * Test that IP is not blocked initially
     */
    public function test_ip_is_not_blocked_initially()
    {
        $ip = '192.168.1.100';
        $this->assertFalse($this->blockedIpService->isIpBlocked($ip));
    }

    /**
     * Test that IP gets blocked after max attempts
     */
    public function test_ip_gets_blocked_after_max_attempts()
    {
        $ip = '192.168.1.100';
        $email = 'test@example.com';
        $userAgent = 'Mozilla/5.0 Test Browser';
        $maxAttempts = config('auth.login_max_attempts', 5);

        // Hacer múltiples intentos fallidos
        for ($i = 1; $i <= $maxAttempts; $i++) {
            $this->blockedIpService->recordLoginAttempt(
                $ip,
                $email,
                $userAgent,
                false,
                'Credenciales inválidas'
            );
        }

        $this->assertTrue($this->blockedIpService->isIpBlocked($ip));
    }

    /**
     * Test that email gets blocked after max attempts
     */
    public function test_email_gets_blocked_after_max_attempts()
    {
        $ip = '192.168.1.100';
        $email = 'test@example.com';
        $userAgent = 'Mozilla/5.0 Test Browser';
        $maxAttempts = config('auth.login_max_attempts', 5);

        // Hacer múltiples intentos fallidos
        for ($i = 1; $i <= $maxAttempts; $i++) {
            $this->blockedIpService->recordLoginAttempt(
                $ip,
                $email,
                $userAgent,
                false,
                'Credenciales inválidas'
            );
        }

        $this->assertTrue($this->blockedIpService->isEmailBlocked($email));
    }

    /**
     * Test that successful login clears attempts
     */
    public function test_successful_login_clears_attempts()
    {
        $ip = '192.168.1.100';
        $email = 'test@example.com';
        $userAgent = 'Mozilla/5.0 Test Browser';

        // Hacer algunos intentos fallidos
        for ($i = 1; $i <= 3; $i++) {
            $this->blockedIpService->recordLoginAttempt(
                $ip,
                $email,
                $userAgent,
                false,
                'Credenciales inválidas'
            );
        }

        // Verificar que hay intentos registrados
        $attempts = LoginAttempt::getFailedAttemptsForIp($ip, 15);
        $this->assertEquals(3, $attempts);

        // Hacer login exitoso
        $this->blockedIpService->recordLoginAttempt(
            $ip,
            $email,
            $userAgent,
            true
        );

        // Verificar que los intentos se limpiaron
        $attempts = LoginAttempt::getFailedAttemptsForIp($ip, 15);
        $this->assertEquals(0, $attempts);
    }

    /**
     * Test that IP block is cleared after successful login
     */
    public function test_ip_block_is_cleared_after_successful_login()
    {
        $ip = '192.168.1.100';
        $email = 'test@example.com';
        $userAgent = 'Mozilla/5.0 Test Browser';
        $maxAttempts = config('auth.login_max_attempts', 5);

        // Hacer suficientes intentos para bloquear la IP
        for ($i = 1; $i <= $maxAttempts; $i++) {
            $this->blockedIpService->recordLoginAttempt(
                $ip,
                $email,
                $userAgent,
                false,
                'Credenciales inválidas'
            );
        }

        // Verificar que la IP está bloqueada
        $this->assertTrue($this->blockedIpService->isIpBlocked($ip));

        // Hacer login exitoso
        $this->blockedIpService->recordLoginAttempt(
            $ip,
            $email,
            $userAgent,
            true
        );

        // Verificar que la IP ya no está bloqueada
        $this->assertFalse($this->blockedIpService->isIpBlocked($ip));
    }

    /**
     * Test manual IP unblocking
     */
    public function test_manual_ip_unblocking()
    {
        $ip = '192.168.1.100';
        $email = 'test@example.com';
        $userAgent = 'Mozilla/5.0 Test Browser';
        $maxAttempts = config('auth.login_max_attempts', 5);

        // Bloquear la IP
        for ($i = 1; $i <= $maxAttempts; $i++) {
            $this->blockedIpService->recordLoginAttempt(
                $ip,
                $email,
                $userAgent,
                false,
                'Credenciales inválidas'
            );
        }

        $this->assertTrue($this->blockedIpService->isIpBlocked($ip));

        // Desbloquear manualmente
        $result = $this->blockedIpService->unblockIp($ip);
        $this->assertTrue($result);
        $this->assertFalse($this->blockedIpService->isIpBlocked($ip));
    }

    /**
     * Test IP whitelist functionality
     */
    public function test_ip_whitelist_functionality()
    {
        // Configurar whitelist
        config(['auth.ip_whitelist' => ['127.0.0.1', '192.168.1.0/24']]);

        // IP en whitelist no debe ser bloqueada
        $this->assertTrue($this->blockedIpService->isIpWhitelisted('127.0.0.1'));
        $this->assertTrue($this->blockedIpService->isIpWhitelisted('192.168.1.100'));
        $this->assertFalse($this->blockedIpService->isIpWhitelisted('192.168.2.100'));
    }

    /**
     * Test blocking statistics
     */
    public function test_blocking_statistics()
    {
        $ip = '192.168.1.100';
        $email = 'test@example.com';
        $userAgent = 'Mozilla/5.0 Test Browser';

        // Registrar algunos intentos
        for ($i = 1; $i <= 3; $i++) {
            $this->blockedIpService->recordLoginAttempt(
                $ip,
                $email,
                $userAgent,
                false,
                'Credenciales inválidas'
            );
        }

        // Registrar un login exitoso
        $this->blockedIpService->recordLoginAttempt(
            $ip,
            $email,
            $userAgent,
            true
        );

        $stats = $this->blockedIpService->getBlockingStats(1); // Última hora

        $this->assertArrayHasKey('total_attempts', $stats);
        $this->assertArrayHasKey('failed_attempts', $stats);
        $this->assertArrayHasKey('successful_attempts', $stats);
        $this->assertArrayHasKey('unique_ips', $stats);
        $this->assertArrayHasKey('blocked_ips', $stats);
        $this->assertArrayHasKey('top_problematic_ips', $stats);

        // Verificar que las estadísticas tienen valores válidos
        $this->assertIsInt($stats['total_attempts']);
        $this->assertIsInt($stats['failed_attempts']);
        $this->assertIsInt($stats['successful_attempts']);
        $this->assertIsInt($stats['unique_ips']);
        $this->assertIsInt($stats['blocked_ips']);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $stats['top_problematic_ips']);
    }

    /**
     * Test cleanup of old attempts
     */
    public function test_cleanup_of_old_attempts()
    {
        $ip = '192.168.1.100';
        $email = 'test@example.com';
        $userAgent = 'Mozilla/5.0 Test Browser';

        // Crear un intento antiguo (simulado)
        LoginAttempt::create([
            'ip_address' => $ip,
            'email' => $email,
            'user_agent' => $userAgent,
            'attempted_at' => now()->subDays(35),
            'success' => false,
            'reason' => 'Test old attempt'
        ]);

        // Verificar que existe
        $this->assertEquals(1, LoginAttempt::count());

        // Limpiar intentos antiguos
        $deleted = $this->blockedIpService->cleanupOldAttempts(30);

        $this->assertEquals(1, $deleted);
        $this->assertEquals(0, LoginAttempt::count());
    }

    /**
     * Test block time remaining calculation
     */
    public function test_block_time_remaining_calculation()
    {
        $ip = '192.168.1.100';
        $email = 'test@example.com';
        $userAgent = 'Mozilla/5.0 Test Browser';
        $maxAttempts = config('auth.login_max_attempts', 5);

        // Bloquear la IP
        for ($i = 1; $i <= $maxAttempts; $i++) {
            $this->blockedIpService->recordLoginAttempt(
                $ip,
                $email,
                $userAgent,
                false,
                'Credenciales inválidas'
            );
        }

        $remaining = $this->blockedIpService->getBlockTimeRemaining($ip);
        $this->assertGreaterThanOrEqual(0, $remaining);
        $this->assertLessThanOrEqual(config('auth.login_lockout_time', 15), $remaining);
    }
}
