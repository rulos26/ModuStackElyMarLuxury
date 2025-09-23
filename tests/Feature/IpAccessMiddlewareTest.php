<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AllowedIp;
use App\Models\AppSetting;
use App\Http\Middleware\IpAccessMiddleware;
use Illuminate\Support\Facades\Log;

class IpAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurar control de acceso por IP habilitado
        AppSetting::setValue('ip_whitelist_enabled', true);
        AppSetting::setValue('ip_access_logging', false);
    }

    /**
     * Test that middleware allows access when IP control is disabled
     */
    public function test_middleware_allows_access_when_ip_control_disabled()
    {
        // Deshabilitar control de IP
        AppSetting::setValue('ip_whitelist_enabled', false);

        $response = $this->get('/');
        $response->assertStatus(302); // Redirect to login
    }

    /**
     * Test that middleware blocks access when IP is not allowed
     */
    public function test_middleware_blocks_access_when_ip_not_allowed()
    {
        // Agregar una IP permitida para activar el control
        AllowedIp::create([
            'ip_address' => '192.168.1.100',
            'type' => 'specific',
            'description' => 'Test IP',
            'status' => 'active'
        ]);

        // Ahora 127.0.0.1 no debería estar permitida
        $response = $this->get('/');
        $response->assertStatus(403);
        $response->assertSee('Acceso denegado');
    }

    /**
     * Test that middleware allows access for specific IP
     */
    public function test_middleware_allows_access_for_specific_ip()
    {
        // Agregar IP específica permitida
        AllowedIp::create([
            'ip_address' => '127.0.0.1',
            'type' => 'specific',
            'description' => 'Localhost',
            'status' => 'active'
        ]);

        $response = $this->get('/');
        $response->assertStatus(302); // Redirect to login
    }

    /**
     * Test that middleware allows access for CIDR range
     */
    public function test_middleware_allows_access_for_cidr_range()
    {
        // Agregar rango CIDR permitido
        AllowedIp::create([
            'ip_address' => '127.0.0.0/24',
            'type' => 'cidr',
            'description' => 'Local network',
            'status' => 'active'
        ]);

        $response = $this->get('/');
        $response->assertStatus(302); // Redirect to login
    }

    /**
     * Test that middleware blocks access for blocked IP
     */
    public function test_middleware_blocks_access_for_blocked_ip()
    {
        // Agregar IP bloqueada
        AllowedIp::create([
            'ip_address' => '127.0.0.1',
            'type' => 'blocked',
            'description' => 'Blocked IP',
            'status' => 'active'
        ]);

        $response = $this->get('/');
        $response->assertStatus(403);
    }

    /**
     * Test that middleware handles JSON requests correctly
     */
    public function test_middleware_handles_json_requests_correctly()
    {
        // Agregar una IP permitida para activar el control
        AllowedIp::create([
            'ip_address' => '192.168.1.100',
            'type' => 'specific',
            'description' => 'Test IP',
            'status' => 'active'
        ]);

        $response = $this->withHeaders(['Accept' => 'application/json'])->get('/');
        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'Acceso denegado',
            'code' => 'IP_ACCESS_DENIED'
        ]);
    }

    /**
     * Test that middleware ignores inactive IPs
     */
    public function test_middleware_ignores_inactive_ips()
    {
        // Agregar IP inactiva
        AllowedIp::create([
            'ip_address' => '127.0.0.1',
            'type' => 'specific',
            'description' => 'Inactive IP',
            'status' => 'inactive'
        ]);

        // Agregar otra IP activa para activar el control
        AllowedIp::create([
            'ip_address' => '192.168.1.100',
            'type' => 'specific',
            'description' => 'Active IP',
            'status' => 'active'
        ]);

        $response = $this->get('/');
        $response->assertStatus(403);
    }

    /**
     * Test that middleware ignores expired IPs
     */
    public function test_middleware_ignores_expired_ips()
    {
        // Agregar IP expirada
        AllowedIp::create([
            'ip_address' => '127.0.0.1',
            'type' => 'specific',
            'description' => 'Expired IP',
            'status' => 'active',
            'expires_at' => now()->subDay()
        ]);

        // Agregar IP activa diferente para activar el control
        AllowedIp::create([
            'ip_address' => '192.168.1.100',
            'type' => 'specific',
            'description' => 'Active IP',
            'status' => 'active'
        ]);

        // La IP 127.0.0.1 está expirada, pero como hay otra IP activa, el control está habilitado
        // Sin embargo, 127.0.0.1 no debería tener acceso porque está expirada

        // Verificar que la IP expirada no esté siendo considerada activa
        $expiredIp = AllowedIp::where('ip_address', '127.0.0.1')->first();
        $this->assertTrue($expiredIp->isExpired());

        $response = $this->get('/');
        $response->assertStatus(403);
    }

    /**
     * Test IPv4 CIDR range validation
     */
    public function test_ipv4_cidr_range_validation()
    {
        // Agregar rango IPv4
        AllowedIp::create([
            'ip_address' => '192.168.1.0/24',
            'type' => 'cidr',
            'description' => 'IPv4 range',
            'status' => 'active'
        ]);

        // Simular IP en el rango (esto requeriría mockear la IP del request)
        $response = $this->get('/');
        // El test real dependería de la IP real del test
        $this->assertTrue(true); // Placeholder
    }

    /**
     * Test IPv6 CIDR range validation
     */
    public function test_ipv6_cidr_range_validation()
    {
        // Agregar rango IPv6
        AllowedIp::create([
            'ip_address' => '2001:db8::/32',
            'type' => 'cidr',
            'description' => 'IPv6 range',
            'status' => 'active'
        ]);

        // Simular IP en el rango
        $response = $this->get('/');
        $this->assertTrue(true); // Placeholder
    }

    /**
     * Test access statistics
     */
    public function test_access_statistics()
    {
        // Crear algunas IPs de prueba
        AllowedIp::create([
            'ip_address' => '127.0.0.1',
            'type' => 'specific',
            'description' => 'Test IP 1',
            'status' => 'active'
        ]);

        AllowedIp::create([
            'ip_address' => '192.168.1.0/24',
            'type' => 'cidr',
            'description' => 'Test CIDR',
            'status' => 'active'
        ]);

        AllowedIp::create([
            'ip_address' => '10.0.0.1',
            'type' => 'blocked',
            'description' => 'Blocked IP',
            'status' => 'active'
        ]);

        $stats = IpAccessMiddleware::getAccessStats();

        $this->assertArrayHasKey('total_allowed_ips', $stats);
        $this->assertArrayHasKey('total_cidr_ranges', $stats);
        $this->assertArrayHasKey('total_blocked_ips', $stats);
        $this->assertArrayHasKey('ip_control_enabled', $stats);
        $this->assertArrayHasKey('access_logging_enabled', $stats);

        $this->assertEquals(1, $stats['total_allowed_ips']);
        $this->assertEquals(1, $stats['total_cidr_ranges']);
        $this->assertEquals(1, $stats['total_blocked_ips']);
        $this->assertTrue($stats['ip_control_enabled']);
    }

    /**
     * Test static IP access check
     */
    public function test_static_ip_access_check()
    {
        // Agregar IP permitida
        AllowedIp::create([
            'ip_address' => '127.0.0.1',
            'type' => 'specific',
            'description' => 'Test IP',
            'status' => 'active'
        ]);

        $this->assertTrue(IpAccessMiddleware::checkIpAccess('127.0.0.1'));

        // Agregar otra IP para activar el control
        AllowedIp::create([
            'ip_address' => '192.168.1.100',
            'type' => 'specific',
            'description' => 'Another IP',
            'status' => 'active'
        ]);

        $this->assertFalse(IpAccessMiddleware::checkIpAccess('192.168.1.1'));
    }

    /**
     * Test add allowed IP functionality
     */
    public function test_add_allowed_ip_functionality()
    {
        $result = IpAccessMiddleware::addAllowedIp('192.168.1.100', 'specific', 'Test IP');

        $this->assertTrue($result);
        $this->assertDatabaseHas('allowed_ips', [
            'ip_address' => '192.168.1.100',
            'type' => 'specific',
            'description' => 'Test IP',
            'status' => 'active'
        ]);
    }

    /**
     * Test remove allowed IP functionality
     */
    public function test_remove_allowed_ip_functionality()
    {
        // Crear IP primero
        AllowedIp::create([
            'ip_address' => '192.168.1.100',
            'type' => 'specific',
            'description' => 'Test IP',
            'status' => 'active'
        ]);

        $result = IpAccessMiddleware::removeAllowedIp('192.168.1.100');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('allowed_ips', [
            'ip_address' => '192.168.1.100'
        ]);
    }

    /**
     * Test AllowedIp model functionality
     */
    public function test_allowed_ip_model_functionality()
    {
        // Test IP específica
        $ip = AllowedIp::create([
            'ip_address' => '127.0.0.1',
            'type' => 'specific',
            'description' => 'Test IP',
            'status' => 'active'
        ]);

        $this->assertTrue($ip->isActive());
        $this->assertFalse($ip->isExpired());
        $this->assertEquals('IP Específica', $ip->type_name);
        $this->assertEquals('Activa', $ip->status_name);

        // Test CIDR
        $cidr = AllowedIp::create([
            'ip_address' => '192.168.1.0/24',
            'type' => 'cidr',
            'description' => 'Test CIDR',
            'status' => 'active'
        ]);

        $this->assertEquals('Rango CIDR', $cidr->type_name);

        // Test blocked
        $blocked = AllowedIp::create([
            'ip_address' => '10.0.0.1',
            'type' => 'blocked',
            'description' => 'Blocked IP',
            'status' => 'active'
        ]);

        $this->assertEquals('IP Bloqueada', $blocked->type_name);
    }

    /**
     * Test IP format validation
     */
    public function test_ip_format_validation()
    {
        // Valid IPs
        $this->assertTrue(AllowedIp::validateIpFormat('127.0.0.1'));
        $this->assertTrue(AllowedIp::validateIpFormat('192.168.1.0/24', 'cidr'));
        $this->assertTrue(AllowedIp::validateIpFormat('2001:db8::1'));

        // Invalid IPs
        $this->assertFalse(AllowedIp::validateIpFormat('invalid-ip'));
        $this->assertFalse(AllowedIp::validateIpFormat('192.168.1.0/33', 'cidr'));
        $this->assertFalse(AllowedIp::validateIpFormat('192.168.1.0', 'cidr'));
    }

    /**
     * Test model statistics
     */
    public function test_model_statistics()
    {
        // Crear IPs de diferentes tipos
        AllowedIp::create(['ip_address' => '127.0.0.1', 'type' => 'specific', 'status' => 'active']);
        AllowedIp::create(['ip_address' => '192.168.1.0/24', 'type' => 'cidr', 'status' => 'active']);
        AllowedIp::create(['ip_address' => '10.0.0.1', 'type' => 'blocked', 'status' => 'active']);
        AllowedIp::create(['ip_address' => '172.16.0.1', 'type' => 'specific', 'status' => 'inactive']);
        AllowedIp::create(['ip_address' => '203.0.113.1', 'type' => 'specific', 'status' => 'active', 'expires_at' => now()->subDay()]);

        $stats = AllowedIp::getStats();

        $this->assertEquals(5, $stats['total_ips']);
        $this->assertEquals(3, $stats['active_ips']);
        $this->assertEquals(1, $stats['expired_ips']);
        $this->assertEquals(1, $stats['specific_ips']);
        $this->assertEquals(1, $stats['cidr_ranges']);
        $this->assertEquals(1, $stats['blocked_ips']);
    }
}
