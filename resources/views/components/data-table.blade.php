@props([
    'headers' => [],
    'data' => [],
    'actions' => [],
    'sortable' => true,
    'hoverable' => true
])

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if(count($data) > 0)
        <table class="w-full">
            <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                <tr>
                    @foreach($headers as $header)
                        <th class="px-4 py-3 text-left font-medium">
                            @if(isset($header['sortable']) && $header['sortable'] && $sortable)
                                <a href="{{ isset($header['sort_url']) ? $header['sort_url'] : '#' }}"
                                   class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                                    {{ $header['label'] }}
                                    @if(isset($header['sort_direction']))
                                        <i class="bi bi-arrow-{{ $header['sort_direction'] == 'asc' ? 'up' : 'down' }} ml-1"></i>
                                    @endif
                                </a>
                            @else
                                {{ $header['label'] }}
                            @endif
                        </th>
                    @endforeach
                    @if(count($actions) > 0)
                        <th class="px-4 py-3 text-left font-medium">Thao tác</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($data as $row)
                    <tr class="{{ $hoverable ? 'hover:bg-gray-50' : '' }}">
                        @foreach($headers as $header)
                            <td class="px-4 py-3">
                                @if(isset($header['key']))
                                    @if(isset($header['type']))
                                        {{-- Handle different cell types --}}
                                        @if($header['type'] === 'badge')
                                            <x-custom-badge
                                                :type="$row[$header['key']]"
                                                :text="$row[$header['text_key'] ?? $header['key']]" />
                                        @elseif($header['type'] === 'status-badge')
                                            <x-status-badge :is-active="$row[$header['key']]" />
                                        @elseif($header['type'] === 'level-badge')
                                            <x-level-badge
                                                :level="$row[$header['key']]"
                                                :text="$row[$header['text_key'] ?? $header['key']]" />
                                        @elseif($header['type'] === 'code')
                                            <code class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-mono">
                                                {{ $row[$header['key']] }}
                                            </code>
                                        @elseif($header['type'] === 'html')
                                            {!! $row[$header['key']] !!}
                                        @else
                                            {{ $row[$header['key']] }}
                                        @endif
                                    @else
                                        {{ $row[$header['key']] }}
                                    @endif
                                @endif
                            </td>
                        @endforeach

                        @if(count($actions) > 0)
                            <td class="px-4 py-3">
                                <div class="action-icons flex space-x-2">
                                    @foreach($actions as $action)
                                        @if($action['type'] === 'link')
                                            <a href="{{ str_replace('{id}', $row['id'], $action['url']) }}"
                                               class="action-icon text-{{ $action['color'] ?? 'blue' }}-600 hover:text-{{ $action['color'] ?? 'blue' }}-800"
                                               title="{{ $action['title'] ?? '' }}">
                                                <i class="bi bi-{{ $action['icon'] }}"></i>
                                            </a>
                                        @elseif($action['type'] === 'button')
                                            <button type="button"
                                                    onclick="{{ str_replace('{id}', $row['id'], $action['onclick'] ?? '') }}"
                                                    class="action-icon text-{{ $action['color'] ?? 'blue' }}-600 hover:text-{{ $action['color'] ?? 'blue' }}-800"
                                                    title="{{ $action['title'] ?? '' }}">
                                                <i class="bi bi-{{ $action['icon'] }}"></i>
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="text-center py-12">
            <i class="bi bi-inbox text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Không có dữ liệu</h3>
            <p class="text-gray-500">Chưa có bản ghi nào được tìm thấy.</p>
        </div>
    @endif
</div>
