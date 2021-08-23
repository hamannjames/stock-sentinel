<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-seo::meta />

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('/static/images/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('/static/images/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ url('/static/images/favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ url('/static/images/favicon/site.webmanifest') }}">

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <livewire:styles />

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
</head><!-- end html-head -->