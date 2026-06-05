<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="WorkLog - Gestión de tiempo, tareas y sesiones de enfoque">
        <meta name="theme-color" content="#2563eb">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="WorkLog">
        <link rel="manifest" href="/manifest.json">
        <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 180 180'><text x='50%' y='50%' font-size='90' fill='%23000' text-anchor='middle' dominant-baseline='middle'>🍅</text></svg>">
        <link rel="apple-touch-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 180 180'><rect fill='%232563eb' width='180' height='180' rx='40'/><text x='90' y='90' font-size='120' fill='white' text-anchor='middle' dominant-baseline='middle'>🍅</text></svg>">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Dark mode script - runs BEFORE CSS loads to prevent flash -->
        <script>
            (function() {
                try {
                    const saved = localStorage.getItem('darkMode');
                    const prefersDark = saved !== null
                        ? saved === 'dark'
                        : window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                        document.documentElement.style.colorScheme = 'dark';
                    } else {
                        document.documentElement.classList.remove('dark');
                        document.documentElement.style.colorScheme = 'light';
                    }
                    console.log('🌙 Dark mode inline script executed:', prefersDark);
                } catch (e) {
                    console.error('🌙 Error in dark mode inline script:', e);
                }
            })();
        </script>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
