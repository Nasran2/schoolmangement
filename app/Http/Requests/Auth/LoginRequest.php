<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'username' => (string) $this->string('username')->trim(),
            'password' => (string) $this->string('password')->trim(),
        ];

        $remember = $this->boolean('remember');

        // First, attempt with username
        $ok = Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']], $remember);

        // Fallback: if user typed email in the username field, attempt with email
        if (! $ok) {
            $ok = Auth::attempt(['email' => $credentials['username'], 'password' => $credentials['password']], $remember);
        }

        if (! $ok) {
            if (config('app.login_debug', env('LOGIN_DEBUG', false))) {
                try {
                    $user = \App\Models\User::query()
                        ->where('username', $credentials['username'])
                        ->orWhere('email', $credentials['username'])
                        ->first();
                    \Log::warning('Login failed', [
                        'provided_username' => $credentials['username'],
                        'user_found' => (bool) $user,
                        'using_email_fallback' => (bool) $user && $user->email === $credentials['username'],
                        'db_connection' => config('database.connections.'.config('database.default').'.database'),
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('Login debug error: '.$e->getMessage());
                }
            }
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }
}
