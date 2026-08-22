@php
    $layoutInfo = $preview['layout'] ?? [];
    $landscape = ($layoutInfo['orientation'] ?? '') === 'landscape';
@endphp
<div class="max-h-[68vh] overflow-auto bg-slate-300 p-6">
    <div class="mx-auto bg-white shadow-xl {{ $landscape ? 'max-w-[1120px] min-h-[760px]' : 'max-w-[800px] min-h-[1050px]' }}"
         style="padding:36px">
        @foreach($preview['parts'] as $part)
            <section class="{{ $part['type'] === 'header' ? 'mb-5 border-b pb-3' : ($part['type'] === 'footer' ? 'mt-5 border-t pt-3' : 'my-3') }}">
                <div class="mb-2 text-[10px] font-bold uppercase tracking-wide text-slate-300">
                    {{ $part['type'] }}
                </div>
                @foreach($part['paragraphs'] as $paragraph)
                    @php($isSelected = $paragraph['target_ref'] && $paragraph['target_ref'] === $selectedRef)
                    <p data-target-ref="{{ $paragraph['target_ref'] }}"
                       class="mb-2 min-h-5 whitespace-pre-wrap rounded px-1 {{ $paragraph['target_ref'] ? 'preview-target cursor-pointer hover:ring-2 hover:ring-blue-300' : '' }} {{ $paragraph['bound'] ? 'bg-emerald-50' : '' }} {{ $isSelected ? 'ring-2 ring-blue-600' : '' }}"
                       style="{{ $paragraph['css'] }}">
                        <span class="preview-value">{{ $paragraph['value'] }}</span>
                    </p>
                @endforeach

                @foreach($part['tables'] as $table)
                    <table data-target-ref="{{ $table['target_ref'] }}"
                           class="preview-target my-3 w-full border-collapse text-sm">
                        @foreach($table['rows'] as $row)
                            <tr style="{{ $row['height'] ? 'height:'.((float) $row['height'] / 20).'pt' : '' }}">
                                @foreach($row['cells'] as $cell)
                                    @php($isSelected = $cell['target_ref'] === $selectedRef)
                                    <td colspan="{{ $cell['colspan'] }}"
                                        data-target-ref="{{ $cell['target_ref'] }}"
                                        class="preview-target cursor-pointer border border-slate-600 px-2 py-1 hover:ring-2 hover:ring-inset hover:ring-blue-300 {{ $cell['bound'] ? 'bg-emerald-50' : '' }} {{ $isSelected ? 'ring-2 ring-inset ring-blue-600' : '' }}"
                                        style="{{ $cell['css'] }}">
                                        <span class="preview-value">{{ $cell['value'] }}</span>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </table>
                @endforeach

                @if(!empty($part['controls']))
                    <div class="my-3 grid gap-2 md:grid-cols-2">
                        @foreach($part['controls'] as $control)
                            <button type="button"
                                    data-target-ref="{{ $control['ref'] }}"
                                    class="preview-target rounded border border-dashed px-3 py-2 text-left text-xs hover:border-blue-500 {{ $control['bound'] ? 'border-emerald-400 bg-emerald-50' : 'border-slate-300' }}">
                                <b>{{ $control['kind'] }}</b> · {{ $control['label'] }}
                                @if($control['data_key'])
                                    <code class="mt-1 block text-emerald-700">{{ $control['data_key'] }}</code>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</div>
