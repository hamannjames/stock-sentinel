<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <x-includes.frontend.html-head />
    <body
        x-data="{ degree: 0 }" 
        class="bg-gradient-to-r from-primary to-primary-dark min-h-screen"
        x-init="setInterval(function(){
            degree += 1
            if (degree > 365) {
                degree = 0
            }
        }, 200)"
        :style="`background:linear-gradient(${degree}deg, #064970, #3E88B3, #086aa3)`"
    >
        <x-includes.frontend.navigation />
        <div class="font-sans text-gray-900 antialiased">
            <header class="relative text-white flex flex-col justify-end text-center p-4 w-full min-h-screen-20 md:min-h-screen-25 mb-8">
                <h1 class="text-2xl md:text-4xl">{{ $heading }}</h1>
                @if (isset($subHeading))
                    <p class="text-md mt-2">{{ $subHeading }}</p>
                @endif
            </header>
            
            <main class="text-white text-xl">
                {{ $slot }}
            </main>
        </div>
        <x-includes.frontend.footer />
    </body>
</html>
