<x-guest-layout>
    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Only Admin</h2>
        <p class="mt-1 text-sm text-gray-600">Enter your 4-digit PIN to manage the system lock.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-xl border bg-white p-4">
            <div class="text-sm font-semibold text-gray-900">School Details</div>
            <div class="mt-3 space-y-1 text-sm text-gray-700">
                <div class="font-medium">{{ $school_name }}</div>
                @if (!empty($school_address))
                    <div class="whitespace-pre-line text-gray-600">{{ $school_address }}</div>
                @endif
                <div class="flex flex-wrap gap-x-3 gap-y-1 text-gray-600">
                    @if (!empty($school_phone))
                        <span>Phone: {{ $school_phone }}</span>
                    @endif
                    @if (!empty($school_email))
                        <span>Email: {{ $school_email }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-xl border bg-white p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-gray-900">System Lock</div>
                    <div class="mt-1 text-xs text-gray-500">When ON, everyone sees the Error 521 screen (except this secret admin link).</div>
                </div>
                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $system_lock_enabled ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                    {{ $system_lock_enabled ? 'ON' : 'OFF' }}
                </span>
            </div>

            <div class="mt-4 flex flex-col gap-2">
                <form method="POST" action="{{ route('onlyadmin.system_lock') }}">
                    @csrf
                    <input type="hidden" name="enabled" value="1">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50" {{ !$onlyadmin_unlocked ? 'disabled' : '' }}>
                        Turn ON (Show 521)
                    </button>
                </form>
                <form method="POST" action="{{ route('onlyadmin.system_lock') }}">
                    @csrf
                    <input type="hidden" name="enabled" value="0">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50" {{ !$onlyadmin_unlocked ? 'disabled' : '' }}>
                        Turn OFF (Normal System)
                    </button>
                </form>
            </div>

            @if (!$onlyadmin_unlocked)
                <div class="mt-3 text-xs text-amber-700">Locked: enter PIN to use these controls.</div>
            @else
                <form method="POST" action="{{ route('onlyadmin.logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-gray-600 hover:text-gray-800">Lock this admin session</button>
                </form>
            @endif
        </div>
    </div>

    <div class="mt-6 rounded-xl border bg-white p-4">
        <div class="text-sm font-semibold text-gray-900">Change PIN</div>
        <p class="mt-1 text-xs text-gray-500">Default PIN is <span class="font-mono">1234</span> (change it now).</p>

        <form method="POST" action="{{ route('onlyadmin.pin') }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
            @csrf

            <div>
                <label class="text-xs font-medium text-gray-700">Current PIN</label>
                <input name="current_pin" inputmode="numeric" pattern="[0-9]*" maxlength="4" class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="••••" {{ !$onlyadmin_unlocked ? 'disabled' : '' }}>
                @error('current_pin')
                    <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-gray-700">New PIN</label>
                <input name="new_pin" inputmode="numeric" pattern="[0-9]*" maxlength="4" class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="••••" {{ !$onlyadmin_unlocked ? 'disabled' : '' }}>
                @error('new_pin')
                    <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-gray-700">Confirm New PIN</label>
                <input name="new_pin_confirmation" inputmode="numeric" pattern="[0-9]*" maxlength="4" class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="••••" {{ !$onlyadmin_unlocked ? 'disabled' : '' }}>
                @error('new_pin_confirmation')
                    <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                @enderror
            </div>

            <div class="sm:col-span-3">
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50" {{ !$onlyadmin_unlocked ? 'disabled' : '' }}>
                    Update PIN
                </button>
            </div>
        </form>

        @error('pin')
            <div class="mt-3 text-xs text-red-600">{{ $message }}</div>
        @enderror
    </div>

    <div class="mt-6 rounded-xl border bg-white p-4">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-sm font-semibold text-gray-900">Route cache</div>
                <div class="mt-1 text-xs text-gray-500">Refresh cached routes after deploying route changes or to fix GET/HEAD mismatches without shell access.</div>
            </div>
            @if (!$onlyadmin_unlocked)
                <span class="text-xs font-medium text-amber-600">Unlock first</span>
            @endif
        </div>

        @if (\Illuminate\Support\Facades\Route::has('onlyadmin.cache.routes'))
            <form method="POST" action="{{ route('onlyadmin.cache.routes') }}" class="mt-4">
        @else
            <form method="GET" action="{{ route('onlyadmin.index') }}" class="mt-4">
                <input type="hidden" name="cache_routes" value="1">
        @endif
            @csrf
            <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50" {{ !$onlyadmin_unlocked ? 'disabled' : '' }}>
                Rebuild route cache
            </button>
        </form>
    </div>

    <div x-data="{
            open: {{ $onlyadmin_unlocked ? 'false' : 'true' }},
            digits: ['', '', '', ''],
            get pin() { return this.digits.join(''); },
            focus(i) { this.$nextTick(() => this.$refs['d'+i]?.focus()); },
            onInput(i, e) {
                const v = (e.target.value || '').replace(/\D/g, '').slice(-1);
                this.digits[i] = v;
                if (v && i < 3) this.focus(i+1);
            },
            onBack(i, e) {
                if (e.key !== 'Backspace') return;
                if (this.digits[i]) { this.digits[i] = ''; return; }
                if (i > 0) this.focus(i-1);
            },
            submit() {
                if (this.pin.length !== 4) return;
                this.$refs.pin.value = this.pin;
                this.$refs.form.submit();
            }
        }">
        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-cloak>
            <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl">
                <div class="text-base font-semibold text-gray-900">Enter 4-digit PIN</div>
                <div class="mt-1 text-xs text-gray-500">This is required to access system lock controls.</div>

                <form method="POST" action="{{ route('onlyadmin.unlock') }}" class="mt-4" x-ref="form" x-on:submit.prevent="submit()">
                    @csrf
                    <input type="hidden" name="pin" x-ref="pin">

                    <div class="flex items-center justify-between gap-2">
                        <template x-for="(d, i) in digits" :key="i">
                            <input
                                :ref="'d'+i"
                                type="password"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="1"
                                class="h-12 w-12 rounded-md border-gray-300 text-center text-lg tracking-widest focus:border-indigo-500 focus:ring-indigo-500"
                                x-model="digits[i]"
                                x-on:input="onInput(i, $event)"
                                x-on:keydown="onBack(i, $event)"
                            />
                        </template>
                    </div>

                    @if ($errors->has('pin'))
                        <div class="mt-3 text-xs text-red-600">{{ $errors->first('pin') }}</div>
                    @endif

                    <button type="submit" class="mt-4 inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Unlock
                    </button>

                    <div class="mt-3 text-center">
                        <a href="{{ route('login') }}" class="text-xs font-medium text-gray-600 hover:text-gray-800">Go to login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
