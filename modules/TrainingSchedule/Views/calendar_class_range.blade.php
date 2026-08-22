<div class="overflow-x-auto shadow-lg rounded-lg max-h-[calc(100vh-200px)] overflow-y-auto">
    <table class="min-w-full border-collapse bg-white">
        <thead>
            <tr class="bg-gradient-to-r from-blue-600 to-blue-700">
                <th class="px-4 py-3 border-r border-blue-500 sticky left-0 top-0 bg-blue-600 z-20">
                    <span class="text-white font-semibold text-sm uppercase">Tiết</span>
                </th>
                @foreach($periods as $day)
                    <th class="px-3 py-3 border-r border-blue-500 last:border-r-0 min-w-[180px] sticky top-0 bg-blue-700 z-10">
                        <div class="text-center">
                            <div class="text-white font-bold text-base">
                                {{ \Carbon\Carbon::parse($day['date'])->format('d/m') }}
                            </div>
                            <div class="text-blue-100 text-xs mt-1 font-medium">
                                {{ $day['weekday'] }}
                            </div>
                        </div>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @for($period = 1; $period <= 9; $period++)
                <tr class="hover:bg-blue-50/50 transition-colors duration-150 {{ $period % 2 == 0 ? 'bg-gray-50/50' : 'bg-white' }}">
                    <td class="border px-3 py-4 text-center sticky left-0 {{ $period % 2 == 0 ? 'bg-gray-50' : 'bg-white' }} z-10">
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 text-white font-bold shadow-sm">
                            {{ $period }}
                        </div>
                    </td>
                    @foreach($periods as $day)
                        @php $detail = $day['periods'][$period] ?? null; @endphp
                        <td class="border px-3 py-4 align-top">
                            @if($detail && $detail['subject_name'])
                                <div class="space-y-1.5">
                                    <div class="font-semibold text-sm leading-tight" style="color: {{ $detail['subject_color'] }}">
                                        {{ $detail['subject_name'] }} 
                                        <span class="inline-block px-2 py-0.5 text-xs font-medium rounded 
                                            {{ $detail['lesson_type'] == 'Lý thuyết' ? 'bg-green-100 text-green-700' : ($detail['lesson_type'] == 'Thực hành' ? 'bg-orange-100 text-orange-700' : 'bg-purple-100 text-purple-700') }}"
                                            title="{{ $detail['lesson_type'] }}">
                                            {{ $detail['lesson_type'] == 'Lý thuyết' ? 'LT' : ($detail['lesson_type'] == 'Thực hành' ? 'TH' : 'Ôn luyện') }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $detail['subject_code'] }}
                                    </div>
                                    <div class="text-xs text-gray-600 mt-1.5 flex gap-1">
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span>{{ $detail['instructor_name'] }}</span>
                                    </div>
                                    <div class="text-xs font-medium text-blue-600 mt-1 flex gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span>{{ $detail['classroom_name'] }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="text-gray-300 text-center py-6">
                                    Trống
                                </div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endfor
        </tbody>
    </table>
</div>