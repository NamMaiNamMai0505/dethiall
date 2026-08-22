@props([
    'name' => 'instructor_id',
    'label' => 'Giảng viên',
    'instructors' => collect(),
    'value' => null,
    'required' => false,
    'placeholder' => 'Tìm và chọn giảng viên...',
    'help' => null,
    'readonly' => false,
    'readonlyLabel' => null,
    'disabled' => false,
])

@php
    $selectedValue = old($name, $value);
@endphp

<div {{ $attributes->merge(['class' => 'instructor-select-field']) }}>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    @if($readonly && $readonlyLabel)
        <input type="hidden" id="{{ $name }}" name="{{ $name }}" value="{{ $selectedValue }}">
        <div class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-gray-50 text-gray-800 text-sm">{{ $readonlyLabel }}</div>
    @else
        <select id="{{ $name }}" name="{{ $name }}"
                data-instructor-select
                data-placeholder="{{ $placeholder }}"
                placeholder="{{ $placeholder }}"
                @if($required) required @endif
                @if($disabled) disabled @endif
                class="w-full @error($name) border-red-300 @enderror">
            <option value="">{{ $placeholder }}</option>
            @foreach($instructors as $instructor)
                <option value="{{ $instructor->id }}"
                        data-name="{{ $instructor->name }}"
                        data-code="{{ $instructor->code }}"
                        data-unit="{{ $instructor->unit->name ?? '' }}"
                        data-unit-id="{{ $instructor->unit_id ?? '' }}"
                        {{ (string) $selectedValue === (string) $instructor->id ? 'selected' : '' }}>
                    {{ $instructor->name }}
                </option>
            @endforeach
        </select>
    @endif

    @if($help)
        <p class="mt-1.5 text-xs text-gray-500">{{ $help }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
