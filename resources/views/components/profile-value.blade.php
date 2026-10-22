@props(['label', 'value' => null])
<div class="min-w-0">
    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</dt>
    <dd class="mt-1.5 text-sm leading-6 break-words whitespace-pre-line {{ $value === null || $value === '' ? 'text-gray-400' : 'text-gray-900' }}">{{ $value instanceof \Carbon\CarbonInterface ? $value->format('d M Y') : ($value === null || $value === '' ? 'Not provided' : $value) }}</dd>
</div>
