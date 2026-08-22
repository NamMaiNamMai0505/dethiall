@props([
    'name' => '',
    'label' => '',
    'value' => '',
    'required' => false,
    'help' => '',
])

<div {{ $attributes }}>
    @if($label)
        <label for="{{ $name }}" class="mb-2 block font-medium text-slate-800">
            {{ $label }}
            @if($required)
                <span class="text-rose-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <input type="text"
               id="{{ $name }}"
               name="{{ $name }}"
               value="{{ old($name, $value) }}"
               autocomplete="off"
               @if($required) required @endif
               class="flatpickr-date w-full border border-slate-200 rounded-xl text-sm pl-3 pr-9 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none @error($name) !border-rose-400 @enderror">
        <i class="bi bi-calendar3 pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
    </div>

    @if($help)
        <p class="mt-1 text-xs text-slate-500">{{ $help }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
    @enderror
</div>
