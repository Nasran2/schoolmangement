<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 text-center">
        <h2 class="text-xl font-semibold text-gray-900">Welcome to the {{ $schoolName ?? config('app.name') }} Portal</h2>
        <p class="mt-1 text-sm text-gray-600">Sign in to manage your school fees and finances</p>
    </div>

    <form method="POST" action="{{ route('login') }}" x-data="{ show: false }">
        @csrf

        <div class="space-y-4">
            <div>
                <label for="username" class="text-xs font-medium text-gray-700">USERNAME</label>
                <div class="mt-1 relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zm-7 9a7 7 0 1114 0H5z"/></svg>
                    </span>
                    <input id="username" name="username" type="text" autocomplete="username" required autofocus placeholder="Enter your username" class="block w-full rounded-md border-gray-300 pl-9 focus:border-indigo-500 focus:ring-indigo-500" value="{{ old('username') }}" />
                </div>
                <x-input-error :messages="$errors->get('username')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="text-xs font-medium text-gray-700">PASSWORD</label>
                <div class="mt-1 relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M12 1a5 5 0 00-5 5v3H6a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2v-8a2 2 0 00-2-2h-1V6a5 5 0 00-5-5zm-3 8V6a3 3 0 116 0v3H9z"/></svg>
                    </span>
                    <input :type="show ? 'text' : 'password'" id="password" name="password" autocomplete="current-password" required placeholder="Enter your password" class="block w-full rounded-md border-gray-300 pl-9 pr-9 focus:border-indigo-500 focus:ring-indigo-500" />
                    <button type="button" class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-600" x-on:click="show = !show" aria-label="Toggle password visibility">
                        <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0112 19c-7 0-11-7-11-7a20.28 20.28 0 015.06-5.94M9.9 4.24A10.94 10.94 0 0112 5c7 0 11 7 11 7a20.28 20.28 0 01-3.1 4.19"/><path d="M1 1l22 22"/></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                    <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                </label>
                @if (Route::has('password.request'))
                    <a class="text-sm text-indigo-600 hover:text-indigo-800" href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
                @endif
            </div>

            <button type="submit" class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-md bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-2 text-white shadow hover:from-indigo-700 hover:to-purple-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                <span>{{ __('Sign in') }}</span>
            </button>

            <p class="mt-4 text-center text-[11px] text-gray-500">By continuing you agree to our terms and conditions</p>
        </div>
    </form>
</x-guest-layout>
