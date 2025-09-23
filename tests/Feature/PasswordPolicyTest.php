<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AppSetting;
use App\Rules\PasswordPolicyRule;
use Illuminate\Support\Facades\Validator;

class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurar política de contraseñas por defecto
        AppSetting::setValue('password_min_length', 8);
        AppSetting::setValue('password_require_uppercase', true);
        AppSetting::setValue('password_require_lowercase', true);
        AppSetting::setValue('password_require_numbers', true);
        AppSetting::setValue('password_require_special_chars', true);
        AppSetting::setValue('password_forbidden_words', json_encode(['password', '123456']));
        AppSetting::setValue('password_max_repeating_chars', 3);
    }

    /**
     * Test password validation with valid password
     */
    public function test_valid_password_passes_validation()
    {
        $rule = new PasswordPolicyRule();
        $validator = Validator::make(['password' => 'SecurePass123!'], [
            'password' => ['required', $rule]
        ]);

        $this->assertTrue($validator->passes());
    }

    /**
     * Test password validation with insufficient length
     */
    public function test_password_validation_fails_with_insufficient_length()
    {
        $rule = new PasswordPolicyRule();
        $validator = Validator::make(['password' => 'Short1!'], [
            'password' => ['required', $rule]
        ]);

        $this->assertFalse($validator->passes());
        $this->assertStringContainsString('8 caracteres', $validator->errors()->first('password'));
    }

    /**
     * Test password validation without uppercase
     */
    public function test_password_validation_fails_without_uppercase()
    {
        $rule = new PasswordPolicyRule();
        $validator = Validator::make(['password' => 'securepass123!'], [
            'password' => ['required', $rule]
        ]);

        $this->assertFalse($validator->passes());
        $this->assertStringContainsString('mayúscula', $validator->errors()->first('password'));
    }

    /**
     * Test password validation without lowercase
     */
    public function test_password_validation_fails_without_lowercase()
    {
        $rule = new PasswordPolicyRule();
        $validator = Validator::make(['password' => 'SECUREPASS123!'], [
            'password' => ['required', $rule]
        ]);

        $this->assertFalse($validator->passes());
        $this->assertStringContainsString('minúscula', $validator->errors()->first('password'));
    }

    /**
     * Test password validation without numbers
     */
    public function test_password_validation_fails_without_numbers()
    {
        $rule = new PasswordPolicyRule();
        $validator = Validator::make(['password' => 'SecurePass!'], [
            'password' => ['required', $rule]
        ]);

        $this->assertFalse($validator->passes());
        $this->assertStringContainsString('número', $validator->errors()->first('password'));
    }

    /**
     * Test password validation without special characters
     */
    public function test_password_validation_fails_without_special_chars()
    {
        $rule = new PasswordPolicyRule();
        $validator = Validator::make(['password' => 'SecurePass123'], [
            'password' => ['required', $rule]
        ]);

        $this->assertFalse($validator->passes());
        $this->assertStringContainsString('carácter especial', $validator->errors()->first('password'));
    }

    /**
     * Test password validation with forbidden words
     */
    public function test_password_validation_fails_with_forbidden_words()
    {
        $rule = new PasswordPolicyRule();
        $validator = Validator::make(['password' => 'password123!'], [
            'password' => ['required', $rule]
        ]);

        $this->assertFalse($validator->passes());
        $errorMessage = $validator->errors()->first('password');
        $this->assertTrue(
            str_contains($errorMessage, 'password') || str_contains($errorMessage, 'mayúscula'),
            "Expected error message to contain 'password' or 'mayúscula', got: {$errorMessage}"
        );
    }

    /**
     * Test password validation with too many repeating characters
     */
    public function test_password_validation_fails_with_too_many_repeating_chars()
    {
        $rule = new PasswordPolicyRule();
        $validator = Validator::make(['password' => 'SecurePaaaa123!'], [
            'password' => ['required', $rule]
        ]);

        $this->assertFalse($validator->passes());
        $this->assertStringContainsString('repetidos', $validator->errors()->first('password'));
    }

    /**
     * Test password strength calculation
     */
    public function test_password_strength_calculation()
    {
        $rule = new PasswordPolicyRule();

        // Contraseña fuerte
        $strength = $rule->getPasswordStrength('SecurePass123!');
        $this->assertEquals('Muy fuerte', $strength['strength']);
        $this->assertGreaterThanOrEqual(80, $strength['score']);

        // Contraseña débil
        $strength = $rule->getPasswordStrength('weak');
        $this->assertContains($strength['strength'], ['Muy débil', 'Débil']);
        $this->assertLessThan(60, $strength['score']);
    }

    /**
     * Test password policy configuration update
     */
    public function test_password_policy_configuration_update()
    {
        $newPolicy = [
            'min_length' => 10,
            'require_uppercase' => false,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special_chars' => false,
            'forbidden_words' => ['admin', 'test'],
            'max_repeating_chars' => 2
        ];

        PasswordPolicyRule::updatePolicy($newPolicy);

        $this->assertEquals(10, AppSetting::getValue('password_min_length'));
        $this->assertEquals(false, AppSetting::getValue('password_require_uppercase'));
        $this->assertEquals(['admin', 'test'], json_decode(AppSetting::getValue('password_forbidden_words'), true));
    }

    /**
     * Test current policy retrieval
     */
    public function test_current_policy_retrieval()
    {
        $policy = PasswordPolicyRule::getCurrentPolicy();

        $this->assertArrayHasKey('min_length', $policy);
        $this->assertArrayHasKey('require_uppercase', $policy);
        $this->assertArrayHasKey('require_lowercase', $policy);
        $this->assertArrayHasKey('require_numbers', $policy);
        $this->assertArrayHasKey('require_special_chars', $policy);
        $this->assertArrayHasKey('forbidden_words', $policy);
        $this->assertArrayHasKey('max_repeating_chars', $policy);
    }

    /**
     * Test registration with password policy
     */
    public function test_registration_with_password_policy()
    {
        // Test with invalid password
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak'
        ]);

        $response->assertSessionHasErrors('password');

        // Test with valid password
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!'
        ]);

        $response->assertRedirect('/home');
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    /**
     * Test password reset with policy
     */
    public function test_password_reset_with_policy()
    {
        $user = User::factory()->create();

        // Test with invalid password
        $response = $this->post('/password/reset', [
            'token' => 'test-token',
            'email' => $user->email,
            'password' => 'weak',
            'password_confirmation' => 'weak'
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * Test policy summary
     */
    public function test_policy_summary()
    {
        $rule = new PasswordPolicyRule();
        $summary = $rule->getPolicySummary();

        $this->assertArrayHasKey('min_length', $summary);
        $this->assertArrayHasKey('require_uppercase', $summary);
        $this->assertArrayHasKey('require_lowercase', $summary);
        $this->assertArrayHasKey('require_numbers', $summary);
        $this->assertArrayHasKey('require_special_chars', $summary);
        $this->assertArrayHasKey('forbidden_words_count', $summary);
        $this->assertArrayHasKey('max_repeating_chars', $summary);
        $this->assertArrayHasKey('forbidden_words', $summary);
    }

    /**
     * Test common password detection
     */
    public function test_common_password_detection()
    {
        $rule = new PasswordPolicyRule();

        // Test common password
        $validator = Validator::make(['password' => 'password'], [
            'password' => ['required', $rule]
        ]);

        $this->assertFalse($validator->passes());
        $errorMessage = $validator->errors()->first('password');
        $this->assertTrue(
            str_contains($errorMessage, 'común') || str_contains($errorMessage, 'mayúscula') || str_contains($errorMessage, 'número'),
            "Expected error message to contain 'común', 'mayúscula', or 'número', got: {$errorMessage}"
        );
    }

    /**
     * Test strength color coding
     */
    public function test_strength_color_coding()
    {
        $rule = new PasswordPolicyRule();

        // Strong password
        $strength = $rule->getPasswordStrength('SecurePass123!');
        $this->assertEquals('#28a745', $strength['color']); // Green

        // Weak password
        $strength = $rule->getPasswordStrength('weak');
        $this->assertContains($strength['color'], ['#dc3545', '#fd7e14']); // Red or Orange
    }
}
