@props([
    'name' => '',
    'label' => '',
    'value' => '1',
    'checked' => false,
    'help' => '',
    'disabled' => false
])

<div>
    <div class="flex items-center">
        <input type="checkbox"
               id="{{ $name }}"
               name="{{ $name }}"
               value="{{ $value }}"
               {{ old($name, $checked) ? 'checked' : '' }}
               @if($disabled) disabled @endif
               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 @if($disabled) cursor-not-allowed @endif"
               {{ $attributes }}>

        @if($label)
            <label class="ml-2 text-sm text-gray-700 @if($disabled) text-gray-400 @endif" for="{{ $name }}">
                {{ $label }}
            </label>
        @endif
    </div>

    @if($help)
        <p class="mt-1 text-xs text-gray-500">{{ $help }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
