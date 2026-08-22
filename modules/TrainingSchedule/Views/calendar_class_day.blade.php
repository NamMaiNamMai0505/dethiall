<div class="overflow-x-auto shadow-sm">
    <table class="min-w-full border-collapse bg-white rounded-lg overflow-hidden">
        <thead>
            <tr class="bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                <th class="px-4 py-3 text-left text-sm font-semibold uppercase tracking-wider">Tiết</th>
                <th class="px-4 py-3 text-left text-sm font-semibold uppercase tracking-wider">Môn học</th>
                <th class="px-4 py-3 text-center text-sm font-semibold uppercase tracking-wider">Loại</th>
                <th class="px-4 py-3 text-center text-sm font-semibold uppercase tracking-wider">Giảng viên</th>
                <th class="px-4 py-3 text-center text-sm font-semibold uppercase tracking-wider">Phòng học</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($periods as $period => $detail)
                <tr class="hover:bg-blue-50 transition-colors duration-150 {{ $detail['subject_name'] ? '' : 'opacity-50' }}">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold text-sm">
                            {{ $period }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($detail['subject_name'])
                            <div class="flex flex-col">
                                <span class="font-semibold text-gray-900" style="color: {{ $detail['subject_color'] }}">
                                    {{ $detail['subject_name'] }}(<span class="text-xs text-gray-500 mt-0.5">{{ $detail['subject_code'] }}</span>)
                                </span>
                            </div>
                        @else
                            <span class="text-gray-900 italic">Trống</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($detail['lesson_type'])
                            <span class="inline-block px-3 py-1 text-xs font-medium rounded-full 
                                {{ $detail['lesson_type'] == 'Lý thuyết' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}"
                                title="{{ $detail['lesson_type'] }}">
                                {{ $detail['lesson_type'] }}
                            </span>
                        @else
                            <span class="text-gray-900">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-gray-700">
                        {{ $detail['instructor_name'] ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($detail['classroom_name'])
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 text-sm font-medium">
                                <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $detail['classroom_name'] }}
                            </span>
                        @else
                            <span class="text-gray-900">-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>