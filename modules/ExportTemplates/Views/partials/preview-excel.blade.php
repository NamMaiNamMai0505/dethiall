<div class="border-b bg-slate-50 px-4 py-2">
    <div class="flex flex-wrap gap-2">
        @foreach($preview['sheets'] as $sheetName)
            <a href="{{ request()->fullUrlWithQuery(['preview_sheet' => $sheetName]) }}"
               class="rounded-md px-3 py-1 text-xs font-bold {{ $preview['active_sheet'] === $sheetName ? 'bg-emerald-600 text-white' : 'border bg-white text-slate-600' }}">
                {{ $sheetName }}
            </a>
        @endforeach
    </div>
</div>
@if($preview['truncated'])
    <div class="border-b border-amber-200 bg-amber-50 px-4 py-2 text-xs text-amber-800">
        Preview được giới hạn 120 dòng và 50 cột để bảo đảm hiệu năng. File xuất không bị giới hạn.
    </div>
@endif
<div class="max-h-[62vh] overflow-auto bg-slate-200 p-4">
    <table class="border-collapse bg-white text-xs shadow-lg">
        <colgroup>
            <col style="width:42px">
            @foreach($preview['columns'] as $column)
                @php
                    $columnPixels = min(280, max(52, ((float) ($column['width'] ?: 10)) * 6));
                @endphp
                <col style="width:{{ $columnPixels }}px; min-width:{{ $columnPixels }}px">
            @endforeach
        </colgroup>
        <thead class="sticky top-0 z-20">
        <tr>
            <th class="border border-slate-300 bg-slate-100 px-2 py-1 text-slate-500"></th>
            @foreach($preview['columns'] as $column)
                <th class="border border-slate-300 bg-slate-100 px-2 py-1 font-bold text-slate-600">{{ $column['letter'] }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach($preview['rows'] as $row)
            <tr style="{{ $row['height'] ? 'height:'.max(18, (float) $row['height']).'px' : '' }}">
                <th class="sticky left-0 z-10 border border-slate-300 bg-slate-100 px-2 py-1 text-right font-normal text-slate-500">
                    {{ $row['number'] }}
                </th>
                @foreach($row['cells'] as $cell)
                    @php
                        $isSelected = $cell['target_ref'] && $cell['target_ref'] === $selectedRef;
                        $targetClasses = $cell['target_ref']
                            ? 'preview-target cursor-pointer hover:ring-2 hover:ring-inset hover:ring-blue-400'
                            : '';
                    @endphp
                    <td rowspan="{{ $cell['rowspan'] }}"
                        colspan="{{ $cell['colspan'] }}"
                        data-target-ref="{{ $cell['target_ref'] }}"
                        data-address="{{ $cell['address'] }}"
                        class="relative min-h-6 border border-slate-300 px-1.5 py-1 align-middle {{ $targetClasses }} {{ $cell['bound'] ? 'bg-emerald-50' : '' }} {{ $isSelected ? 'ring-2 ring-inset ring-blue-600' : '' }}"
                        style="{{ $cell['css'] }}">
                        <span class="preview-value whitespace-pre-wrap">{{ $cell['value'] }}</span>
                        @if($cell['bound'])
                            <span class="absolute right-0 top-0 h-0 w-0 border-l-[7px] border-t-[7px] border-l-transparent border-t-emerald-500"
                                  title="{{ $cell['data_key'] }}"></span>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if(!empty($preview['regions']))
    <div class="border-t bg-white p-3">
        <div class="mb-2 text-xs font-bold uppercase text-slate-400">Vùng bảng / merge / named range</div>
        <div class="flex flex-wrap gap-2">
            @foreach($preview['regions'] as $region)
                <button type="button"
                        data-target-ref="{{ $region['ref'] }}"
                        class="preview-target rounded-lg border px-3 py-2 text-left text-xs hover:border-blue-400 {{ $region['bound'] ? 'border-emerald-300 bg-emerald-50' : 'bg-slate-50' }}">
                    <b>{{ $region['kind'] }}</b> · {{ $region['label'] }}
                    @if($region['data_key'])
                        <code class="mt-1 block text-emerald-700">{{ $region['data_key'] }}</code>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
@endif
