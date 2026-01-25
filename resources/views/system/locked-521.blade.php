<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error 521 - Web server is down</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-800">
    <div class="mx-auto max-w-4xl px-4 py-10 sm:py-16">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-5xl font-light tracking-tight text-gray-900">Error 521</div>
                <div class="mt-2 text-2xl font-light text-gray-500">Web server is down</div>
                <div class="mt-4 text-sm text-gray-500">
                    <span class="font-medium text-gray-700">Host:</span> {{ $host ?? request()->getHost() }}
                    <span class="mx-2">•</span>
                    <span class="font-medium text-gray-700">Time:</span> {{ now()->utc()->format('Y-m-d H:i:s') }} UTC
                </div>
            </div>
        </div>

        <div class="mt-10 rounded-lg border bg-white p-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div class="flex flex-col items-center justify-center text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-emerald-600"><path d="M9 12.75L11.25 15 15 9.75"/><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm0 1.5a8.25 8.25 0 100 16.5 8.25 8.25 0 000-16.5z" clip-rule="evenodd"/></svg>
                    </div>
                    <div class="mt-3 text-sm font-medium">You</div>
                    <div class="text-xs text-emerald-600">Browser Working</div>
                </div>

                <div class="flex flex-col items-center justify-center text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-emerald-600"><path d="M4.5 10.5a7.5 7.5 0 0114.91-1.26A5.25 5.25 0 0118 19.5H7.125A4.875 4.875 0 016.375 9.75c.37 0 .73.04 1.08.114A7.46 7.46 0 014.5 10.5z"/></svg>
                    </div>
                    <div class="mt-3 text-sm font-medium">Cloudflare</div>
                    <div class="text-xs text-emerald-600">Working</div>
                </div>

                <div class="flex flex-col items-center justify-center text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-red-600"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm3.53 6.22a.75.75 0 010 1.06L13.06 12l2.47 2.47a.75.75 0 11-1.06 1.06L12 13.06l-2.47 2.47a.75.75 0 11-1.06-1.06L10.94 12 8.47 9.53a.75.75 0 111.06-1.06L12 10.94l2.47-2.47a.75.75 0 011.06 0z" clip-rule="evenodd"/></svg>
                    </div>
                    <div class="mt-3 text-sm font-medium">Host</div>
                    <div class="text-xs text-red-600">Error</div>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-8 sm:grid-cols-2">
                <div>
                    <div class="text-base font-semibold">What happened?</div>
                    <p class="mt-2 text-sm text-gray-600">The web server is not returning a connection. As a result, the web page is not displaying.</p>
                </div>
                <div>
                    <div class="text-base font-semibold">What can I do?</div>
                    <p class="mt-2 text-sm text-gray-600"><span class="font-medium">If you are a visitor of this website:</span> Please try again in a few minutes.</p>
                    <p class="mt-2 text-sm text-gray-600"><span class="font-medium">If you are the owner of this website:</span> Access the secret admin link to unlock the system.</p>
                    <div class="mt-3">
                        <a href="{{ url('/onlyadmin') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Go to secret admin</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 text-center text-xs text-gray-500">
            Ray ID: <span class="font-mono">{{ substr(sha1(($host ?? request()->getHost()).'|'.now()->timestamp), 0, 16) }}</span>
        </div>
    </div>
</body>
</html>
