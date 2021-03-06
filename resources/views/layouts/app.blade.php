<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <x-includes.dashboard.html-head />
    
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <x-includes.frontend.navigation />

            <!-- Page Heading -->
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <x-includes.dashboard.footer />
    </body>
</html>
