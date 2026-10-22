@props(['name', 'label', 'value' => null, 'type' => 'text', 'rows' => 3])
@php($fieldId = 'field_'.str_replace(['[', ']', '.'], '_', $name))
@php($inputValue = is_scalar(old($name, $value)) ? old($name, $value) : '')
<div>
    <label for="{{ $fieldId }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @if($type === 'textarea')
        <textarea id="{{ $fieldId }}" name="{{ $name }}" rows="{{ $rows }}" {{ $attributes->class(['w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }}>{{ $inputValue }}</textarea>
    @else
        <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" value="{{ $inputValue }}" {{ $attributes->class(['w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }}>
    @endif
    @error($name)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
