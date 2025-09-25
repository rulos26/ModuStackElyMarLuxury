<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'body_html',
        'body_text',
        'variables',
        'is_active',
        'category',
        'description'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Categorías de plantillas
     */
    const CATEGORY_AUTH = 'auth';
    const CATEGORY_NOTIFICATIONS = 'notifications';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_MARKETING = 'marketing';

    /**
     * Scope para plantillas activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para plantillas por categoría
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Procesar plantilla con variables
     */
    public function processTemplate(array $variables = []): array
    {
        $subject = $this->processString($this->subject, $variables);
        $bodyHtml = $this->processString($this->body_html, $variables);
        $bodyText = $this->processString($this->body_text, $variables);

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText
        ];
    }

    /**
     * Procesar string con variables
     */
    private function processString(string $template, array $variables): string
    {
        $processed = $template;

        // Variables del sistema por defecto
        $systemVariables = [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'current_year' => date('Y'),
            'current_date' => Carbon::now()->format('d/m/Y'),
            'current_time' => Carbon::now()->format('H:i:s'),
        ];

        // Combinar variables del sistema con las proporcionadas
        $allVariables = array_merge($systemVariables, $variables);

        // Reemplazar variables en formato {{variable}}
        foreach ($allVariables as $key => $value) {
            $processed = str_replace("{{$key}}", $value, $processed);
        }

        // Reemplazar variables en formato :variable
        foreach ($allVariables as $key => $value) {
            $processed = str_replace(":{$key}", $value, $processed);
        }

        return $processed;
    }

    /**
     * Obtener variables disponibles en la plantilla
     */
    public function getAvailableVariables(): array
    {
        $variables = $this->variables ?? [];

        // Agregar variables del sistema
        $systemVariables = [
            'app_name' => 'Nombre de la aplicación',
            'app_url' => 'URL de la aplicación',
            'current_year' => 'Año actual',
            'current_date' => 'Fecha actual',
            'current_time' => 'Hora actual',
        ];

        return array_merge($systemVariables, $variables);
    }

    /**
     * Validar si la plantilla tiene todas las variables necesarias
     */
    public function validateVariables(array $providedVariables): array
    {
        $missing = [];
        $templateVariables = $this->getTemplateVariables($this->subject . $this->body_html . $this->body_text);

        foreach ($templateVariables as $variable) {
            if (!isset($providedVariables[$variable]) && !in_array($variable, ['app_name', 'app_url', 'current_year', 'current_date', 'current_time'])) {
                $missing[] = $variable;
            }
        }

        return $missing;
    }

    /**
     * Extraer variables de un template
     */
    private function getTemplateVariables(string $template): array
    {
        $variables = [];

        // Buscar variables en formato {{variable}}
        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches1);
        if (!empty($matches1[1])) {
            $variables = array_merge($variables, $matches1[1]);
        }

        // Buscar variables en formato :variable
        preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $template, $matches2);
        if (!empty($matches2[1])) {
            $variables = array_merge($variables, $matches2[1]);
        }

        return array_unique($variables);
    }

    /**
     * Obtener plantillas por categoría
     */
    public static function getTemplatesByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()->byCategory($category)->get();
    }

    /**
     * Obtener plantilla por nombre
     */
    public static function getTemplateByName(string $name): ?self
    {
        return self::active()->where('name', $name)->first();
    }

    /**
     * Crear plantilla de ejemplo
     */
    public static function createExampleTemplate(string $name, string $category): self
    {
        $templates = [
            'welcome' => [
                'subject' => 'Bienvenido a {{app_name}}',
                'body_html' => '<h1>¡Bienvenido {{user_name}}!</h1><p>Gracias por registrarte en {{app_name}}.</p>',
                'body_text' => '¡Bienvenido {{user_name}}! Gracias por registrarte en {{app_name}}.',
                'variables' => ['user_name' => 'Nombre del usuario']
            ],
            'password_reset' => [
                'subject' => 'Restablecer contraseña - {{app_name}}',
                'body_html' => '<h1>Restablecer contraseña</h1><p>Haz clic en el enlace para restablecer tu contraseña: <a href="{{reset_url}}">Restablecer</a></p>',
                'body_text' => 'Restablecer contraseña. Visita: {{reset_url}}',
                'variables' => ['reset_url' => 'URL de restablecimiento']
            ],
            'notification' => [
                'subject' => 'Notificación: {{notification_title}}',
                'body_html' => '<h1>{{notification_title}}</h1><p>{{notification_message}}</p>',
                'body_text' => '{{notification_title}}: {{notification_message}}',
                'variables' => [
                    'notification_title' => 'Título de la notificación',
                    'notification_message' => 'Mensaje de la notificación'
                ]
            ]
        ];

        $templateData = $templates[$name] ?? $templates['notification'];

        return self::create([
            'name' => $name,
            'subject' => $templateData['subject'],
            'body_html' => $templateData['body_html'],
            'body_text' => $templateData['body_text'],
            'variables' => $templateData['variables'],
            'category' => $category,
            'description' => "Plantilla de {$name}",
            'is_active' => true
        ]);
    }

    /**
     * Obtener estadísticas de uso
     */
    public function getUsageStats(): array
    {
        // Esto se puede implementar con una tabla de logs de emails
        return [
            'total_sent' => 0,
            'last_sent' => null,
            'success_rate' => 0
        ];
    }

    /**
     * Duplicar plantilla
     */
    public function duplicate(string $newName): self
    {
        return self::create([
            'name' => $newName,
            'subject' => $this->subject,
            'body_html' => $this->body_html,
            'body_text' => $this->body_text,
            'variables' => $this->variables,
            'category' => $this->category,
            'description' => "Copia de {$this->name}",
            'is_active' => false
        ]);
    }

    /**
     * Obtener nombre de categoría formateado
     */
    public function getCategoryNameAttribute(): string
    {
        return match($this->category) {
            self::CATEGORY_AUTH => 'Autenticación',
            self::CATEGORY_NOTIFICATIONS => 'Notificaciones',
            self::CATEGORY_SYSTEM => 'Sistema',
            self::CATEGORY_MARKETING => 'Marketing',
            default => 'Otro'
        };
    }

    /**
     * Obtener badge de estado
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active
            ? '<span class="badge badge-success">Activa</span>'
            : '<span class="badge badge-secondary">Inactiva</span>';
    }

    /**
     * Obtener badge de categoría
     */
    public function getCategoryBadgeAttribute(): string
    {
        $colors = [
            self::CATEGORY_AUTH => 'primary',
            self::CATEGORY_NOTIFICATIONS => 'info',
            self::CATEGORY_SYSTEM => 'warning',
            self::CATEGORY_MARKETING => 'success'
        ];

        $color = $colors[$this->category] ?? 'secondary';
        return "<span class=\"badge badge-{$color}\">{$this->category_name}</span>";
    }
}



