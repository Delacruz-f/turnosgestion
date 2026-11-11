<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0E2F2B">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">

        <!-- Estilos del sistema de turnos -->
        <link rel="stylesheet" href="{{ asset('css/turnos.css') }}">

        <!-- Scripts -->
        @vite(['resources/js/app.js'])
    </head>
    <body class="auth">
        <main class="auth-main">
            <div class="auth-card">
                <div class="auth-card__brand">
                    <span class="brand__mark" aria-hidden="true">T</span>
                    <span class="brand__text">
                        <span class="brand__name">{{ config('app.name', 'Laravel') }}</span>
                        <span class="brand__sub">Atención al ciudadano</span>
                    </span>
                </div>

                {{ $slot }}
            </div>
        </main>
    </body>
</html>
