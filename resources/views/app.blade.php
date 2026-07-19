<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Pulseboard') }}</title>

        {{-- Apply the saved theme before paint to avoid a flash of the wrong theme. --}}
        <script>
            try {
                document.documentElement.dataset.theme =
                    localStorage.getItem('pb-theme') === 'light' ? 'light' : 'dark';
            } catch (e) {
                document.documentElement.dataset.theme = 'dark';
            }
        </script>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link
            href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap"
            rel="stylesheet"
        >

        @vite('resources/js/app.ts')
    </head>
    <body class="antialiased">
        <div id="app"></div>
    </body>
</html>
