@php
    $user = Auth::user();
@endphp

<div class="hidden lg:block">
    <div class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
        <div class="flex items-center gap-3">
            <x-application-logo class="h-8 w-8 rounded-md object-cover" />
            <div>
                <div class="text-sm text-gray-600">Welcome,</div>
                <div class="text-lg font-semibold text-gray-900">{{ $user?->name }}</div>
            </div>
        </div>

        <div class="flex items-center gap-6">
            <div class="text-right">
                {{-- <div class="text-xs text-gray-600">Sri Lanka Time</div> --}}
                <div id="colombo_time" class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::now('Asia/Colombo')->format('d-m-Y H:i') }}</div>
            </div>
            <form method="POST" action="{{ route('academic-year.set') }}" class="flex items-center gap-2">
                @csrf
                <label for="academic_year" class="text-sm text-gray-600">Select Academic Year</label>
                <select
                    id="academic_year"
                    name="academic_year"
                    class="border-gray-300 rounded-md shadow-sm text-sm"
                    onchange="this.form.submit()"
                >
                    @foreach(($availableAcademicYears ?? []) as $year)
                        <option value="{{ $year }}" @selected(($selectedAcademicYear ?? '') === $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </form>



            <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:bg-gray-100" title="Notifications">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7"/>
                    <path d="M13.73 21a2 2 0 01-3.46 0"/>
                </svg>
            </button>

            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-gray-700">
                            {{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}
                        </span>
                        <span class="hidden xl:inline">{{ $user?->name }}</span>
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</div>

<script>
    (function() {
        const el = document.getElementById('colombo_time');
        if (!el) return;
        const formatter = new Intl.DateTimeFormat('en-GB', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit',
            hour12: false, timeZone: 'Asia/Colombo'
        });
        const startClient = Date.now();
        // Server timestamp in ms for accuracy across timezones
        const serverTs = {{ \Carbon\Carbon::now('Asia/Colombo')->getTimestampMs() }};
        function tick() {
            const elapsed = Date.now() - startClient;
            const d = new Date(serverTs + elapsed);
            el.textContent = formatter.format(d).replace(',', '');
        }
        tick();
        setInterval(tick, 1000);
    })();
</script>
