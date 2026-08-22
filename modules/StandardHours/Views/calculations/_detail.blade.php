<div class="grid gap-6">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Thông tin giảng viên</h3>
            <div class="flex gap-2">
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $yearlyResult->meets_overall ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $yearlyResult->overall_result_text }}
                </span>
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $yearlyResult->isLocked() ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $yearlyResult->status_text }}
                </span>
            </div>
        </div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <span class="text-sm text-gray-600">Giảng viên</span>
                <div class="mt-1 font-medium">{{ $yearlyResult->instructor->name }} ({{ $yearlyResult->instructor->code }})</div>
            </div>
            <div>
                <span class="text-sm text-gray-600">Đơn vị</span>
                <div class="mt-1">{{ $yearlyResult->instructor->unit->name ?? '—' }}</div>
            </div>
            <div>
                <span class="text-sm text-gray-600">Đối tượng</span>
                <div class="mt-1">{{ $yearlyResult->objectType->name ?? '—' }}</div>
            </div>
            <div>
                <span class="text-sm text-gray-600">Chức danh</span>
                <div class="mt-1">{{ $yearlyResult->position->name ?? '—' }}</div>
            </div>
            <div>
                <span class="text-sm text-gray-600">{{ $yearlyResult->period_mode === 'academic_year' ? 'Năm học' : 'Năm' }}</span>
                <div class="mt-1 font-medium">{{ $yearlyResult->period_label }}</div>
            </div>
            <div>
                <span class="text-sm text-gray-600">Khoảng thời gian kê khai</span>
                <div class="mt-1 font-medium">{{ $yearlyResult->declaration_period_text }}</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h4 class="font-semibold text-gray-900 mb-4">Giờ trực tiếp giảng dạy</h4>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-600">Tự động từ Lịch huấn luyện</dt><dd class="font-medium">{{ number_format((float) $yearlyResult->schedule_teaching_hours, 2) }} giờ</dd></div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Giờ giảng dạy khác</dt>
                    <dd class="font-medium">
                        {{ number_format((float) $yearlyResult->other_teaching_hours, 2) }} giờ
                        @if($yearlyResult->declared_at)
                            <span class="ml-1 text-xs {{ $yearlyResult->declaration_status === 'approved' ? 'text-green-700' : 'text-amber-700' }}">
                                ({{ $yearlyResult->declaration_status_text }})
                            </span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between border-t pt-2"><dt class="font-medium text-gray-800">Cộng giờ trực tiếp</dt><dd class="font-bold text-indigo-700">{{ number_format($yearlyResult->teaching_hours, 2) }} giờ</dd></div>
                <div class="flex justify-between"><dt class="text-gray-600">Giờ quy đổi HĐ CM</dt><dd class="font-medium">{{ number_format($yearlyResult->conversion_hours, 0) }} giờ</dd></div>
                <div class="flex justify-between border-t pt-2"><dt class="font-medium">Tổng giờ chuẩn</dt><dd class="font-bold text-blue-700">{{ number_format($yearlyResult->total_standard_hours, 2) }} giờ</dd></div>
                <div class="flex justify-between"><dt class="text-gray-600">Giờ đủ điều kiện tính vượt</dt><dd class="font-medium text-violet-700">{{ number_format((float) $yearlyResult->overtime_eligible_hours, 2) }} giờ</dd></div>
                <div class="flex justify-between"><dt class="text-gray-600">Định mức yêu cầu</dt><dd>{{ number_format($yearlyResult->standard_norm_hours, 0) }} giờ</dd></div>
                <div class="flex justify-between"><dt class="text-gray-600">Chênh lệch</dt><dd class="{{ $yearlyResult->standard_difference >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format($yearlyResult->standard_difference, 0) }} giờ</dd></div>
                <div class="flex justify-between"><dt class="text-gray-600">Tối thiểu đứng lớp</dt><dd>{{ number_format($yearlyResult->min_classroom_hours, 0) }} giờ</dd></div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Đạt giờ chuẩn</dt>
                    <dd><span class="{{ $yearlyResult->meets_standard ? 'text-green-700' : 'text-red-700' }}">{{ $yearlyResult->meets_standard ? 'Có' : 'Không' }}</span></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Đạt giờ đứng lớp (≥50%)</dt>
                    <dd><span class="{{ $yearlyResult->meets_classroom ? 'text-green-700' : 'text-red-700' }}">{{ $yearlyResult->meets_classroom ? 'Có' : 'Không' }}</span></dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4">
            <h4 class="font-semibold text-gray-900 mb-4">Nghiên cứu khoa học</h4>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-600">Giờ NCKH quy đổi</dt><dd class="font-medium">{{ number_format($yearlyResult->research_hours, 0) }} giờ</dd></div>
                <div class="flex justify-between"><dt class="text-gray-600">Định mức NCKH</dt><dd>{{ number_format($yearlyResult->research_norm_hours, 0) }} giờ</dd></div>
                <div class="flex justify-between border-t pt-2"><dt class="text-gray-600">Chênh lệch</dt><dd class="{{ $yearlyResult->research_difference >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format($yearlyResult->research_difference, 0) }} giờ</dd></div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Đạt NCKH</dt>
                    <dd><span class="{{ $yearlyResult->meets_research ? 'text-green-700' : 'text-red-700' }}">{{ $yearlyResult->meets_research ? 'Có' : 'Không' }}</span></dd>
                </div>
            </dl>
        </div>
    </div>

    @php
        $teachingSnapshot = $yearlyResult->schedule_teaching_details ?? [];
        $teachingRows = collect($teachingSnapshot['rows'] ?? []);
        $teachingBySubject = collect($teachingSnapshot['by_subject'] ?? []);
    @endphp
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="font-semibold text-gray-900">Thống kê Lịch huấn luyện đã truy xuất</h3>
                <p class="mt-1 text-xs text-gray-500">
                    {{ $teachingRows->count() }} tiết
                    @if($yearlyResult->schedule_retrieved_at)
                        · truy xuất lúc {{ $yearlyResult->schedule_retrieved_at->format('d/m/Y H:i') }}
                    @endif
                </p>
            </div>
            @if((auth()->user()?->isInstructor() || auth()->user()?->isManagementActor()) && ! $yearlyResult->isLocked())
                <a href="{{ route('standard-hours.my-results.declaration', array_filter([
                        'year' => $yearlyResult->year,
                        'instructor_id' => auth()->user()?->isInstructor() ? null : $yearlyResult->instructor_id,
                    ])) }}"
                   class="{{ \Modules\StandardHours\Support\ActionButton::classes('secondary') }}">
                    <i class="bi bi-arrow-repeat"></i> Truy xuất lại
                </a>
            @endif
        </div>

        @if($teachingBySubject->isNotEmpty())
            <div class="grid grid-cols-1 gap-2 border-b border-gray-100 bg-slate-50 p-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($teachingBySubject as $subject)
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                        <div class="font-medium text-slate-900">{{ $subject['subject_name'] ?? 'Chưa xác định môn' }}</div>
                        <div class="mt-1 flex justify-between text-xs text-slate-500">
                            <span>LT: {{ number_format((float) ($subject['theory_hours'] ?? 0), 0) }}</span>
                            <span>TH: {{ number_format((float) ($subject['practice_hours'] ?? 0), 0) }}</span>
                            <strong class="text-indigo-700">Tổng: {{ number_format((float) ($subject['total_hours'] ?? 0), 0) }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($teachingRows->isNotEmpty())
            <div class="max-h-[460px] overflow-auto">
                <table class="w-full min-w-[850px] text-sm">
                    <thead class="sticky top-0 bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-3 py-2 text-left">Ngày</th>
                            <th class="px-3 py-2 text-center">Tiết</th>
                            <th class="px-3 py-2 text-left">Môn học</th>
                            <th class="px-3 py-2 text-left">Loại tiết</th>
                            <th class="px-3 py-2 text-left">Lớp</th>
                            <th class="px-3 py-2 text-left">Lịch huấn luyện</th>
                            <th class="px-3 py-2 text-left">Phòng</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($teachingRows as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2"><div class="font-medium">{{ $row['date_label'] ?? '—' }}</div><div class="text-xs text-slate-500">{{ $row['weekday'] ?? '' }}</div></td>
                                <td class="px-3 py-2 text-center font-semibold">{{ $row['period'] ?? '—' }}</td>
                                <td class="px-3 py-2"><div class="font-medium">{{ $row['subject_name'] ?? '—' }}</div><div class="text-xs text-slate-500">{{ $row['subject_code'] ?? '' }}</div></td>
                                <td class="px-3 py-2">{{ $row['lesson_type_label'] ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $row['class_name'] ?? $row['class_code'] ?? '—' }}</td>
                                <td class="px-3 py-2"><div>{{ $row['schedule_name'] ?? '—' }}</div><div class="text-xs text-slate-500">{{ $row['schedule_code'] ?? '' }}</div></td>
                                <td class="px-3 py-2">{{ $row['classroom'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-4 py-8 text-center text-sm text-gray-500">Hồ sơ chưa có bản thống kê chi tiết lịch giảng dạy.</div>
        @endif
    </div>

    @if($yearlyResult->other_teaching_notes)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm">
            <h4 class="font-semibold text-amber-900">Giải trình giờ giảng dạy khác</h4>
            <div class="mt-2 whitespace-pre-line text-amber-800">{{ $yearlyResult->other_teaching_notes }}</div>
        </div>
    @endif

    @if($yearlyResult->declared_at)
        <div class="rounded-xl border {{ $yearlyResult->declaration_status === 'approved' ? 'border-green-200 bg-green-50' : ($yearlyResult->declaration_status === 'rejected' ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50') }} p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h4 class="font-semibold text-slate-900">Duyệt hồ sơ kê khai giờ chuẩn</h4>
                    <p class="mt-1 text-sm text-slate-700">
                        Trạng thái: <strong>{{ $yearlyResult->declaration_status_text }}</strong>.
                        Giờ giảng dạy khác chỉ được cộng sau khi cấp quản lý duyệt.
                    </p>
                    @if($yearlyResult->declaration_review_note)
                        <p class="mt-2 text-sm text-slate-700"><strong>Ý kiến:</strong> {{ $yearlyResult->declaration_review_note }}</p>
                    @endif
                </div>

                @if(\App\Support\ApprovalAgency::canReviewAnnualStandardHours(auth()->user()))
                    @unless($yearlyResult->isLocked())
                        <div class="grid w-full gap-2 sm:grid-cols-2 lg:w-auto">
                            <form action="{{ route('standard-hours.calculations.review-declaration', $yearlyResult) }}" method="POST" data-turbo="false">
                                @csrf @method('PATCH')
                                <input type="hidden" name="declaration_status" value="approved">
                                <button class="w-full rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-800">
                                    <i class="bi bi-check-circle mr-1"></i>Duyệt kê khai
                                </button>
                            </form>
                            <form action="{{ route('standard-hours.calculations.review-declaration', $yearlyResult) }}" method="POST" class="flex gap-2" data-turbo="false">
                                @csrf @method('PATCH')
                                <input type="hidden" name="declaration_status" value="rejected">
                                <input name="declaration_review_note" required maxlength="2000" placeholder="Lý do từ chối"
                                       class="min-w-0 flex-1 rounded-lg border border-red-300 bg-white px-3 py-2 text-sm">
                                <button class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-800">
                                    Từ chối
                                </button>
                            </form>
                        </div>
                    @endunless
                @endif
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-4 text-sm text-gray-600">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($yearlyResult->declared_at)
            <div>Kê khai bởi: <strong>{{ $yearlyResult->declarer->name ?? $yearlyResult->instructor->name ?? '—' }}</strong> — {{ $yearlyResult->declared_at->format('d/m/Y H:i') }}</div>
            @endif
            <div>Tính bởi: <strong>{{ $yearlyResult->calculator->name ?? '—' }}</strong> — {{ $yearlyResult->calculated_at?->format('d/m/Y H:i') ?? '—' }}</div>
            @if($yearlyResult->locked_at)
            <div>Khóa bởi: <strong>{{ $yearlyResult->locker->name ?? '—' }}</strong> — {{ $yearlyResult->locked_at->format('d/m/Y H:i') }}</div>
            @endif
        </div>
    </div>
</div>
