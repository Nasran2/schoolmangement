@props(['name' => 'document'])
<svg {{ $attributes->class(['profile-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('person')<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0116 0v2"/>@break
        @case('book')<path d="M12 5c-3-2-6-2-9-1v15c3-1 6-1 9 1 3-2 6-2 9-1V4c-3-1-6-1-9 1v15"/>@break
        @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2"/>@break
        @case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>@break
        @case('phone')<path d="m5 3 4 1 1 5-3 2a16 16 0 006 6l2-3 5 1 1 4c0 1-1 2-2 2A18 18 0 013 5c0-1 1-2 2-2z"/>@break
        @case('location')<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1116 0z"/><circle cx="12" cy="10" r="2.5"/>@break
        @case('check')<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>@break
        @case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
        @case('download')<path d="M12 3v12m-4-4 4 4 4-4M4 16v4a1 1 0 001 1h14a1 1 0 001-1v-4"/>@break
        @case('arrow')<path d="M4 12h16m-6-6 6 6-6 6"/>@break
        @case('chevron')<path d="m9 5 7 7-7 7"/>@break
        @case('plus')<path d="M12 5v14M5 12h14"/>@break
        @case('lock')<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3M12 15v2"/>@break
        @default<path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9zM14 3v6h6M8 13h8M8 17h5"/>
    @endswitch
</svg>
