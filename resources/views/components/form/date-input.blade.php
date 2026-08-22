@props([
    'name' => '',
    'label' => '',
    'value' => '',
    'required' => false,
    'help' => '',
    'min' => null,
    'max' => null,
    'readonly' => false,
    'disabled' => false,
])

<div {{ $attributes->merge(['class' => 'date-input-field']) }}>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="date-input-control">
        <input type="date"
               id="{{ $name }}"
               name="{{ $name }}"
               value="{{ old($name, $value) }}"
               @if($required) required @endif
               @if($readonly) readonly @endif
               @if($disabled) disabled @endif
               @if($min !== null) min="{{ $min }}" @endif
               @if($max !== null) max="{{ $max }}" @endif
               class="date-input date-input--ready w-full @error($name) !border-red-400 @enderror">

        <i class="bi bi-calendar3 date-input-icon" aria-hidden="true"></i>
    </div>

    @if($help)
        <p class="mt-1.5 text-xs text-gray-500">{{ $help }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>