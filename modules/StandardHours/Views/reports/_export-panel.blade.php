@props([
    'exportRoute',
    'exportQuery' => [],
    'units' => [],
    'showPrint' => false,
    'printRoute' => null,
    'printQuery' => [],
    'hint' => null,
    'panelId' => null,
])

@php
    $panelId = $panelId ?: 'export-panel-'.uniqid();
    $exportLevel = old('export_level', request('export_level', \Modules\StandardHours\Support\ReportDocumentLayout::LEVEL_PERSONAL));
    $exportLevel = \Modules\StandardHours\Support\ReportDocumentLayout::normalizeLevel($exportLevel);
    $selectedUnitIds = old('unit_ids', request('unit_ids', request('unit_id') ? [request('unit_id')] : []));
    if (! is_array($selectedUnitIds)) {
        $selectedUnitIds = $selectedUnitIds !== null && $selectedUnitIds !== '' ? [$selectedUnitIds] : [];
    }
    $selectedUnitIds = array_map('strval', $selectedUnitIds);
    // Không có chọn sẵn → mặc định chọn hết khoa (giống xuất lịch / ngành)
    $selectAllUnits = $selectedUnitIds === [];
@endphp

<div id="{{ $panelId }}" class="bg-white rounded-lg shadow-sm border p-4 mb-6 report-export-panel"
     data-export-panel>
    <form method="GET" action="{{ $exportRoute }}" class="space-y-4">
        @foreach($exportQuery as $key => $value)
            @if(is_array($value))
                @foreach($value as $item)
                    @if($item !== null && $item !== '')
                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                    @endif
                @endforeach
            @elseif($value !== null && $value !== '')
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
            <div class="flex-1 space-y-3 min-w-0">
                <div>
                    <h3 class="font-semibold text-gray-900">Xuất báo cáo Excel</h3>
                    @if($hint)
                        <p class="text-sm text-gray-500 mt-1">{{ $hint }}</p>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-3xl">
                    <div>
                        <label for="{{ $panelId }}-level" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="bi bi-layers text-blue-600 mr-1"></i>
                            Cấp xuất
                        </label>
                        <div class="ui-select-field">
                            <select name="export_level"
                                    id="{{ $panelId }}-level"
                                    data-placeholder="Chọn cấp xuất..."
                                    data-searchable="0"
                                    class="w-full"
                                    data-export-level>
                                @foreach(\Modules\StandardHours\Support\ReportDocumentLayout::LEVELS as $value => $label)
                                    <option value="{{ $value }}" @selected($exportLevel === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Cá nhân: chi tiết GV · Khoa: chi tiết + tổng khoa · Trường: tổng khoa + tổng trường
                        </p>
                    </div>

                    <div data-unit-ids-wrap class="{{ $exportLevel === 'unit' ? '' : 'hidden' }}">
                        <label for="{{ $panelId }}-units" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="bi bi-building text-indigo-600 mr-1"></i>
                            Khoa / đơn vị
                            <span class="text-xs font-normal text-gray-500">
                                (<span data-unit-count>0</span>)
                            </span>
                        </label>
                        <div class="ui-select-field">
                            <select name="unit_ids[]"
                                    id="{{ $panelId }}-units"
                                    multiple
                                    data-placeholder="Chọn khoa / đơn vị..."
                                    data-searchable="1"
                                    class="w-full"
                                    data-export-units
                                    @disabled($exportLevel !== 'unit')>
                                @foreach($units as $id => $name)
                                    <option value="{{ $id }}"
                                        @selected($selectAllUnits || in_array((string) $id, $selectedUnitIds, true))>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Chọn nhiều khoa (chip). Có thể tìm nhanh trong khung.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 shrink-0">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    <i class="bi bi-file-earmark-excel"></i> Xuất Excel
                </button>

                @if($showPrint && $printRoute)
                    <a href="{{ route($printRoute, $printQuery) }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        <i class="bi bi-printer"></i> In
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>
