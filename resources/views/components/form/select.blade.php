@props([
    'name' => '',
    'label' => '',
    'value' => '',
    'options' => [],
    'placeholder' => 'Chọn...',
    'required' => false,
    'help' => '',
    'disabled' => false
])

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <select id="{{ $name }}"
            name="{{ $name }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error($name) border-red-300 @enderror @if($disabled) bg-gray-100 cursor-not-allowed @endif"
            {{ $attributes }}>

        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $key => $label)
            <option value="{{ $key }}" {{ old($name, $value) == $key ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>

    @if($help)
        <p class="mt-1 text-xs text-gray-500">{{ $help }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
