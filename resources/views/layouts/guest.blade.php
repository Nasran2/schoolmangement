<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @php($loginBg = app('settings')->get('ui.login.background'))
        @php($loginBgUrl = $loginBg ? url('/storage/'.$loginBg) : null)
        <div class="relative min-h-screen flex items-center justify-center overflow-hidden" style="{{ $loginBgUrl ? 'background-image:url('.$loginBgUrl.');background-size:cover;background-position:center;' : '' }}">
            @if(!$loginBgUrl)
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-purple-600 to-blue-600"></div>
            @endif
            <!-- Dark overlay for readability -->
            <div class="absolute inset-0 bg-black/30 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-lg">
                <div class="mx-auto mb-6 flex flex-col items-center text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white/20 backdrop-blur">
                        <x-application-logo class="h-10 w-10 text-white" />
                    </div>
                    <div class="mt-4 text-3xl font-bold text-white tracking-tight">
                        {{ $schoolName ?? config('app.name') }}
                    </div>
                    <div class="mt-1 text-sm text-white/80">School Fee Management System</div>
                </div>

                <div class="rounded-2xl bg-white/95 shadow-xl backdrop-blur">
                    <div class="px-6 py-6 sm:px-8 sm:py-8">
                        {{ $slot }}
                        <div class="mt-6 flex items-center justify-center text-[11px] text-gray-500">
                            <span class="inline-flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3 w-3 text-emerald-600"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm-.75 14.25l-3.5-3.5 1.5-1.5 2 2 4.25-4.25 1.5 1.5-5.75 5.75z"/></svg>
                                Secure & Encrypted Connection
                            </span>
                            <span class="mx-2">•</span>
                            <span>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
