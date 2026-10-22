@props(['name', 'label', 'active' => false, 'href' => null])
<div class="flex items-center rounded-lg {{ $active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
    @if($href)
        <a href="{{ $href }}" class="min-w-0 flex-1 flex items-center gap-3 px-3 py-2.5 text-sm font-semibold">{{ $slot }}<span>{{ $label }}</span></a>
    @else
        <button type="button" @click="menus.{{ $name }} = !menus.{{ $name }}" :aria-expanded="menus.{{ $name }}.toString()" class="min-w-0 flex-1 flex items-center gap-3 px-3 py-2.5 text-sm font-semibold">{{ $slot }}<span>{{ $label }}</span></button>
    @endif
    <button type="button" aria-label="Toggle {{ $label }} menu" @click="menus.{{ $name }} = !menus.{{ $name }}" :aria-expanded="menus.{{ $name }}.toString()" class="rounded-lg p-2.5 hover:bg-indigo-100/60">
        <svg class="h-4 w-4 transition-transform" :class="menus.{{ $name }} ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
    </button>
</div>
