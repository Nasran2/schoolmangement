<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $schoolName ?? config('app.name', 'Laravel') }}</title>

        @if($logo = app('settings')->get('school.logo'))
            <link rel="icon" href="{{ Storage::url($logo) }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.sidebar')

            <div class="lg:pl-64">
                @include('layouts.topbar')

                <main class="pt-14 lg:pt-0">
                    @isset($header)
                        <div class="bg-white border-b border-gray-200">
                            <div class="px-4 sm:px-6 lg:px-8 py-4">
                                {{ $header }}
                            </div>
                        </div>
                    @endisset

                    <div class="px-4 sm:px-6 lg:px-8">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>
