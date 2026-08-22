{{-- calendar_spec_day.blade.php --}}
<div class="overflow-x-auto shadow-lg rounded-lg max-h-[calc(100vh-200px)] overflow-y-auto">
    <table class="min-w-full border-collapse bg-white">
        <thead>
            <tr class="bg-gradient-to-r from-blue-600 to-blue-700">
                <th class="px-4 py-3 border-r border-blue-500 sticky left-0 top-0 bg-blue-600 z-20 min-w-[120px]">
                    <span class="text-white font-semibold text-sm uppercase">Lớp</span>
                </th>
                @for($period = 1; $period <= 9; $period++)
                    <th class="px-3 py-3 border-r border-blue-500 last:border-r-0 min-w-[180px] sticky top-0 bg-blue-700 z-10">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/20 backdrop-blur-sm">
                                <span class="text-white font-bold text-base">{{ $period }}</span>
                            </div>
                            <div class="text-blue-100 text-xs mt-1 font-medium">Tiết {{ $period }}</div>
                        </div>
                    </th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @foreach($periods as $index => $row)
                <tr class="hover:bg-blue-50/50 transition-colors duration-150 {{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50/50' }}">
                    <td class="border px-4 py-4 sticky left-0 {{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }} z-10">
                        <div class="flex items-center gap-2">
                            <div class="w-1 h-12 bg-gradient-to-b from-blue-500 to-blue-600 rounded-full"></div>
                            <div class="font-bold text-gray-800 text-sm">{{ $row['class_name'] }}</div>
                        </div>
                    </td>
                    @for($period = 1; $period <= 9; $period++)
                        @php $detail = $row['periods'][$period] ?? null; @endphp
                        <td class="border px-3 py-4 align-top">
                            @if($detail && $detail['subject_name'])
                                <div class="space-y-1.5">
                                    <div class="font-semibold text-sm leading-tight" style="color: {{ $detail['subject_color'] }}">
                                        {{ $detail['subject_name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $detail['subject_code'] }}
                                    </div>
                                    <div class="flex gap-1 mt-2">
                                        <span class="inline-block px-2 py-0.5 text-xs font-medium rounded 
                                            {{ $detail['lesson_type'] == 'Lý thuyết' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}"
                                            title="{{ $detail['lesson_type'] }}">
                                            {{ $detail['lesson_type'] == 'Lý thuyết' ? 'LT' : ($detail['lesson_type'] == 'Thực hành' ? 'TH' : 'Ôn luyện') }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-600 mt-1.5 flex gap-1">
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span>{{ $detail['instructor_name'] }}</span>
                                    </div>
                                    <div class="text-xs font-medium text-blue-600 mt-1 flex gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span>{{ $detail['classroom_name'] }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="text-gray-300 text-center py-6">
                                    <svg class="w-6 h-6 mx-auto opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                </div>
                            @endif
                        </td>
                    @endfor
                </tr>
            @endforeach
        </tbody>
    </table>
</div>