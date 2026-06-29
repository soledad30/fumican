<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ url('/') }}">
    <meta name="app-base-path" content="{{ config('app.base_path') }}">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/1d8ccf6f72.js" crossorigin="anonymous"></script>

    <!-- Scripts -->
    @routes
    <script>
        (function () {
            var appUrl = @json(url('/'));
            if (typeof Ziggy !== 'undefined') {
                Ziggy.url = appUrl;
            }
            window.Ziggy = window.Ziggy || (typeof Ziggy !== 'undefined' ? Ziggy : {});
            window.Ziggy.url = appUrl;
        })();
    </script>
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
