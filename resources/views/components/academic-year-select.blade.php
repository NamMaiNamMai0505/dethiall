@props([
    'name' => 'academic_year',
    'selected' => null,
    'value' => null,
    'label' => null,
    'required' => false,
    'placeholder' => 'Chọn năm học',
    'readonly' => false,
    'disabled' => false,
])

@php
    $selectedValue = $selected ?? $value;
    $academicYearOptions = \App\Support\AcademicYearCatalog::options();

    if ($selectedValue && ! array_key_exists($selectedValue, $academicYearOptions)) {
        $academicYearOptions = [$selectedValue => $selectedValue] + $academicYearOptions;
    }
@endphp

@if($label)
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
        {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
    </label>
@endif

<select
    id="{{ $attributes->get('id', $name) }}"
    name="{{ $name }}"
    {{ $required ? 'required' : '' }}
    {{ ($readonly || $disabled) ? 'disabled' : '' }}

    {{ $attributes->except('id')->merge(['class' => 'w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500']) }}
    title="{{ $readonly ? 'Không thể sửa vì đã có lịch chi tiết' : '' }}"
>
    <option value="">{{ $placeholder }}</option>
    @foreach($academicYearOptions as $key => $optionLabel)
        <option value="{{ $key }}" @selected((string) $selectedValue === (string) $key)>{{ $optionLabel }}</option>
    @endforeach
</select>
