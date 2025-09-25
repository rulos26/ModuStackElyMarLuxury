<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Sitio en Mantenimiento' }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .maintenance-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            text-align: center;
            max-width: 600px;
            width: 90%;
            margin: 2rem;
        }

        .maintenance-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .maintenance-title {
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .maintenance-message {
            color: #5a6c7d;
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .progress-container {
            background: #e9ecef;
            border-radius: 10px;
            height: 8px;
            margin: 2rem 0;
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            border-radius: 10px;
            animation: progress 3s ease-in-out infinite;
        }

        @keyframes progress {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }

        .contact-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0.5rem 0;
            color: #5a6c7d;
        }

        .contact-item i {
            margin-right: 0.5rem;
            color: #667eea;
        }

        .retry-info {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        .social-links {
            margin-top: 2rem;
        }

        .social-links a {
            display: inline-block;
            width: 50px;
            height: 50px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            line-height: 50px;
            margin: 0 0.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: #764ba2;
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .maintenance-container {
                padding: 2rem 1.5rem;
            }

            .maintenance-title {
                font-size: 2rem;
            }

            .maintenance-message {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <i class="fas fa-tools"></i>
        </div>

        <h1 class="maintenance-title">{{ $title ?? 'Sitio en Mantenimiento' }}</h1>

        <p class="maintenance-message">
            {{ $message ?? 'Estamos realizando mejoras en nuestro sitio web para brindarte una mejor experiencia. Volveremos pronto con nuevas funcionalidades.' }}
        </p>

        <div class="progress-container">
            <div class="progress-bar"></div>
        </div>

        @if(isset($contact_info) && !empty($contact_info))
        <div class="contact-info">
            <h5><i class="fas fa-headset"></i> ¿Necesitas ayuda?</h5>

            @if(!empty($contact_info['email']))
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <a href="mailto:{{ $contact_info['email'] }}">{{ $contact_info['email'] }}</a>
            </div>
            @endif

            @if(!empty($contact_info['phone']))
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <a href="tel:{{ $contact_info['phone'] }}">{{ $contact_info['phone'] }}</a>
            </div>
            @endif

            @if(!empty($contact_info['support_url']))
            <div class="contact-item">
                <i class="fas fa-external-link-alt"></i>
                <a href="{{ $contact_info['support_url'] }}" target="_blank">Centro de Ayuda</a>
            </div>
            @endif
        </div>
        @endif

        @if(isset($retry_after) && $retry_after)
        <div class="retry-info">
            <i class="fas fa-clock"></i>
            Tiempo estimado de finalización: {{ $retry_after }} minutos
        </div>
        @endif

        <div class="social-links">
            <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
        </div>

        <div class="retry-info">
            <small>
                <i class="fas fa-info-circle"></i>
                Esta página se actualizará automáticamente cuando el sitio esté disponible nuevamente.
            </small>
        </div>
    </div>

    <!-- Auto-refresh cada 30 segundos -->
    <script>
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>



