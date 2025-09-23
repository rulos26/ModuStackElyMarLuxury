<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\AppSetting;

class PasswordPolicyRule implements Rule
{
    protected $message = '';
    protected $minLength;
    protected $requireUppercase;
    protected $requireLowercase;
    protected $requireNumbers;
    protected $requireSpecialChars;
    protected $forbiddenWords;
    protected $maxRepeatingChars;

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
        $this->loadPolicyFromSettings();
    }

    /**
     * Load password policy from app settings
     */
    protected function loadPolicyFromSettings(): void
    {
        $this->minLength = (int) AppSetting::getValue('password_min_length', 8);
        $this->requireUppercase = (bool) AppSetting::getValue('password_require_uppercase', true);
        $this->requireLowercase = (bool) AppSetting::getValue('password_require_lowercase', true);
        $this->requireNumbers = (bool) AppSetting::getValue('password_require_numbers', true);
        $this->requireSpecialChars = (bool) AppSetting::getValue('password_require_special_chars', true);
        $this->forbiddenWords = json_decode(AppSetting::getValue('password_forbidden_words', '[]'), true);
        $this->maxRepeatingChars = (int) AppSetting::getValue('password_max_repeating_chars', 3);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $password = (string) $value;

        // Verificar longitud mínima
        if (strlen($password) < $this->minLength) {
            $this->message = "La contraseña debe tener al menos {$this->minLength} caracteres.";
            return false;
        }

        // Verificar mayúsculas
        if ($this->requireUppercase && !preg_match('/[A-Z]/', $password)) {
            $this->message = 'La contraseña debe contener al menos una letra mayúscula.';
            return false;
        }

        // Verificar minúsculas
        if ($this->requireLowercase && !preg_match('/[a-z]/', $password)) {
            $this->message = 'La contraseña debe contener al menos una letra minúscula.';
            return false;
        }

        // Verificar números
        if ($this->requireNumbers && !preg_match('/[0-9]/', $password)) {
            $this->message = 'La contraseña debe contener al menos un número.';
            return false;
        }

        // Verificar caracteres especiales
        if ($this->requireSpecialChars && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $this->message = 'La contraseña debe contener al menos un carácter especial.';
            return false;
        }

        // Verificar palabras prohibidas
        if (!empty($this->forbiddenWords)) {
            foreach ($this->forbiddenWords as $word) {
                if (stripos($password, $word) !== false) {
                    $this->message = "La contraseña no puede contener la palabra '{$word}'.";
                    return false;
                }
            }
        }

        // Verificar caracteres repetidos
        if ($this->maxRepeatingChars > 0) {
            $repeatingPattern = '/(.)\1{' . $this->maxRepeatingChars . ',}/';
            if (preg_match($repeatingPattern, $password)) {
                $this->message = "La contraseña no puede tener más de {$this->maxRepeatingChars} caracteres repetidos consecutivamente.";
                return false;
            }
        }

        // Verificar contraseñas comunes (opcional)
        if ($this->isCommonPassword($password)) {
            $this->message = 'La contraseña es demasiado común. Por favor, elige una contraseña más segura.';
            return false;
        }

        return true;
    }

    /**
     * Check if password is in common passwords list
     */
    protected function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'letmein', 'welcome', 'monkey',
            '1234567890', 'password1', '12345678', 'dragon', 'master',
            'hello', 'freedom', 'whatever', 'qazwsx', 'trustno1'
        ];

        return in_array(strtolower($password), $commonPasswords);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }

    /**
     * Get password policy summary
     */
    public function getPolicySummary(): array
    {
        return [
            'min_length' => $this->minLength,
            'require_uppercase' => $this->requireUppercase,
            'require_lowercase' => $this->requireLowercase,
            'require_numbers' => $this->requireNumbers,
            'require_special_chars' => $this->requireSpecialChars,
            'forbidden_words_count' => count($this->forbiddenWords),
            'max_repeating_chars' => $this->maxRepeatingChars,
            'forbidden_words' => $this->forbiddenWords
        ];
    }

    /**
     * Get password strength score (0-100)
     */
    public function getPasswordStrength(string $password): array
    {
        $score = 0;
        $feedback = [];

        // Longitud
        if (strlen($password) >= $this->minLength) {
            $score += 20;
        } else {
            $feedback[] = "Agregar más caracteres (mínimo {$this->minLength})";
        }

        // Mayúsculas
        if (preg_match('/[A-Z]/', $password)) {
            $score += 15;
        } else {
            $feedback[] = 'Agregar letras mayúsculas';
        }

        // Minúsculas
        if (preg_match('/[a-z]/', $password)) {
            $score += 15;
        } else {
            $feedback[] = 'Agregar letras minúsculas';
        }

        // Números
        if (preg_match('/[0-9]/', $password)) {
            $score += 15;
        } else {
            $feedback[] = 'Agregar números';
        }

        // Caracteres especiales
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $score += 20;
        } else {
            $feedback[] = 'Agregar caracteres especiales';
        }

        // Longitud extra
        if (strlen($password) >= 12) {
            $score += 10;
        }

        // Variedad
        $uniqueChars = count(array_unique(str_split($password)));
        if ($uniqueChars >= strlen($password) * 0.7) {
            $score += 5;
        }

        // Penalizar contraseñas comunes
        if ($this->isCommonPassword($password)) {
            $score -= 30;
            $feedback[] = 'Evitar contraseñas comunes';
        }

        // Penalizar caracteres repetidos
        if ($this->maxRepeatingChars > 0) {
            $repeatingPattern = '/(.)\1{' . $this->maxRepeatingChars . ',}/';
            if (preg_match($repeatingPattern, $password)) {
                $score -= 20;
                $feedback[] = 'Evitar caracteres repetidos consecutivos';
            }
        }

        $score = max(0, min(100, $score));

        $strength = 'Muy débil';
        if ($score >= 80) $strength = 'Muy fuerte';
        elseif ($score >= 60) $strength = 'Fuerte';
        elseif ($score >= 40) $strength = 'Media';
        elseif ($score >= 20) $strength = 'Débil';

        return [
            'score' => $score,
            'strength' => $strength,
            'feedback' => $feedback,
            'color' => $this->getStrengthColor($score)
        ];
    }

    /**
     * Get color for strength indicator
     */
    protected function getStrengthColor(int $score): string
    {
        if ($score >= 80) return '#28a745'; // Verde
        if ($score >= 60) return '#17a2b8'; // Azul
        if ($score >= 40) return '#ffc107'; // Amarillo
        if ($score >= 20) return '#fd7e14'; // Naranja
        return '#dc3545'; // Rojo
    }

    /**
     * Update password policy settings
     */
    public static function updatePolicy(array $settings): void
    {
        AppSetting::setValue('password_min_length', $settings['min_length'] ?? 8);
        AppSetting::setValue('password_require_uppercase', $settings['require_uppercase'] ?? true);
        AppSetting::setValue('password_require_lowercase', $settings['require_lowercase'] ?? true);
        AppSetting::setValue('password_require_numbers', $settings['require_numbers'] ?? true);
        AppSetting::setValue('password_require_special_chars', $settings['require_special_chars'] ?? true);
        AppSetting::setValue('password_forbidden_words', json_encode($settings['forbidden_words'] ?? []));
        AppSetting::setValue('password_max_repeating_chars', $settings['max_repeating_chars'] ?? 3);
    }

    /**
     * Get current policy settings
     */
    public static function getCurrentPolicy(): array
    {
        return [
            'min_length' => (int) AppSetting::getValue('password_min_length', 8),
            'require_uppercase' => (bool) AppSetting::getValue('password_require_uppercase', true),
            'require_lowercase' => (bool) AppSetting::getValue('password_require_lowercase', true),
            'require_numbers' => (bool) AppSetting::getValue('password_require_numbers', true),
            'require_special_chars' => (bool) AppSetting::getValue('password_require_special_chars', true),
            'forbidden_words' => json_decode(AppSetting::getValue('password_forbidden_words', '[]'), true),
            'max_repeating_chars' => (int) AppSetting::getValue('password_max_repeating_chars', 3),
        ];
    }
}
