@php
    $user = auth()->user();
    $militaryPersonnel = $militaryPersonnel ?? \Modules\LeaveManagement\Models\LeavePersonnel::withoutGlobalScopes()
        ->where('active', true)
        ->where(function ($q) use ($user) {
            $q->where('user_id', $user?->id)
                ->orWhere(function ($x) use ($user) {
                    $x->whereNotNull('email')->where('email', $user?->email);
                })
                ->orWhere('name', $user?->name)
                ->orWhere('staff_code', $user?->code);
        })
        ->first();
    if (!isset($canProposeForUnit)) {
        $canProposeForUnit = $user?->isSuperAdmin()
            || (!$militaryPersonnel && ($user?->can('leave-management.approvals.approve') || $user?->can('leave-management.approve')));
    }
    $isMilitaryAccount = $isMilitaryAccount ?? (!$user?->isSuperAdmin() && (bool) $militaryPersonnel);
    $militaryExtraData = $extraStandards->map(fn($rule) => [
        'id' => $rule->id,
        'label' => $rule->label,
        'days' => (int) $rule->base_days,
    ])->values();
    $localityData = $localities->map(fn($item) => [
        'id' => $item->id,
        'parent_id' => $item->parent_id,
        'name' => $item->name,
        'parent_name' => $item->parent?->name,
        'label' => $item->parent?->name ? $item->name.' — '.$item->parent->name : $item->name,
    ])->values();
    $replacementData = $replacementPersonnel->map(fn($item) => [
        'id' => (int) $item->id,
        'label' => ($item->staff_code ? $item->staff_code.' — ' : '').$item->name,
        'unit_id' => (int) $item->unit_id,
    ])->values();
    $proposalPersonnel = collect($personnel ?? [])->filter(fn($person) => $person?->active !== false);
    $normalizeClassName = fn($name) => \Illuminate\Support\Str::lower(trim((string) $name));
    $personToRow = fn($person, $className = null) => [
        'id' => (int) $person->id,
        'name' => $person->name,
        'code' => $person->staff_code,
        'class' => $className ?: ($person->leaveClass?->name ?? $person->class_name),
        'class_id' => (int) ($person->class_id ?? 0),
        'class_name' => $person->class_name ?? $person->leaveClass?->name,
        'unit_id' => (int) ($person->unit_id ?? 0),
    ];
    $proposalPeopleData = $proposalPersonnel
        ->sortBy('name')
        ->map(fn($person) => $personToRow($person))
        ->values();
    $classProposalOptions = $classes->map(function ($class) use ($proposalPersonnel, $normalizeClassName, $personToRow) {
        $className = $normalizeClassName($class->name);
        $people = collect($class->personnel ?? [])
            ->merge($proposalPersonnel->filter(fn($person) =>
                (int) ($person->class_id ?? 0) === (int) $class->id
                || ($className !== '' && $normalizeClassName($person->class_name ?? $person->leaveClass?->name) === $className)
            ))
            ->filter(fn($person) => $person?->id)
            ->unique('id')
            ->sortBy('name')
            ->map(fn($person) => $personToRow($person, $class->name))
            ->values();

        return [
            'id' => (int) $class->id,
            'name' => $class->name,
            'unit_name' => $class->unit?->name,
            'people' => $people,
        ];
    })->values();
    $unitProposalOptions = $personnel->filter(fn($person) => $person->unit_id)->groupBy('unit_id')->map(function ($people) {
        $first = $people->first();
        return [
            'id' => (int) $first->unit_id,
            'name' => $first->unitRelation?->name ?? $first->unit ?? 'Chưa có đơn vị',
            'people' => $people->sortBy('name')->map(fn($person) => [
                'id' => (int) $person->id,
                'name' => $person->name,
                'code' => $person->staff_code,
                'class' => $person->leaveClass?->name ?? $person->class_name,
            ])->values(),
        ];
    })->sortBy('name')->values();
@endphp
<section class="mb-5 overflow-visible rounded-2xl border border-blue-100 bg-white shadow-sm">
    <style>
        #class-info-panel,
        #class-info-panel .date-input-field,
        #class-info-panel .date-input-control {
            overflow: visible;
        }
        #class-info-panel .flatpickr-calendar {
            z-index: 10080 !important;
            min-width: 18rem;
        }
        #class-students-table-wrap {
            transition: min-height 0.18s ease;
        }
        #class-students-table-wrap.is-datepicker-open,
        #class-students-table-wrap:focus-within {
            min-height: 24rem;
        }
    </style>
    <div class="border-b border-blue-100 bg-gradient-to-r from-blue-50 via-white to-sky-50 px-5 py-4">
        <p class="text-xs font-bold uppercase tracking-[.16em] text-blue-600">Quy trình đề xuất</p>
        <h2 class="mt-1 text-lg font-extrabold text-slate-900">Đề xuất nghỉ phép</h2>
        <p class="mt-1 text-sm text-slate-500">Thực hiện đề xuất nghỉ phép theo đúng đối tượng, hình thức và quy trình xét duyệt.</p>
    </div>
    @if($errors->any())
        <div class="mx-4 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif
    <form id="leave-proposal-form" method="POST" action="{{ route('leave-management.requests.store') }}" novalidate class="grid gap-x-4 gap-y-3 p-4 md:grid-cols-2 md:p-5">
        @csrf
        @if($isMilitaryAccount)
            <input type="hidden" name="personnel_id" value="{{ $militaryPersonnel?->id }}">
            <style>#proposal-personnel,#personnel-field,#proposal-class,#class-field,#short-people,#class-scope-panel,#class-info-panel{display:none!important}</style>
            <div id="military-profile-fields" class="grid gap-3 md:col-span-2 md:grid-cols-2">
                <label class="block text-xs font-bold text-slate-600">Họ tên<input value="{{ $militaryPersonnel?->name }}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label>
                <label class="block text-xs font-bold text-slate-600">Ngày nhập ngũ / tuyển dụng<input value="{{ $militaryPersonnel?->enlistment_date?->format('d/m/Y') }}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label>
                <label class="block text-xs font-bold text-slate-600">Cấp bậc<input value="{{ $militaryPersonnel?->rank }}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label>
                <label class="block text-xs font-bold text-slate-600">Chức vụ<input value="{{ $militaryPersonnel?->position }}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label>
                <label class="block text-xs font-bold text-slate-600">Đơn vị<input value="{{ $militaryPersonnel?->unitRelation?->name ?? $militaryPersonnel?->unit }}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label>
                <label class="block text-xs font-bold text-slate-600">Chỉ huy đơn vị<input value="{{ $militaryPersonnel?->commander?->name ?? $militaryPersonnel?->commander_name ?? 'Chưa gán' }}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label>
                <label class="block text-xs font-bold text-slate-600">Thâm niên / Ngày phép cơ bản<input value="{{ $militaryServiceYears ?? 0 }} năm — {{ $militaryAnnualDays ?? 0 }} ngày" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label>
            </div>
        @endif
        <div id="class-scope-panel" class="hidden rounded-2xl border border-slate-200 bg-white p-4 md:col-span-2">
            <div class="mb-4 text-sm font-extrabold text-slate-900">Phạm vi đề xuất</div>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Đề xuất cho <b class="text-red-500">*</b></span><select id="class-scope-select" class="w-full rounded-lg border-slate-200 px-3 py-2.5 shadow-sm"><option value="CLASS">Phép hằng năm của học viên</option><option value="HSQBS_ANNUAL">Phép hằng năm của HSQBS</option><option value="HSQBS_SPECIAL">Phép đặc biệt của HSQBS</option><option value="SHORT_LEAVE">Phép tranh thủ</option></select></label>
                <label id="class-class-field" class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Lớp <b class="text-red-500">*</b></span><select id="class-class-select" class="w-full rounded-lg border-slate-200 px-3 py-2.5 shadow-sm"><option value="">Chọn lớp</option>@foreach($classProposalOptions as $classOption)<option value="{{ $classOption['id'] }}" data-people='@json($classOption['people'], JSON_UNESCAPED_UNICODE)'>{{ $classOption['name'] }} — {{ $classOption['unit_name'] }}</option>@endforeach</select></label>
                <label id="class-unit-field" class="hidden block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Đơn vị <b class="text-red-500">*</b></span><select id="class-unit-select" name="unit_id" class="w-full rounded-lg border-slate-200 px-3 py-2.5 shadow-sm"><option value="">Chọn đơn vị</option>@foreach($unitProposalOptions as $unit)<option value="{{ $unit['id'] }}" data-people='@json($unit['people'], JSON_UNESCAPED_UNICODE)'>{{ $unit['name'] }}</option>@endforeach</select></label>
                <label id="class-agency-field" class="block md:col-span-2"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Cơ quan quản lý tiếp nhận <b class="text-red-500">*</b></span><select id="class-agency-select" name="managing_agency" disabled class="w-full rounded-lg border-slate-200 px-3 py-2.5 shadow-sm"><option value="">Chọn cơ quan quản lý</option><option value="QUAN_LUC">Quân lực</option><option value="CO_QUAN_CAN_BO">Cơ quan cán bộ</option></select><span class="mt-1 block text-xs text-slate-500">Đơn vị chọn cơ quan thẩm định; cơ quan quản lý sẽ trình Ban Giám hiệu ký và thông báo xuống.</span></label>
            </div>
        </div>
        <div id="class-info-panel" class="hidden rounded-2xl border border-slate-200 bg-white p-4 md:col-span-2">
            <div class="mb-4 text-sm font-extrabold text-slate-900">Thông tin đề xuất</div>
            <div class="grid gap-3 md:grid-cols-2">
                <label class="block md:col-span-2"><span class="mb-1 block text-sm font-semibold text-slate-700">Đối tượng</span><input id="class-object" readonly class="w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5"></label>
                <label class="block md:col-span-2"><span class="mb-1 block text-sm font-semibold text-slate-700">Lý do <b class="text-red-500">*</b></span><input id="class-reason" placeholder="Nghỉ hè" class="w-full rounded-lg border-slate-200 px-3 py-2.5"></label>
                <label class="block"><span class="mb-1 block text-sm font-semibold text-slate-700">Đơn vị</span><input id="class-unit" readonly class="w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5"></label>
                <label class="block"><span id="class-days-label" class="mb-1 block text-sm font-semibold text-slate-700">Số ngày nghỉ <b class="text-red-500">*</b></span><input id="class-days" type="number" min="1" value="1" class="w-full rounded-lg border-slate-200 px-3 py-2.5"><span id="class-days-help" class="mt-1 block text-xs text-slate-500">Đại đội nhập trực tiếp, không tính theo thâm niên.</span></label>
                <label id="class-travel-field" class="hidden block"><span class="mb-1 block text-sm font-semibold text-slate-700">Ngày đi đường</span><input id="class-travel" type="number" min="0" step="1" value="0" class="w-full rounded-lg border-slate-200 px-3 py-2.5"></label>
                <label id="class-from-field" class="block"><span class="mb-1 block text-sm font-semibold text-slate-700">Ngày bắt đầu</span><input id="class-from" type="date" class="w-full rounded-lg border-slate-200 px-3 py-2.5"></label>
                <label id="class-to-field" class="block"><span class="mb-1 block text-sm font-semibold text-slate-700">Ngày kết thúc</span><input id="class-to" type="date" placeholder="Tự động tính" class="w-full rounded-lg border-slate-200 px-3 py-2.5"></label>
            </div>
            <div class="mt-4 flex items-center justify-between gap-3"><div class="text-sm font-semibold text-slate-700">Nơi nghỉ của học viên trong lớp</div><button type="button" id="select-all-short-students" class="hidden rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white hover:bg-blue-700">Tích tất cả học viên</button></div>
            <div id="class-students-table-wrap" class="mt-3 overflow-x-auto overflow-y-visible rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wide text-slate-600">
                        <tr><th id="class-student-select-head" class="hidden w-14 px-4 py-3">Chọn</th><th class="w-[22%] px-4 py-3">Quân nhân</th><th class="w-[12%] px-4 py-3">Lớp</th><th class="min-w-[190px] px-4 py-3">Ngày đi</th><th class="min-w-[190px] px-4 py-3">Ngày kết thúc</th><th class="min-w-[280px] px-4 py-3">Nơi nghỉ</th></tr>
                    </thead>
                    <tbody id="class-students" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>
        <div class="md:col-span-2 border-b border-slate-100 pb-2 text-sm font-extrabold text-slate-900">Thông tin đề xuất</div>
        <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Đối tượng</span><select id="proposal-scope" name="request_scope" required class="w-full rounded-lg border-slate-200 px-3 py-2.5 shadow-sm">@if($canProposeForUnit)<option value="CLASS">Phép hằng năm của học viên</option><option value="HSQBS_ANNUAL">Phép hằng năm của HSQBS</option><option value="HSQBS_SPECIAL">Phép đặc biệt của HSQBS</option><option value="SHORT_LEAVE">Phép tranh thủ — đại đội tích chọn học viên</option>@endif<option value="PERSONAL">Sĩ quan</option></select></label>
        <label id="personnel-field" class="hidden block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Quân nhân</span><select id="proposal-personnel" name="personnel_id" class="w-full rounded-xl border-slate-200 px-3 py-2.5 shadow-sm"><option value="">Chọn quân nhân</option>@foreach($personnel as $person)<option value="{{ $person->id }}" data-unit-id="{{ $person->unit_id }}">{{ $person->staff_code }} — {{ $person->name }}</option>@endforeach</select></label>
        <label id="replacement-field" class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Người thay thế trong thời gian nghỉ</span><select id="proposal-replacement" name="replacement_personnel_id" class="w-full rounded-xl border-slate-200 px-3 py-2.5 shadow-sm"><option value="">Chọn quân nhân thay thế...</option>@foreach($replacementPersonnel as $replacement)<option value="{{ $replacement->id }}" data-unit-id="{{ $replacement->unit_id }}">{{ $replacement->staff_code }} — {{ $replacement->name }}</option>@endforeach</select><span class="mt-1 block text-xs text-slate-500">Chỉ hiển thị quân nhân cùng đơn vị với người nghỉ.</span></label>
         <label id="class-field" class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Lớp / đại đội <b class="text-red-500">*</b></span><select id="proposal-class" name="class_id" class="w-full rounded-xl border-slate-200 px-3 py-2.5 shadow-sm"><option value="">Chọn lớp</option>@foreach($classProposalOptions as $classOption)<option value="{{ $classOption['id'] }}" data-people='@json($classOption['people'], JSON_UNESCAPED_UNICODE)'>{{ $classOption['unit_name'] }} — {{ $classOption['name'] }} ({{ count($classOption['people']) }} học viên)</option>@endforeach</select></label>
        <label id="leave-type-field" class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Loại phép</span><select id="proposal-leave-type" name="leave_type" class="w-full rounded-lg border-slate-200 px-3 py-2.5 shadow-sm"><option value="ANNUAL">Phép hàng năm</option><option value="SICK">Nghỉ ốm</option><option value="PERSONAL">Nghỉ việc riêng</option></select></label>
        <div id="short-people" class="hidden rounded-xl border border-blue-100 bg-blue-50/60 p-4 md:col-span-3"><div class="mb-3 flex items-center justify-between"><div><h3 class="font-bold text-slate-900">Danh sách học viên nghỉ tranh thủ</h3><p class="text-xs text-slate-500">Tích đúng những học viên được nghỉ từ tối thứ Sáu đến chiều Chủ nhật.</p></div><button type="button" id="check-all-short" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white">Tích tất cả</button></div><div id="short-people-list" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3"></div></div>
        <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày bắt đầu <b class="text-red-500">*</b></span><input id="proposal-from" name="from_date" type="date" required class="w-full rounded-lg border-slate-200 px-3 py-2.5 shadow-sm"></label>
         <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày kết thúc <b class="text-red-500">*</b></span><input id="proposal-to" name="to_date" type="date" required placeholder="Tự động tính ngày kết thúc" class="w-full rounded-lg border-slate-200 px-3 py-2.5 shadow-sm"><input id="proposal-to-hidden" type="hidden" value=""></label>
        <label id="annual-fields" class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày đi đường</span><input id="proposal-travel" name="travel_days" type="number" min="0" step="1" inputmode="numeric" value="0" class="w-full rounded-xl border-slate-200 px-3 py-2.5 shadow-sm"></label>
        <label class="block md:col-span-2"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Ghi chú / lý do nghỉ <b id="reason-required-mark" class="hidden text-red-500">*</b></span><textarea id="proposal-reason" name="reason" rows="2" class="w-full rounded-lg border-slate-200 px-3 py-2.5 shadow-sm" placeholder="Nhập lý do nghỉ..."></textarea></label>
        <label id="proposal-locality-field" class="block md:col-span-2"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Nơi nghỉ (phường / xã)</span><select id="proposal-locality" name="locality_id" class="w-full rounded-xl border-slate-200 px-3 py-2.5 shadow-sm"><option value="">Chọn phường / xã</option>@foreach($localities->whereNotNull('parent_id') as $locality)<option value="{{ $locality->id }}">{{ $locality->parent?->name }} — {{ $locality->name }}</option>@endforeach</select></label>
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-bold text-slate-800 shadow-sm md:col-span-2"><span id="leave-summary-text">Thâm niên: 0 năm &nbsp; Phép cơ bản: 0 ngày &nbsp; Đi đường: 0 ngày &nbsp; Nghỉ thêm: 0 ngày &nbsp; Tổng: 0 ngày</span></div>
         <div class="flex justify-start md:col-span-2"><button type="submit" id="leave-proposal-submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800"><i class="bi bi-send mr-1"></i> Gửi đăng ký</button></div>
    @if($isMilitaryAccount || $canProposeForUnit)<div id="server-military-extra" class="mt-4 rounded-xl border border-slate-200 bg-white p-3 md:col-span-2"><div class="mb-3 text-sm font-extrabold text-slate-800">Ph&eacute;p th&ecirc;m</div><div class="flex border-b border-slate-200 bg-slate-50">@foreach([5,10] as $tabDays)<button type="button" class="server-extra-tab px-5 py-3 text-sm font-extrabold {{ $loop->first ? 'bg-white text-blue-700' : 'text-slate-500' }}" data-tab-days="{{ $tabDays }}">{{ $tabDays }} ng&agrave;y</button>@endforeach</div>@foreach([5,10] as $tabDays)<div class="server-extra-panel space-y-2 p-3 {{ $loop->first ? '' : 'hidden' }}" data-panel-days="{{ $tabDays }}">@foreach($extraStandards->where('base_days',$tabDays) as $rule)<label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3"><input type="checkbox" name="extra_standard_ids[]" value="{{ $rule->id }}" data-days="{{ $rule->base_days }}" class="server-extra-check mt-1 h-4 w-4 accent-blue-600"><span><span class="block font-semibold text-slate-700">{{ $rule->label ?: $rule->description }}</span><span class="mt-1 block text-xs text-slate-500">{{ $rule->base_days }} ngày</span></span></label>@endforeach</div>@endforeach</div>@endif
</form>
</section>
@php
    // This script is rendered outside the permission block above, so keep the
    // replacement data available for every render path.
    $replacementData = ($replacementPersonnel ?? collect())->map(fn($item) => [
        'id' => (int) $item->id,
        'label' => ($item->staff_code ? $item->staff_code . ' — ' : '') . $item->name,
        'unit_id' => (int) $item->unit_id,
    ])->values();
@endphp
<script>
(()=>{
  const isAdmin=@json(($canProposeForUnit ?? false) && !($isMilitaryAccount ?? false));
  const scope=document.getElementById('proposal-scope');
  if(!isAdmin||!scope)return;
  const label=scope.closest('label');
  if(label){label.classList.add('md:col-span-2');label.style.order='-20';const title=label.querySelector('span');if(title)title.textContent='Phạm vi đề xuất';}
  const labels={
    PERSONAL:'Phép của quân nhân',
    SHORT_LEAVE:'Phép tranh thủ — phép của đại đội',
    CLASS:'Phép hằng năm của học viên',
    HSQBS_ANNUAL:'Phép hằng năm của HSQBS',
    HSQBS_SPECIAL:'Phép đặc biệt của HSQBS'
  };
  Array.from(scope.options).forEach(option=>{if(labels[option.value])option.textContent=labels[option.value];});
  scope.value='PERSONAL';
  const cleanAdminOptions=()=>{
    Array.from(scope.options).forEach(option=>{if(option.value==='ADMIN_EXTRA')option.remove();});
    Array.from(scope.options).forEach(option=>{if(labels[option.value])option.textContent=labels[option.value];});
  };
  setTimeout(cleanAdminOptions,0);
 })();
 </script>
 <script>
 (()=>{
   const scope=document.getElementById('proposal-scope');
   if(!scope)return;
   const syncGroupRequired=()=>{
     const value=scope.tomselect?scope.tomselect.getValue():scope.value;
     const group=['CLASS','SHORT_LEAVE','HSQBS_ANNUAL','HSQBS_SPECIAL'].includes(value);
     const hsqbs=['HSQBS_ANNUAL','HSQBS_SPECIAL'].includes(value);
     ['proposal-class','class-class-select','class-reason','class-days'].forEach(id=>document.getElementById(id)?.toggleAttribute('required',group&&!hsqbs));
     document.getElementById('class-from')?.removeAttribute('required');
     document.getElementById('class-unit-select')?.toggleAttribute('required',hsqbs);
   };
   scope.addEventListener('change',syncGroupRequired);
   scope.tomselect?.on('change',syncGroupRequired);
   syncGroupRequired();
 })();
 </script>
 <script>
(()=>{
  const fixLeaveFields=()=>{
    const scope=document.getElementById('proposal-scope'),note=document.getElementById('proposal-reason'),extraBox=document.getElementById('server-military-extra')||document.getElementById('personal-extra-box');
    if(note&&extraBox){const noteLabel=note.closest('label');if(noteLabel)noteLabel.after(extraBox);const isPersonal=scope?.value==='PERSONAL';extraBox.classList.toggle('hidden',!(@json($isMilitaryAccount ?? false))&&!isPersonal);}
    const summary=document.getElementById('leave-summary-text');
    if(summary&&@json($isMilitaryAccount ?? false)){
      const service=Number(@json($militaryServiceYears ?? 0)),base=Number(@json($militaryAnnualDays ?? 0)),travel=Number(document.getElementById('proposal-travel')?.value||0),extra=Array.from(document.querySelectorAll('.server-extra-check:checked,.military-extra:checked,.generic-extra:checked')).reduce((sum,item)=>sum+Number(item.dataset.days||0),0),total=base+travel+extra;
      summary.innerHTML='<div class="flex min-w-max items-center gap-8 text-sm font-bold text-slate-900"><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Th&acirc;m ni&ecirc;n</span><span>'+service+' n&#x103;m</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Ph&eacute;p c&#x1a1; b&#x1ea3;n</span><span>'+base+' ng&agrave;y</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">&#x110;i &#x111;&#x1b0;&#x1edd;ng</span><span>'+travel+' ng&agrave;y</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Ngh&#x1ec9; th&ecirc;m</span><span>'+extra+' ng&agrave;y</span></div><div class="whitespace-nowrap rounded-lg bg-blue-50 px-3 py-2 text-slate-900"><span class="mr-2 text-xs font-semibold text-slate-500">T&#x1ed5;ng</span><span>'+total+' ng&agrave;y</span></div></div>';
    }
    const end=document.getElementById('proposal-to');
    if(end){
      window.initDateInputs?.(end.closest('label')||document);end.readOnly=false;end.disabled=false;end.removeAttribute('readonly');end.removeAttribute('disabled');end.style.pointerEvents='';end.style.userSelect='';end.tabIndex=0;end.placeholder='Tự động tính ngày kết thúc';
      let hidden=document.getElementById('proposal-to-hidden');
      if(!hidden){hidden=document.createElement('input');hidden.type='hidden';hidden.id='proposal-to-hidden';hidden.name='to_date';end.removeAttribute('name');end.after(hidden);}
      hidden.value=end.value||'';
    }
    const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);
    let node;
    while(node=walker.nextNode()){
      node.nodeValue=node.nodeValue.replace(/Đi\s+.{1,8}ng/g,'Đi đường').replace(/đi\s+.{1,8}ng/g,'đi đường');
    }
  };
  const form=document.getElementById('leave-proposal-form');
  document.addEventListener('click',event=>{
    const tab=event.target.closest('[data-tab-days]');
    if(!tab)return;
    const box=tab.closest('#server-military-extra');
    if(!box)return;
    event.preventDefault();event.stopPropagation();
    box.querySelectorAll('[data-tab-days]').forEach(item=>{item.classList.toggle('bg-white',item===tab);item.classList.toggle('text-blue-700',item===tab);item.classList.toggle('text-slate-500',item!==tab);});
    box.querySelectorAll('[data-panel-days]').forEach(panel=>panel.classList.toggle('hidden',panel.dataset.panelDays!==tab.dataset.tabDays));
  },true);
  form?.addEventListener('change',event=>{
    if(!event.target.matches('.server-extra-check,.military-extra,.generic-extra'))return;
    if(event.target.checked){form.querySelectorAll('.server-extra-check,.military-extra,.generic-extra').forEach(item=>{if(item!==event.target)item.checked=false;});}
  });
  document.getElementById('proposal-scope')?.addEventListener('change',event=>{
    const extra=document.getElementById('server-military-extra')||document.getElementById('personal-extra-box');
    if(extra)extra.classList.toggle('hidden',event.target.value!=='PERSONAL');
  });
  fixLeaveFields();
  [50,250,750].forEach(delay=>setTimeout(fixLeaveFields,delay));
})();
</script>
<script>
(()=>{const form=document.getElementById('leave-proposal-form'),scope=document.getElementById('proposal-scope'),cls=document.getElementById('proposal-class'),classField=document.getElementById('class-field'),personField=document.getElementById('personnel-field'),shortBox=document.getElementById('short-people'),shortList=document.getElementById('short-people-list'),typeField=document.getElementById('leave-type-field'),from=document.getElementById('proposal-from'),to=document.getElementById('proposal-to'),reason=document.getElementById('proposal-reason'),annual=document.getElementById('annual-fields');function selectedPeople(){const option=cls.options[cls.selectedIndex];try{return JSON.parse(option?.dataset.people||'[]')}catch(e){return[]}}function setDate(input,value){if(window.setDateInputValue)window.setDateInputValue(input,value,false);else input.value=value;}function setWeekend(){if(scope.value!=='SHORT_LEAVE'||!from.value)return;const d=new Date(from.value+'T00:00:00'),fr=new Date(d);fr.setDate(d.getDate()+(5-d.getDay()+7)%7);setDate(from,fr.toISOString().slice(0,10));const su=new Date(fr);su.setDate(fr.getDate()+2);setDate(to,su.toISOString().slice(0,10))}function render(){const s=scope.value,isPersonal=s==='PERSONAL',isShort=s==='SHORT_LEAVE';classField.classList.toggle('hidden',isPersonal);personField.classList.toggle('hidden',!isPersonal);shortBox.classList.toggle('hidden',!isShort);typeField.classList.toggle('hidden',!isPersonal);annual.classList.toggle('hidden',!isPersonal);cls.required=!isPersonal;shortList.innerHTML='';if(isShort)selectedPeople().forEach(p=>{const label=document.createElement('label');label.className='flex items-center gap-2 rounded-lg bg-white p-3';label.innerHTML=`<input type="checkbox" name="personnel_ids[]" value="${p.id}" class="short-person h-4 w-4 accent-blue-600"><span class="font-semibold text-slate-700">${p.code||''} — ${p.name}</span>`;shortList.appendChild(label)});if(s==='CLASS'){reason.value='Nghỉ hè theo kế hoạch của lớp';reason.placeholder='Lý do lớp nghỉ';}else if(isShort){reason.value='';reason.placeholder='Ghi chú nghỉ tranh thủ (nếu có)';setWeekend()}else{reason.value='';reason.placeholder='Nhập lý do nghỉ cá nhân';}}document.getElementById('check-all-short').addEventListener('click',()=>document.querySelectorAll('.short-person').forEach(x=>x.checked=true));scope.addEventListener('change',render);cls.addEventListener('change',render);from.addEventListener('change',()=>{if(scope.value==='SHORT_LEAVE')setWeekend()});render()})();
</script>
<script>
(()=>{
  const leaveType = document.getElementById('proposal-leave-type');
  if (leaveType && !Array.from(leaveType.options).some(option => option.value === 'EXTRA')) {
    const extra = document.createElement('option');
    extra.value = 'EXTRA';
    extra.textContent = 'Phép đặc biệt';
    leaveType.appendChild(extra);
  }
})();
</script>
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form'),summary=document.getElementById('leave-summary-text');
  if(!form||!summary)return;
  const scope=document.getElementById('proposal-scope');
  const configuredBase=Number(@json($militaryAnnualDays ?? 0)),service=Number(@json($militaryServiceYears ?? 0));
  const syncFinalSummary=()=>{
    if(scope&&['CLASS','SHORT_LEAVE','HSQBS_ANNUAL','HSQBS_SPECIAL'].includes(scope.value))return;
    const travel=Number(form.querySelector('input[name="travel_days"]')?.value||0);
    const extra=Array.from(form.querySelectorAll('input[name="extra_standard_ids[]"]:checked'))
      .reduce((sum,item)=>sum+Number(item.dataset.days||0),0);
    const baseMatch=summary.textContent.match(/Ph[^0-9]*(\d+)\s*ng/);
    const base=configuredBase||Number(baseMatch?.[1]||0);
    const total=base+travel+extra;
    summary.innerHTML=`<div class="flex min-w-max items-center gap-8 text-sm font-bold text-slate-900">
      <div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Thâm niên</span><span>${service} năm</span></div>
      <div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Phép cơ bản</span><span>${base} ngày</span></div>
      <div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Đi đường</span><span>${travel} ngày</span></div>
      <div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Nghỉ thêm</span><span>${extra} ngày</span></div>
      <div class="whitespace-nowrap rounded-lg bg-blue-50 px-3 py-2 text-slate-900"><span class="mr-2 text-xs font-semibold text-slate-500">Tổng</span><span>${total} ngày</span></div>
    </div>`;
    const from=document.getElementById('proposal-from'),to=document.getElementById('proposal-to');
    if(to){const toLabel=to.closest('label');if(toLabel){toLabel.classList.remove('hidden');toLabel.style.display='block';}}
    if(from?.value&&to&&to.dataset.manualEdited!=='1'){const date=new Date(`${from.value}T00:00:00`);date.setDate(date.getDate()+total-1);const iso=date.toISOString().slice(0,10);to.readOnly=false;to.disabled=false;window.setDateInputValue?window.setDateInputValue(to,iso,false):to.value=iso;const hidden=document.getElementById('proposal-to-hidden');if(hidden)hidden.value=iso;const display=document.getElementById('proposal-to-display');if(display)display.textContent=iso;}
    const totalBox=document.getElementById('military-total-days');
    if(totalBox)totalBox.textContent=`${total} ngày`;
  };
  form.addEventListener('input',syncFinalSummary);
  form.addEventListener('change',syncFinalSummary);
  const annualInputSelector='#proposal-from,#proposal-travel,input[name="extra_standard_ids[]"]';
  const blockLegacyAnnualHandlers=event=>{
    const target=event.target;
    if(scope&&['CLASS','SHORT_LEAVE','HSQBS_ANNUAL','HSQBS_SPECIAL'].includes(scope.value))return;
    if(target?.matches?.(annualInputSelector)){
      if(target.matches('input[name="extra_standard_ids[]"]')&&target.checked){
        form.querySelectorAll('input[name="extra_standard_ids[]"]').forEach(item=>{if(item!==target)item.checked=false;});
      }
      event.stopImmediatePropagation();
      syncFinalSummary();
    }
  };
  form.addEventListener('input',blockLegacyAnnualHandlers,true);
  form.addEventListener('change',blockLegacyAnnualHandlers,true);
  setTimeout(syncFinalSummary,0);
  const observer=new MutationObserver(records=>{
    if(records.some(record=>Array.from(record.addedNodes).some(node=>node.nodeType===1&&(node.id==='proposal-to'||node.querySelector?.('#proposal-to')))))setTimeout(syncFinalSummary,0);
  });
  observer.observe(form,{childList:true,subtree:true});
})();
</script>
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form'),summary=document.getElementById('leave-summary-text');
  if(!form||!summary||!@json($isMilitaryAccount ?? false))return;
  const base=Number(@json($militaryAnnualDays ?? 0)),service=Number(@json($militaryServiceYears ?? 0));
  const syncFinalSummary=()=>{
    const travel=Number(form.querySelector('input[name="travel_days"]')?.value||0);
    const extra=Array.from(form.querySelectorAll('input[name="extra_standard_ids[]"]:checked')).reduce((sum,item)=>sum+Number(item.dataset.days||0),0);
    const total=base+travel+extra;
    summary.innerHTML=`<div class="flex min-w-max items-center gap-8 text-sm font-bold text-slate-900"><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Thâm niên</span><span>${service} năm</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Phép cơ bản</span><span>${base} ngày</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Đi đường</span><span>${travel} ngày</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Nghỉ thêm</span><span>${extra} ngày</span></div><div class="whitespace-nowrap rounded-lg bg-blue-50 px-3 py-2 text-slate-900"><span class="mr-2 text-xs font-semibold text-slate-500">Tổng</span><span>${total} ngày</span></div></div>`;
    const totalBox=document.getElementById('military-total-days');
    if(totalBox)totalBox.textContent=`${total} ngày`;
  };
  form.addEventListener('input',syncFinalSummary);
  form.addEventListener('change',syncFinalSummary);
  setTimeout(syncFinalSummary,0);
})();
</script>
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form'),summary=document.getElementById('leave-summary-text');
  if(!form||!summary||!@json($isMilitaryAccount ?? false))return;
  const base=Number(@json($militaryAnnualDays ?? 0)),service=Number(@json($militaryServiceYears ?? 0));
  const syncFinalSummary=()=>{
    const travel=Number(form.querySelector('input[name="travel_days"]')?.value||0);
    const extra=Array.from(form.querySelectorAll('input[name="extra_standard_ids[]"]:checked')).reduce((sum,item)=>sum+Number(item.dataset.days||0),0);
    const total=base+travel+extra;
    summary.innerHTML=`Thâm niên: ${service} năm   Phép cơ bản: ${base} ngày   Đi đường: ${travel} ngày   Nghỉ thêm: ${extra} ngày   Tổng: ${total} ngày`;
    const totalBox=document.getElementById('military-total-days');
    if(totalBox)totalBox.textContent=`${total} ngày`;
  };
  form.addEventListener('input',syncFinalSummary);
  form.addEventListener('change',syncFinalSummary);
  setTimeout(syncFinalSummary,0);
})();
</script>
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form');
  if(!form)return;
  const boot=()=>window.initTomSelects?.(form);
  boot();
  ['proposal-scope','proposal-personnel','proposal-replacement','proposal-class','proposal-leave-type','proposal-locality'].forEach(id=>document.getElementById(id)?.addEventListener('change',()=>setTimeout(boot,0)));
})();
</script>
@php
    // This script is outside the permission block, so define the data here as well.
    $replacementData = ($replacementPersonnel ?? collect())->map(fn($item) => [
        'id' => (int) $item->id,
        'label' => ($item->staff_code ? $item->staff_code . ' — ' : '') . $item->name,
        'unit_id' => (int) $item->unit_id,
    ])->values();
@endphp
<script>
(() => {
    const personnel = document.getElementById('proposal-personnel');
    const replacement = document.getElementById('proposal-replacement');
    if (!replacement) return;
    const allReplacement = @json($replacementData);
    const militaryUnitId = Number(@json($militaryPersonnel?->unit_id));
    const selectedUnitId = () => {
        if (personnel?.tomselect) {
            const value = personnel.tomselect.getValue();
            const option = personnel.querySelector(`option[value="${CSS.escape(String(value || ''))}"]`);
            return Number(option?.dataset.unitId || 0);
        }
        return Number(personnel?.selectedOptions?.[0]?.dataset.unitId || militaryUnitId || 0);
    };
    const syncReplacementOptions = () => {
        const unitId = selectedUnitId();
        const matches = allReplacement.filter(item => Number(item.unit_id) === unitId);
        if (replacement.tomselect) {
            const ts = replacement.tomselect;
            ts.clear(true);
            ts.clearOptions();
            ts.addOption({value: '', text: 'Chọn quân nhân thay thế...'});
            matches.forEach(item => ts.addOption({value: String(item.id), text: item.label}));
            ts.refreshOptions(false);
        } else {
            Array.from(replacement.options).forEach(option => {
                if (option.value) option.hidden = !matches.some(item => String(item.id) === option.value);
            });
            replacement.value = '';
        }
    };
    personnel?.addEventListener('change', syncReplacementOptions);
    if (personnel?.tomselect) personnel.tomselect.on('change', syncReplacementOptions);
    requestAnimationFrame(syncReplacementOptions);
    setTimeout(syncReplacementOptions, 300);
})();
</script>
<script>
(() => {
    const tableWrap = document.getElementById('class-students-table-wrap');
    if (!tableWrap) return;

    let closeTimer = null;
    const openTable = () => {
        clearTimeout(closeTimer);
        tableWrap.classList.add('is-datepicker-open');
    };
    const closeTable = () => {
        clearTimeout(closeTimer);
        closeTimer = setTimeout(() => {
            if (!tableWrap.querySelector(':focus')) {
                tableWrap.classList.remove('is-datepicker-open');
            }
        }, 160);
    };

    tableWrap.addEventListener('focusin', event => {
        if (event.target.matches('.row-from, .row-to, .flatpickr-input')) openTable();
    });
    tableWrap.addEventListener('click', event => {
        if (event.target.closest('.date-input-control') || event.target.matches('.row-from, .row-to')) openTable();
    });
    document.addEventListener('click', event => {
        if (!tableWrap.contains(event.target) && !event.target.closest('.flatpickr-calendar')) closeTable();
        if (event.target.closest('.flatpickr-day')) closeTable();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeTable();
    });
})();
</script>
@if($canProposeForUnit)
@php
  $adminProfilePeople = $personnel->map(function ($person) {
      return [
          'id' => $person->id,
          'name' => $person->name,
          'rank' => $person->rank,
          'position' => $person->position,
          'unit' => $person->unitRelation?->name ?? $person->unit,
          'object_type' => $person->object_type,
          'enlistment_date' => $person->enlistment_date?->format('Y-m-d'),
      ];
  })->values();
  $adminProfileRules = $regulations->map(function ($rule) {
      return [
          'type' => $rule->leave_type,
          'object_type' => $rule->object_type,
          'min' => $rule->min_years,
          'max' => $rule->max_years,
          'days' => (int) $rule->base_days,
      ];
  })->values();
@endphp
<script>
(()=>{
  const scope=document.getElementById('proposal-scope'),personnel=document.getElementById('proposal-personnel'),form=document.getElementById('leave-proposal-form');
  if(!scope||!personnel||!form)return;
  const people=@json($adminProfilePeople);
  let profile=document.getElementById('admin-personal-profile');
  if(!profile){profile=document.createElement('div');profile.id='admin-personal-profile';profile.className='hidden order-[-10] rounded-2xl border border-blue-100 bg-blue-50/40 p-4 md:col-span-2';form.querySelector('.border-b')?.before(profile);}
  const updateSummary=(years,base)=>{const summary=document.getElementById('leave-summary-text');if(!summary)return;const travel=Number(document.getElementById('proposal-travel')?.value||0),extra=Array.from(form.querySelectorAll('.generic-extra:checked,.server-extra-check:checked,.military-extra:checked')).reduce((sum,item)=>sum+Number(item.dataset.days||0),0),total=base+travel+extra;summary.innerHTML='<div class="flex min-w-max items-center gap-8 text-sm font-bold text-slate-900"><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Th&acirc;m ni&ecirc;n</span><span>'+years+' n&#x103;m</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Ph&eacute;p c&#x1a1; b&#x1ea3;n</span><span>'+base+' ng&agrave;y</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">&#x110;i &#x111;&#x1b0;&#x1edd;ng</span><span>'+travel+' ng&agrave;y</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Ngh&#x1ec9; th&ecirc;m</span><span>'+extra+' ng&agrave;y</span></div><div class="whitespace-nowrap rounded-lg bg-blue-50 px-3 py-2 text-slate-900"><span class="mr-2 text-xs font-semibold text-slate-500">T&#x1ed5;ng</span><span>'+total+' ng&agrave;y</span></div></div>';};
  const render=()=>{
    const isPersonal=scope.value==='PERSONAL';
    const person=people.find(item=>String(item.id)===String(personnel.value));
    profile.classList.toggle('hidden',!isPersonal||!person);
    if(!isPersonal||!person)return;
    const years=person.enlistment_date?Math.max(0,new Date().getFullYear()-new Date(person.enlistment_date+'T00:00:00').getFullYear()):0;
    const rules=@json($adminProfileRules);
    const matched=rules.filter(rule=>rule.type==='ANNUAL'&&(!rule.object_type||rule.object_type===person.object_type)&&(rule.min===null||rule.min===undefined||rule.min===''||rule.min<=years)&&(rule.max===null||rule.max===undefined||rule.max===''||rule.max>=years)).sort((a,b)=>Number(Boolean(b.object_type&&b.object_type===person.object_type))-Number(Boolean(a.object_type&&a.object_type===person.object_type))||Number(b.min||0)-Number(a.min||0))[0];
    const field=(label,value)=>`<div><div class="text-xs font-semibold text-slate-500">${label}</div><div class="mt-1 rounded-lg bg-white px-3 py-2.5 font-semibold text-slate-800">${value||'—'}</div></div>`;
    profile.innerHTML='<div class="mb-3 text-sm font-extrabold text-slate-900">Thông tin phép của quân nhân</div><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">'+field('Họ tên',person.name)+field('Ngày nhập ngũ / tuyển dụng',person.enlistment_date?person.enlistment_date.split('-').reverse().join('/'):'—')+field('Cấp bậc',person.rank)+field('Chức vụ',person.position)+field('Đơn vị',person.unit)+field('Thâm niên / Phép cơ bản',years+' năm — '+Number(matched?.days||0)+' ngày')+'</div>';
    updateSummary(years,Number(matched?.days||0));
  };
  scope.addEventListener('change',()=>setTimeout(render,0));personnel.addEventListener('change',()=>setTimeout(render,0));form.addEventListener('input',()=>setTimeout(render,0));form.addEventListener('change',()=>setTimeout(render,0));render();
})();
</script>
@if($canProposeForUnit)
@php
  $proposalPeople = $personnel->map(fn($p) => [
    'id' => $p->id,
    'name' => $p->name,
    'staff_code' => $p->staff_code,
    'rank' => $p->rank,
    'position' => $p->position,
    'unit' => $p->unitRelation?->name ?? $p->unit,
    'commander' => $p->commander?->name ?? $p->commander_name,
    'enlistment_date' => $p->enlistment_date?->format('Y-m-d'),
    'permanent_residence' => $p->permanent_residence,
     'object_type' => $p->object_type,
     'managing_agency' => \Modules\LeaveManagement\Support\LeaveAccess::agencyForObject($p->object_type),
  ])->values();
  $proposalRegulations = $regulations->map(fn($r) => [
    'type' => $r->leave_type,
    'object_type' => $r->object_type,
    'min' => $r->min_years,
    'max' => $r->max_years,
    'days' => (int) $r->base_days,
  ])->values();
  $proposalExtras = $extraStandards->map(fn($r) => [
    'id' => $r->id,
    'label' => $r->label,
    'days' => (int) $r->base_days,
  ])->values();
@endphp
<script>
(()=>{
  const scope = document.getElementById('proposal-scope');
  const leaveType = document.getElementById('proposal-leave-type');
  if (!scope || !leaveType) return;
  const addScope = (value, label) => {
    if (Array.from(scope.options).some(option => option.dataset.leaveType === value)) return;
    const option = document.createElement('option');
    option.value = 'ADMIN_EXTRA';
    option.dataset.leaveType = value;
    option.textContent = label;
    scope.appendChild(option);
  };
  addScope('EXTRA', 'Phép đặc biệt — một quân nhân');
  const adminOptions = [
    { value: 'ADMIN_EXTRA', text: 'Phép đặc biệt — một quân nhân' },
  ];
  if (scope.tomselect) {
    adminOptions.forEach(option => scope.tomselect.addOption(option));
    scope.tomselect.refreshOptions(false);
  }
  const applyAdminScope = (value) => {
    const isPersonal = value === 'PERSONAL' || value === 'ADMIN_EXTRA';
    document.getElementById('class-field')?.classList.toggle('hidden', isPersonal);
    document.getElementById('personnel-field')?.classList.toggle('hidden', !isPersonal);
    document.getElementById('leave-type-field')?.classList.toggle('hidden', !isPersonal);
    document.getElementById('annual-fields')?.classList.toggle('hidden', !isPersonal);
    const classSelect = document.getElementById('proposal-class');
    if (classSelect) classSelect.required = !isPersonal;
    if (value === 'ADMIN_EXTRA') leaveType.value = 'EXTRA';
  };
  if (scope.tomselect) scope.tomselect.on('change', applyAdminScope);
  scope.addEventListener('change', () => applyAdminScope(scope.value));
  const syncReasonRequired=()=>{
    const value=scope.tomselect?scope.tomselect.getValue():scope.value;
    const personal=value==='PERSONAL'||value==='ADMIN_EXTRA';
    const leaveType=document.getElementById('proposal-leave-type')?.value||'';
    const needsReason=personal&&!['ANNUAL','EXTRA'].includes(leaveType);
    const reason=document.getElementById('proposal-reason'),mark=document.getElementById('reason-required-mark');
    if(reason)reason.required=needsReason;
    mark?.classList.toggle('hidden',!needsReason);
  };
  scope.addEventListener('change',syncReasonRequired);
  scope.tomselect?.on('change',syncReasonRequired);
  document.getElementById('proposal-leave-type')?.addEventListener('change',syncReasonRequired);
  syncReasonRequired();
  document.getElementById('leave-proposal-form')?.addEventListener('submit', () => {
    if (scope.value === 'ADMIN_EXTRA') {
      if (scope.tomselect) scope.tomselect.setValue('PERSONAL', true);
      else scope.value = 'PERSONAL';
    }
  });
})();
</script>
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form');
  const scope=document.getElementById('proposal-scope');
  const classPanel=document.getElementById('class-scope-panel');
  const infoPanel=document.getElementById('class-info-panel');
  const classScope=document.getElementById('class-scope-select');
  const classSelect=document.getElementById('class-class-select');
  const unitSelect=document.getElementById('class-unit-select');
  const agencySelect=document.getElementById('class-agency-select');
  const oldClass=document.getElementById('proposal-class');
  const classLocalities=@json($localityData);
  const allProposalPeople=@json($proposalPeopleData, JSON_UNESCAPED_UNICODE);
  if(!form||!scope||!classPanel||!infoPanel||!classSelect||!unitSelect||!oldClass)return;

  const groupScopes=['CLASS','SHORT_LEAVE','HSQBS_ANNUAL','HSQBS_SPECIAL'];
  const fixedDaysByScope={HSQBS_ANNUAL:10,HSQBS_SPECIAL:5};
  const reasonByScope={
    CLASS:'Phép hằng năm của học viên',
    HSQBS_ANNUAL:'Phép hàng năm của HSQBS',
    HSQBS_SPECIAL:'Phép đặc biệt của HSQBS',
    SHORT_LEAVE:''
  };
  const genericHeading=Array.from(form.children||[]).find(el=>el.classList.contains('border-b'));
  const genericFields=['proposal-scope','proposal-class','proposal-personnel','proposal-replacement','proposal-leave-type','proposal-from','proposal-to','proposal-travel','proposal-reason','proposal-locality'].map(id=>document.getElementById(id));
  const wrapper=el=>el?.closest('label');
  const hiddenManual=document.createElement('input');
  hiddenManual.type='hidden';
  hiddenManual.name='manual_days';
  hiddenManual.id='class-manual-days-hidden';
  form.appendChild(hiddenManual);

  const selectedScope=()=>scope.tomselect?scope.tomselect.getValue():scope.value;
  const isGroupScope=value=>groupScopes.includes(value);
  const isHsqbsScope=value=>value==='HSQBS_ANNUAL'||value==='HSQBS_SPECIAL';
  const activeGroupSelect=()=>isHsqbsScope(selectedScope())?unitSelect:classSelect;
  const syncRowSelection=()=>{
    document.querySelectorAll('#class-students tr').forEach(row=>{
      const checked=!!row.querySelector('.selected-person')?.checked;
      row.classList.toggle('bg-blue-50/40',checked);
      row.querySelectorAll('.row-from,.row-to,select[name^="class_leave_locations"]').forEach(input=>{
        input.disabled=!checked;
        input.classList.toggle('bg-slate-100',!checked);
        input.classList.toggle('cursor-not-allowed',!checked);
        if(input._flatpickr?.altInput){
          input._flatpickr.altInput.disabled=!checked;
          input._flatpickr.altInput.classList.toggle('bg-slate-100',!checked);
          input._flatpickr.altInput.classList.toggle('cursor-not-allowed',!checked);
        }
      });
      row.querySelectorAll('.row-from,.flatpickr-input').forEach(input=>input.removeAttribute('required'));
    });
  };

  const syncStudents=()=>{
    const value=selectedScope(),hsqbs=isHsqbsScope(value),selector=activeGroupSelect(),option=selector.options[selector.selectedIndex],body=document.getElementById('class-students');
    let people=[];try{people=JSON.parse(option?.dataset.people||'[]');}catch(e){}
    if(!people.length&&selector.value){
      const selectedText=(option?.textContent||'').split(' — ')[0].trim().toLowerCase();
      people=allProposalPeople.filter(p=>hsqbs?String(p.unit_id)===String(selector.value):(String(p.class_id)===String(selector.value)||String(p.class||p.class_name||'').trim().toLowerCase()===selectedText));
    }
    document.getElementById('class-student-select-head')?.classList.remove('hidden');
    const selectAllButton=document.getElementById('select-all-short-students');
    if(selectAllButton){
      selectAllButton.classList.remove('hidden');
      selectAllButton.textContent='Tích tất cả';
    }
    body.innerHTML=people.map(p=>`<tr class="transition-colors hover:bg-blue-50/50" data-person-id="${p.id}"><td class="px-4 py-3"><input type="checkbox" name="personnel_ids[]" value="${p.id}" class="selected-person short-person h-4 w-4 accent-blue-600"></td><td class="px-4 py-3"><div class="font-bold text-slate-800">${p.name||''}</div><div class="mt-0.5 text-xs font-medium text-slate-400">${p.code||''}</div></td><td class="px-4 py-3"><span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">${p.class||(!hsqbs?option?.textContent.split(' — ')[0]:'')}</span></td><td class="px-4 py-3"><input type="date" data-floating-calendar name="person_from_dates[${p.id}]" class="row-from w-full rounded-lg border-slate-200 px-3 py-2 text-sm shadow-sm"></td><td class="px-4 py-3"><input type="date" data-floating-calendar name="person_to_dates[${p.id}]" class="row-to w-full rounded-lg border-slate-200 px-3 py-2 text-sm shadow-sm"></td><td class="px-4 py-3"><select name="class_leave_locations[${p.id}]" class="w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"><option value="">Chọn phường/xã — tỉnh/thành...</option>${classLocalities.filter(locality=>locality.parent_name).map(locality=>`<option value="${locality.id}">${locality.label}</option>`).join('')}</select></td></tr>`).join('');
    window.initDateInputs?.(body);
    body.querySelectorAll('.selected-person').forEach(input=>input.addEventListener('change',syncRowSelection));
    syncRowSelection();
    if(isGroupScope(value)){
      body.querySelectorAll('.row-from').forEach(input=>['input','change'].forEach(eventName=>input.addEventListener(eventName,()=>{const row=input.closest('tr');row.querySelector('.row-to')?.removeAttribute('data-manual-edited');calculateEnd();})));
      body.querySelectorAll('.row-to').forEach(input=>['input','change'].forEach(eventName=>input.addEventListener(eventName,()=>{input.dataset.manualEdited='1';syncValues();})));
    }
    document.getElementById('class-object').value=option?.textContent.trim()||'';
    document.getElementById('class-unit').value=hsqbs?(option?.textContent.trim()||''):(option?.textContent.split(' — ')[1]||'');
  };

  const syncValues=()=>{
    const reason=document.getElementById('class-reason'),days=document.getElementById('class-days'),travel=document.getElementById('class-travel'),from=document.getElementById('class-from'),to=document.getElementById('class-to'),oldFrom=document.getElementById('proposal-from'),oldTo=document.getElementById('proposal-to'),oldReason=document.getElementById('proposal-reason'),oldTravel=document.getElementById('proposal-travel'),hiddenTo=document.getElementById('proposal-to-hidden');
    const firstRow=document.querySelector('#class-students .selected-person:checked')?.closest('tr')||document.querySelector('#class-students tr');
    const firstFrom=firstRow?.querySelector('.row-from')?.value||from.value||'';
    const firstTo=firstRow?.querySelector('.row-to')?.value||to.value||'';
    oldClass.value=isHsqbsScope(selectedScope())?'':classSelect.value;
    hiddenManual.value=days.value||1;
    oldFrom.value=firstFrom;
    oldReason.value=reason.value||'';
    if(oldTravel)oldTravel.value=isHsqbsScope(selectedScope())?0:(travel?.value||0);
    oldTo.value=firstTo;
    if(hiddenTo)hiddenTo.value=firstTo;
  };

  const calculateEnd=()=>{
    const value=selectedScope(),from=document.getElementById('class-from'),to=document.getElementById('class-to'),daysInput=document.getElementById('class-days'),travelInput=document.getElementById('class-travel');
    const days=Number(daysInput?.value||0);
    const travel=isHsqbsScope(value)?0:Number(travelInput?.value||0);
    if(isGroupScope(value)){
      document.querySelectorAll('#class-students tr').forEach(row=>{
        const rowFrom=row.querySelector('.row-from'),rowTo=row.querySelector('.row-to');
        if(rowFrom&&!rowFrom.value&&from.value){
          if(window.setDateInputValue)window.setDateInputValue(rowFrom,from.value,false);
          else rowFrom.value=from.value;
        }
        if(rowFrom?.value&&rowTo&&rowTo.dataset.manualEdited!=='1'){
          const parts=rowFrom.value.split('-').map(Number),d=new Date(parts[0],parts[1]-1,parts[2]);
          d.setDate(d.getDate()+days+travel-1);
          const iso=[d.getFullYear(),String(d.getMonth()+1).padStart(2,'0'),String(d.getDate()).padStart(2,'0')].join('-');
          window.setDateInputValue?window.setDateInputValue(rowTo,iso,false):rowTo.value=iso;
        }
      });
      syncValues();
      return;
    }
    if(to?.dataset.manualEdited==='1'){syncValues();return;}
    if(from.value&&days>0){
      const parts=from.value.split('-').map(Number),d=new Date(parts[0],parts[1]-1,parts[2]);
      d.setDate(d.getDate()+days+travel-1);
      const iso=[d.getFullYear(),String(d.getMonth()+1).padStart(2,'0'),String(d.getDate()).padStart(2,'0')].join('-');
      window.setDateInputValue?window.setDateInputValue(to,iso,false):to.value=iso;
    }else{
      window.setDateInputValue?window.setDateInputValue(to,'',false):to.value='';
    }
    syncValues();
  };

  const render=()=>{
    const value=selectedScope(),isGroup=isGroupScope(value),hsqbs=isHsqbsScope(value),fixedDays=fixedDaysByScope[value]||0;
    classPanel.classList.toggle('hidden',!isGroup);
    infoPanel.classList.toggle('hidden',!isGroup);
    document.getElementById('class-class-field')?.classList.toggle('hidden',hsqbs);
    document.getElementById('class-unit-field')?.classList.toggle('hidden',!hsqbs);
    if(agencySelect){
      agencySelect.disabled=!isGroup;
      agencySelect.required=isGroup;
    }
    document.getElementById('class-from-field')?.classList.toggle('hidden',isGroup);
    document.getElementById('class-to-field')?.classList.toggle('hidden',isGroup);
    genericHeading?.classList.toggle('hidden',isGroup);
    genericFields.forEach(el=>wrapper(el)?.classList.toggle('hidden',isGroup));
    document.getElementById('leave-summary-text')?.parentElement.classList.toggle('hidden',isGroup);
    Array.from(form.querySelectorAll('div')).find(el=>el.textContent.includes('Phép lớp tạo một đề xuất'))?.classList.toggle('hidden',isGroup);
    document.getElementById('short-people')?.classList.add('hidden');
    document.getElementById('class-travel-field')?.classList.toggle('hidden',hsqbs||!fixedDays);
    const days=document.getElementById('class-days'),daysLabel=document.getElementById('class-days-label'),daysHelp=document.getElementById('class-days-help'),reason=document.getElementById('class-reason');
    if(fixedDays){days.value=fixedDays;days.readOnly=true;days.classList.add('bg-slate-100');daysLabel.innerHTML='Tổng phép <b class="text-red-500">*</b>';daysHelp.textContent='Theo quy định HSQBS.';const travel=document.getElementById('class-travel');if(travel)travel.value=0;}
    else{days.readOnly=false;days.classList.remove('bg-slate-100');daysLabel.innerHTML='Số ngày nghỉ <b class="text-red-500">*</b>';daysHelp.textContent='Đại đội nhập trực tiếp, không tính theo thâm niên.';}
    if(isGroup&&reasonByScope[value]!==undefined)reason.value=reasonByScope[value];
    if(isGroup){if(!hsqbs)classSelect.value=oldClass.value||classSelect.value;syncStudents();calculateEnd();}else{oldClass.required=false;}
  };

  const resetManualEnd=()=>document.getElementById('class-to')?.removeAttribute('data-manual-edited');
  classScope.addEventListener('change',()=>{resetManualEnd();if(scope.tomselect)scope.tomselect.setValue(classScope.value);else{scope.value=classScope.value;scope.dispatchEvent(new Event('change'));}render();});
  document.getElementById('select-all-short-students')?.addEventListener('click',()=>{document.querySelectorAll('#class-students .selected-person').forEach(input=>input.checked=true);syncRowSelection();});
  classSelect.addEventListener('change',()=>{resetManualEnd();syncStudents();calculateEnd();});
  unitSelect.addEventListener('change',()=>{resetManualEnd();syncStudents();calculateEnd();});
  document.getElementById('class-reason').addEventListener('input',syncValues);
  document.getElementById('class-days').addEventListener('input',()=>{resetManualEnd();calculateEnd();});
  document.getElementById('class-days').addEventListener('change',()=>{resetManualEnd();calculateEnd();});
  document.getElementById('class-travel')?.addEventListener('input',()=>{resetManualEnd();calculateEnd();});
  document.getElementById('class-travel')?.addEventListener('change',()=>{resetManualEnd();calculateEnd();});
  document.getElementById('class-from').addEventListener('input',()=>{resetManualEnd();calculateEnd();});
  document.getElementById('class-from').addEventListener('change',()=>{resetManualEnd();calculateEnd();});
  document.getElementById('class-to').addEventListener('input',()=>{document.getElementById('class-to').dataset.manualEdited='1';syncValues();});
  form.addEventListener('submit',event=>{const value=selectedScope();if(isGroupScope(value)){syncRowSelection();syncValues();const checkedRows=Array.from(document.querySelectorAll('#class-students .selected-person:checked')).map(input=>input.closest('tr'));if(!agencySelect?.value){event.preventDefault();alert('Vui lòng chọn cơ quan quản lý tiếp nhận đề xuất.');return;}if(!checkedRows.length){event.preventDefault();alert('Vui lòng tích ít nhất một quân nhân/học viên cần đề xuất nghỉ phép.');return;}if(checkedRows.some(row=>!row.querySelector('.row-from')?.value)){event.preventDefault();alert('Vui lòng nhập ngày đi cho quân nhân/học viên đã tích.');}}});
  scope.addEventListener('change',()=>{classScope.value=selectedScope();render();});
  scope.tomselect?.on('change',render);
  render();
  setTimeout(calculateEnd,0);
})();
</script>
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form');
  if(!form)return;
  const selectedScope=()=>{
    const scope=document.getElementById('proposal-scope');
    return scope?.tomselect?scope.tomselect.getValue():scope?.value;
  };
  const syncSelectableRows=()=>{
    const tableScope=['CLASS','SHORT_LEAVE','HSQBS_ANNUAL','HSQBS_SPECIAL'].includes(selectedScope());
    document.querySelectorAll('#class-students tr').forEach(row=>{
      const checked=tableScope&&!!row.querySelector('.selected-person')?.checked;
      row.querySelectorAll('.row-from,.flatpickr-input').forEach(input=>input.removeAttribute('required'));
      row.querySelectorAll('.row-from,.row-to,select[name^="class_leave_locations"]').forEach(input=>{
        input.disabled=!checked;
        input.removeAttribute('required');
        if(input._flatpickr?.altInput){
          input._flatpickr.altInput.disabled=!checked;
          input._flatpickr.altInput.removeAttribute('required');
        }
      });
    });
  };
  document.addEventListener('change',event=>{
    if(event.target.matches('#class-students .selected-person'))syncSelectableRows();
  },true);
  document.getElementById('leave-proposal-submit')?.addEventListener('click',syncSelectableRows,true);
  form.addEventListener('submit',syncSelectableRows,true);
  [0,50,250,750].forEach(delay=>setTimeout(syncSelectableRows,delay));
})();
</script>
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form'),scope=document.getElementById('proposal-scope'),classField=document.getElementById('class-field'),shortPeople=document.getElementById('short-people'),classScopePanel=document.getElementById('class-scope-panel'),classInfoPanel=document.getElementById('class-info-panel');
  if(!scope)return;
   const sync=()=>{const value=scope.tomselect?scope.tomselect.getValue():scope.value;const isClass=value==='CLASS',isShort=value==='SHORT_LEAVE',isHsqbs=value==='HSQBS_ANNUAL'||value==='HSQBS_SPECIAL',isGroup=isClass||isShort||isHsqbs,personal=value==='PERSONAL'||value==='ADMIN_EXTRA';const genericIds=['proposal-scope','proposal-personnel','proposal-replacement','proposal-leave-type','proposal-from','proposal-to','proposal-travel','proposal-reason','proposal-locality'];classField?.classList.toggle('hidden',personal||isGroup);scope.required=false;document.getElementById('proposal-class')?.removeAttribute('required');document.getElementById('class-class-select')?.toggleAttribute('required',isGroup&&!isHsqbs);document.getElementById('class-unit-select')?.toggleAttribute('required',isHsqbs);const agency=document.getElementById('class-agency-select');if(agency){agency.disabled=!isGroup;agency.required=isGroup;}document.getElementById('class-from')?.removeAttribute('required');['proposal-from','proposal-to'].forEach(id=>{const input=document.getElementById(id);if(input)input.required=!isGroup;});shortPeople?.classList.add('hidden');classScopePanel?.classList.toggle('hidden',!isGroup);classInfoPanel?.classList.toggle('hidden',!isGroup);Array.from(form?.children||[]).find(el=>el.classList.contains('border-b'))?.classList.toggle('hidden',isGroup);genericIds.forEach(id=>document.getElementById(id)?.closest('label')?.classList.toggle('hidden',isGroup));document.getElementById('proposal-locality-field')?.classList.toggle('hidden',isGroup);document.getElementById('leave-summary-text')?.parentElement.classList.toggle('hidden',isGroup);};
  scope.addEventListener('change',()=>setTimeout(sync,0));scope.tomselect?.on('change',()=>setTimeout(sync,0));sync();[50,250,750].forEach(delay=>setTimeout(sync,delay));
})();
</script>
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form'),note=document.getElementById('proposal-reason'),from=document.getElementById('proposal-from'),to=document.getElementById('proposal-to'),travel=document.getElementById('proposal-travel'),summary=document.getElementById('leave-summary-text');
  const baseDays=Number(@json($militaryAnnualDays ?? 0)),serviceYears=Number(@json($militaryServiceYears ?? 0)),extras=@json($militaryExtraData ?? []);
  if(!@json($isMilitaryAccount ?? false)||!form||!note||!summary)return;
  const serverBox=document.getElementById('server-military-extra');if(serverBox)note.closest('label')?.after(serverBox);
  serverBox?.querySelectorAll('[data-tab-days]').forEach(tab=>tab.addEventListener('click',()=>{serverBox.querySelectorAll('[data-tab-days]').forEach(item=>{item.classList.remove('bg-white','text-blue-700');item.classList.add('text-slate-500');});tab.classList.add('bg-white','text-blue-700');tab.classList.remove('text-slate-500');serverBox.querySelectorAll('[data-panel-days]').forEach(panel=>panel.classList.toggle('hidden',panel.dataset.panelDays!==tab.dataset.tabDays));}));
  if(!document.getElementById('military-extra-standards')){
    const box=document.createElement('section');box.id='military-extra-standards';box.className='mt-4 rounded-xl border border-slate-200 bg-white p-3 md:col-span-2';
    const groups=[5,10].map(days=>({days:days,items:extras.filter(item=>Number(item.days)===days)})).filter(group=>group.items.length);
    let html='<div class="mb-3 text-sm font-extrabold text-slate-800">Ph&eacute;p th&ecirc;m</div>';
    if(groups.length){html+='<div class="overflow-hidden rounded-xl border border-slate-200"><div class="flex border-b border-slate-200 bg-slate-50">'+groups.map((group,index)=>'<button type="button" data-extra-tab="'+group.days+'" class="px-5 py-3 text-sm font-extrabold '+(index===0?'bg-white text-blue-700':'text-slate-500')+'">'+group.days+' ng&agrave;y</button>').join('')+'</div>'+groups.map((group,index)=>'<div data-extra-panel="'+group.days+'" class="space-y-2 p-3 '+(index?'hidden':'')+'">'+group.items.map(item=>'<label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 hover:border-blue-300"><input type="checkbox" name="extra_standard_ids[]" value="'+item.id+'" data-days="'+item.days+'" class="military-extra mt-1 h-4 w-4 accent-blue-600"><span><span class="block font-semibold text-slate-700">'+(item.label||'Ph&eacute;p th&ecirc;m')+'</span><span class="mt-1 block text-xs text-slate-500">'+item.days+' ng&agrave;y</span></span></label>').join('')+'</div>').join('')+'</div>';}else{html+='<div class="p-3 text-sm text-slate-500">Ch&#x1b0;a c&oacute; quy &#x111;&#x1ecb;nh ph&eacute;p th&ecirc;m.</div>';}
    box.innerHTML=html;note.closest('label')?.after(box);
    box.querySelectorAll('[data-extra-tab]').forEach(tab=>tab.addEventListener('click',()=>{box.querySelectorAll('[data-extra-tab]').forEach(item=>{item.classList.remove('bg-white','text-blue-700');item.classList.add('text-slate-500');});tab.classList.add('bg-white','text-blue-700');tab.classList.remove('text-slate-500');box.querySelectorAll('[data-extra-panel]').forEach(panel=>panel.classList.toggle('hidden',panel.dataset.extraPanel!==tab.dataset.extraTab));}));
  }
  const update=()=>{const travelDays=Number(travel?.value||0),extraDays=Array.from(form.querySelectorAll('#military-extra-standards .military-extra:checked')).reduce((sum,item)=>sum+Number(item.dataset.days||0),0),total=baseDays+travelDays+extraDays;summary.innerHTML='<div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-bold text-slate-900"><span>Th&acirc;m ni&ecirc;n: '+serviceYears+' n&#x103;m</span><span>Ph&eacute;p c&#x1a1; b&#x1ea3;n: '+baseDays+' ng&agrave;y</span><span>&#x110;i &#x111;&#x1b0;&#x7901;ng: '+travelDays+' ng&agrave;y</span><span>Ngh&#x1ec9; th&ecirc;m: '+extraDays+' ng&agrave;y</span><span>T&#x1ed5;ng: '+total+' ng&agrave;y</span></div>';if(from?.value&&to&&to.dataset.manualEdited!=='1'){const date=new Date(from.value+'T00:00:00');date.setDate(date.getDate()+Math.max(0,total-1));const iso=date.toISOString().slice(0,10);window.setDateInputValue?window.setDateInputValue(to,iso,false):to.value=iso;}};
  const refreshServerExtra=()=>{const travelDays=Number(travel?.value||0),extraDays=Array.from(form.querySelectorAll('.server-extra-check:checked')).reduce((sum,item)=>sum+Number(item.dataset.days||0),0),total=baseDays+travelDays+extraDays;summary.innerHTML='<div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-bold text-slate-900"><span>Th&acirc;m ni&ecirc;n: '+serviceYears+' n&#x103;m</span><span>Ph&eacute;p c&#x1a1; b&#x1ea3;n: '+baseDays+' ng&agrave;y</span><span>&#x110;i &#x111;&#x1b0;&#x7901;ng: '+travelDays+' ng&agrave;y</span><span>Ngh&#x1ec9; th&ecirc;m: '+extraDays+' ng&agrave;y</span><span>T&#x1ed5;ng: '+total+' ng&agrave;y</span></div>';if(from?.value&&to&&to.dataset.manualEdited!=='1'){const date=new Date(from.value+'T00:00:00');date.setDate(date.getDate()+Math.max(0,total-1));const iso=date.toISOString().slice(0,10);window.setDateInputValue?window.setDateInputValue(to,iso,false):to.value=iso;}};serverBox?.querySelectorAll('.server-extra-check').forEach(item=>item.addEventListener('change',refreshServerExtra));
  form.addEventListener('input',update);form.addEventListener('change',update);update();
})();
</script>
<script>
  (()=>{const textWalker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);let textNode;while(textNode=textWalker.nextNode())textNode.nodeValue=textNode.nodeValue.replace(/\u0110i\s+\S+ng/g,'\u0110i \u0111\u01b0\u1eddng').replace(/\u0111i\s+\S+ng/g,'\u0111i \u0111\u01b0\u1eddng');})();
</script>
@endif
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form');
  const scope=document.getElementById('proposal-scope');
  if(!form||!scope)return;
  const value=()=>{
    const classScope=document.getElementById('class-scope-select');
    const classPanel=document.getElementById('class-scope-panel');
    if(classScope&&classPanel&&!classPanel.classList.contains('hidden'))return classScope.value;
    return scope.tomselect?scope.tomselect.getValue():scope.value;
  };
  const isTableScope=()=>['CLASS','SHORT_LEAVE','HSQBS_ANNUAL','HSQBS_SPECIAL'].includes(value());
  const isHsqbs=()=>['HSQBS_ANNUAL','HSQBS_SPECIAL'].includes(value());
  const sync=()=>{
    const tableScope=isTableScope();
    const hsqbs=isHsqbs();
    ['class-travel-field','class-from-field','class-to-field','proposal-locality-field'].forEach(id=>document.getElementById(id)?.classList.toggle('hidden',tableScope));
    ['proposal-from','proposal-to','proposal-travel','proposal-locality'].forEach(id=>document.getElementById(id)?.closest('label')?.classList.toggle('hidden',tableScope));
    const travel=document.getElementById('proposal-travel');
    if(hsqbs&&travel)travel.value=0;
  };
  scope.addEventListener('change',()=>setTimeout(sync,0));
  scope.tomselect?.on('change',()=>setTimeout(sync,0));
  form.addEventListener('input',()=>setTimeout(sync,0));
  form.addEventListener('change',()=>setTimeout(sync,0));
  sync();
  [50,250,750].forEach(delay=>setTimeout(sync,delay));
})();
</script>
@if($canProposeForUnit)
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form'),scope=document.getElementById('proposal-scope'),personnel=document.getElementById('proposal-personnel'),type=document.getElementById('proposal-leave-type'),from=document.getElementById('proposal-from'),to=document.getElementById('proposal-to'),travel=document.getElementById('proposal-travel');
  if(!form||!scope||!personnel)return;
  const people=@json($proposalPeople);
  const regulations=@json($proposalRegulations);
  const extras=@json($proposalExtras);
  const findPerson=()=>people.find(p=>String(p.id)===String(personnel.value));
   const years=p=>p?.enlistment_date?Math.max(0,new Date().getFullYear()-new Date(p.enlistment_date+'T00:00:00').getFullYear()):0;
  const matches=(r,p,y)=>r.type==='ANNUAL'&&(!r.object_type||r.object_type===p?.object_type)&&(r.min===null||r.min===undefined||r.min===''||r.min<=y)&&(r.max===null||r.max===undefined||r.max===''||r.max>=y);
   const baseDays=(p)=>{const y=years(p),matched=regulations.filter(r=>matches(r,p,y)),exact=matched.filter(r=>r.object_type===p?.object_type),rules=exact.length?exact:matched;return Number(rules.sort((a,b)=>Number(b.min||0)-Number(a.min||0))[0]?.days||0);};
   const panel=document.createElement('div');panel.id='personal-leave-summary';panel.className='hidden md:col-span-3 rounded-2xl border border-blue-100 bg-blue-50/40 p-4';
   const localityOptions=@json($localityData);
   const serviceYears=0, annualDays=0, localities=localityOptions, defaultLocality=null;
   panel.innerHTML='<div class="mb-3 flex items-center justify-between"><h3 class="font-extrabold text-slate-900">Thông tin phép cá nhân</h3><span id="personal-service-years" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700"></span></div><div id="personal-info-fields" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5"></div><div id="personal-extra-box" class="mt-4 hidden overflow-x-auto rounded-xl border border-slate-200 bg-white"></div><div class="mt-4 grid gap-3 md:grid-cols-2"><label class="block text-sm font-bold text-slate-700">Nơi nghỉ<select id="personal-locality" name="locality_id" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5"><option value="">Chọn phường / xã</option>'+localityOptions.filter(item=>item.parent_id).map(item=>'<option value="'+item.id+'">'+item.label+'</option>').join('')+'</select></label><div><div class="text-sm font-bold text-slate-700">Tổng ngày phép</div><div id="personal-total-days" class="mt-1 rounded-xl border border-blue-200 bg-white px-3 py-2.5 text-lg font-extrabold text-blue-700">—</div></div></div>';
   panel.className='md:col-span-2 rounded-xl border border-slate-200 bg-white p-4';
   panel.innerHTML=`<div class="mb-3 flex items-center justify-between"><div><h3 class="font-extrabold text-slate-900">Thông tin phép cá nhân</h3><p class="text-xs text-slate-500">Đơn phép này dành cho quân nhân đang đăng nhập.</p></div><span id="military-service-years" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">${serviceYears} năm thâm niên</span></div><div id="personal-info-fields" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5"></div><div id="personal-extra-box" class="mt-4 hidden overflow-x-auto rounded-xl border border-slate-200 bg-white"></div><div class="mt-4 grid gap-3 md:grid-cols-2"><label class="block"><span class="mb-1 block text-sm font-bold text-slate-700">Địa chỉ nghỉ phép</span><select id="military-locality" name="locality_id" class="w-full rounded-lg border-slate-200 px-3 py-2.5"><option value="">Chọn địa chỉ nghỉ phép</option>${localities.map(x=>`<option value="${x.id}" ${defaultLocality&&String(defaultLocality.id)===String(x.id)?'selected':''}>${x.name}</option>`).join('')}</select></label><div><span class="mb-1 block text-sm font-bold text-slate-700">Tổng ngày phép</span><div id="military-total-days" class="rounded-lg border border-blue-200 bg-white px-3 py-2.5 text-lg font-extrabold text-blue-700">${annualDays} ngày</div></div></div>`;
   panel.className='md:col-span-2 p-0';
   panel.firstElementChild?.classList.add('hidden');
    const militaryExtraBox=panel.querySelector('#personal-extra-box');
    if(militaryExtraBox){
      const groups=[5,10].map(days=>({days,items:extras.filter(x=>Number(x.days)===days)})).filter(group=>group.items.length);
      militaryExtraBox.innerHTML=groups.length
        ? `<div class="border-b border-slate-200 bg-slate-50 px-3 pt-3"><div class="flex gap-2">${groups.map((group,index)=>`<button type="button" data-extra-tab="${group.days}" class="extra-tab rounded-t-lg px-4 py-2 text-sm font-extrabold ${index===0?'bg-white text-blue-700':'text-slate-500'}">${group.days} ngày</button>`).join('')}</div></div>${groups.map((group,index)=>`<div data-extra-panel="${group.days}" class="space-y-2 p-3 ${index===0?'':'hidden'}">${group.items.map(x=>`<label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-white p-3 hover:border-blue-300"><input type="checkbox" name="extra_standard_ids[]" value="${x.id}" data-days="${x.days}" class="military-extra mt-1 h-4 w-4 accent-blue-600"><span><span class="block font-semibold text-slate-700">${x.label||'Phép thêm'}</span><span class="mt-1 block text-xs text-slate-500">${x.days} ngày</span></span></label>`).join('')}</div>`).join('')}`
        : '<div class="p-3 text-sm text-slate-500">Chưa có quy định phép thêm phù hợp.</div>';
      militaryExtraBox.querySelectorAll('[data-extra-tab]').forEach(tab=>tab.addEventListener('click',()=>{
        militaryExtraBox.querySelectorAll('[data-extra-tab]').forEach(item=>item.classList.remove('bg-white','text-blue-700'));
        militaryExtraBox.querySelectorAll('[data-extra-tab]').forEach(item=>item.classList.add('text-slate-500'));
        tab.classList.add('bg-white','text-blue-700');tab.classList.remove('text-slate-500');
        militaryExtraBox.querySelectorAll('[data-extra-panel]').forEach(panelItem=>panelItem.classList.toggle('hidden',panelItem.dataset.extraPanel!==tab.dataset.extraTab));
      }));
    }
   if(militaryExtraBox){militaryExtraBox.classList.toggle('hidden',!extras.length);militaryExtraBox.innerHTML=`<table class="w-full text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">Chọn</th><th class="p-3">Phép thêm theo quy định</th><th class="p-3">Số ngày</th></tr></thead><tbody>${extras.length?extras.map(x=>`<tr class="border-t"><td class="p-3"><input type="checkbox" name="extra_standard_ids[]" value="${x.id}" data-days="${x.days}" class="military-extra h-4 w-4 accent-blue-600"></td><td class="p-3 font-semibold text-slate-700">${x.label||'Phép thêm'}</td><td class="p-3">${x.days} ngày</td></tr>`).join(''):'<tr><td colspan="3" class="p-3 text-slate-500">Chưa có quy định phép thêm phù hợp.</td></tr>'}</tbody></table>`;}
    if(militaryExtraBox){
      const tabGroups=[5,10].map(days=>({days,items:extras.filter(x=>Number(x.days)===days)})).filter(group=>group.items.length);
      militaryExtraBox.innerHTML=tabGroups.length?`<div class="border-b border-slate-200 bg-slate-50 px-3 pt-3"><div class="flex gap-2">${tabGroups.map((group,index)=>`<button type="button" data-extra-tab="${group.days}" class="rounded-t-lg px-4 py-2 text-sm font-extrabold ${index===0?'bg-white text-blue-700':'text-slate-500'}">${group.days} ngày</button>`).join('')}</div></div>${tabGroups.map((group,index)=>`<div data-extra-panel="${group.days}" class="space-y-2 p-3 ${index===0?'':'hidden'}">${group.items.map(x=>`<label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-white p-3"><input type="checkbox" name="extra_standard_ids[]" value="${x.id}" data-days="${x.days}" class="military-extra mt-1 h-4 w-4 accent-blue-600"><span><span class="block font-semibold text-slate-700">${x.label||'Phép thêm'}</span><span class="mt-1 block text-xs text-slate-500">${x.days} ngày</span></span></label>`).join('')}</div>`).join('')}`:'<div class="p-3 text-sm text-slate-500">Chưa có quy định phép thêm phù hợp.</div>';
      militaryExtraBox.querySelectorAll('[data-extra-tab]').forEach(tab=>tab.addEventListener('click',()=>{militaryExtraBox.querySelectorAll('[data-extra-tab]').forEach(item=>{item.classList.remove('bg-white','text-blue-700');item.classList.add('text-slate-500');});tab.classList.add('bg-white','text-blue-700');tab.classList.remove('text-slate-500');militaryExtraBox.querySelectorAll('[data-extra-panel]').forEach(item=>item.classList.toggle('hidden',item.dataset.extraPanel!==tab.dataset.extraTab));}));
    }
    from?.closest('label')?.before(panel);
   panel.dataset.baseDays=String(annualDays);
   panel.dataset.serviceYears=String(serviceYears);
  const render=()=>{const p=findPerson(),s=scope.tomselect?scope.tomselect.getValue():scope.value,annual=s==='PERSONAL'&&type?.value==='ANNUAL',show=s==='PERSONAL'||s==='ADMIN_EXTRA';panel.classList.toggle('hidden',!show);if(!p||!show)return;const y=years(p);document.getElementById('personal-service-years').textContent=`${y} năm thâm niên`;document.getElementById('personal-info-fields').innerHTML=`<label class="block text-xs font-bold text-slate-600">Họ tên<input readonly value="${p.name||''}" class="mt-1 w-full rounded-xl border-slate-200 bg-slate-100 px-3 py-2.5"></label><label class="block text-xs font-bold text-slate-600">Cấp bậc<input readonly value="${p.rank||''}" class="mt-1 w-full rounded-xl border-slate-200 bg-slate-100 px-3 py-2.5"></label><label class="block text-xs font-bold text-slate-600">Chức vụ<input readonly value="${p.position||''}" class="mt-1 w-full rounded-xl border-slate-200 bg-slate-100 px-3 py-2.5"></label><label class="block text-xs font-bold text-slate-600">Cơ quan quản lý<input readonly value="${p.unit||''}" class="mt-1 w-full rounded-xl border-slate-200 bg-slate-100 px-3 py-2.5"></label><label class="block text-xs font-bold text-slate-600">Cơ quan chỉ huy<input readonly value="${p.commander||''}" class="mt-1 w-full rounded-xl border-slate-200 bg-slate-100 px-3 py-2.5"></label>`;const box=document.getElementById('personal-extra-box');box.classList.toggle('hidden',!annual);if(annual)box.innerHTML=`<table class="w-full text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">Chọn</th><th class="p-3">Phép thêm theo quy định</th><th class="p-3">Số ngày</th></tr></thead><tbody>${extras.map(x=>`<tr class="border-t"><td class="p-3"><input type="checkbox" name="extra_standard_ids[]" value="${x.id}" data-days="${x.days}" class="generic-extra h-4 w-4 accent-blue-600"></td><td class="p-3">${x.label||'Phép thêm'}</td><td class="p-3">${x.days} ngày</td></tr>`).join('')}</tbody></table>`;const total=annual?baseDays(p)+Number(travel?.value||0)+Array.from(form.querySelectorAll('.generic-extra:checked')).reduce((a,x)=>a+Number(x.dataset.days||0),0):null;document.getElementById('personal-total-days').textContent=total===null?'Theo thời gian đã chọn':`${total} ngày`;if(to){to.readOnly=false;to.classList.remove('bg-slate-100');if(annual&&from?.value&&to.dataset.manualEdited!=='1'){const d=new Date(from.value+'T00:00:00');d.setDate(d.getDate()+Math.max(0,total-1));const iso=d.toISOString().slice(0,10);window.setDateInputValue?window.setDateInputValue(to,iso,false):to.value=iso;}}if(window.initTomSelects)window.initTomSelects(panel);};
  if(scope.tomselect)scope.tomselect.on('change',render);scope.addEventListener('change',render);personnel.addEventListener('change',render);type?.addEventListener('change',render);from?.addEventListener('change',render);travel?.addEventListener('input',render);form.addEventListener('change',e=>{if(e.target.matches('.generic-extra'))render();});render();
})();
</script>
@endif
@if($isMilitaryAccount)
<script>
(()=>{
  const scope = document.getElementById('proposal-scope');
  if (scope) Array.from(scope.options).forEach(option => { if (option.value !== 'PERSONAL') option.remove(); });
  const ownPersonnelIds = @json($personnel->where('user_id', auth()->id())->pluck('id')->map(fn($id) => (string) $id)->values());
  const personnel = document.getElementById('proposal-personnel');
  if (personnel) Array.from(personnel.options).forEach(option => { if (option.value && !ownPersonnelIds.includes(String(option.value))) option.remove(); });
  const leaveType = document.getElementById('proposal-leave-type');
  if (leaveType) {
    Array.from(leaveType.options).forEach(option => { if (option.value !== 'ANNUAL') option.remove(); });
    const extra = document.createElement('option');
    extra.value = 'EXTRA';
    extra.textContent = 'Phép đặc biệt';
    leaveType.appendChild(extra);
  }
})();
</script>
@endif
@if($isMilitaryAccount)
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form'),scope=document.getElementById('proposal-scope'),from=document.getElementById('proposal-from'),to=document.getElementById('proposal-to'),travel=document.getElementById('proposal-travel'),personnel=document.getElementById('proposal-personnel'),reason=document.getElementById('proposal-reason');
  const person=@json($militaryPersonnel),serviceYears=@json($militaryServiceYears ?? 0),annualDays=@json($militaryAnnualDays ?? 0);
  const extras=@json($militaryExtraData);
  const localities=@json($localityData);
   if(!form||!person)return;
   if(reason)reason.placeholder='Ghi chu (neu co)';
   if(personnel){personnel.value=String(person.id);personnel.required=true;personnel.closest('label')?.classList.add('hidden');}
   const objectLabels={SQ:'Sĩ quan',QNCN:'Quân nhân chuyên nghiệp',CNQP:'Công nhân quốc phòng',VCQP:'Viên chức quốc phòng',HSQBS:'Hạ sĩ quan, binh sĩ',HV:'Học viên'};
   const accountObjectLabel=objectLabels[person.object_type]||person.object_type||'Quân nhân';
   if(scope){scope.innerHTML=`<option value="PERSONAL">${accountObjectLabel}</option>`;scope.value='PERSONAL';scope.closest('label')?.querySelector('span')?.replaceChildren(document.createTextNode('Đối tượng'));scope.dispatchEvent(new Event('change'));}
   document.getElementById('class-scope-panel')?.classList.add('hidden');
   document.getElementById('class-info-panel')?.classList.add('hidden');
   const classField=document.getElementById('class-field');
   if(classField){classField.classList.add('hidden');document.getElementById('proposal-class')?.removeAttribute('required');}
   document.getElementById('short-people')?.classList.add('hidden');
   document.getElementById('proposal-scope')?.closest('label')?.style.setProperty('order','1');
   document.getElementById('proposal-leave-type')?.closest('label')?.style.setProperty('order','2');
   document.getElementById('proposal-replacement')?.closest('label')?.style.setProperty('order','3');
  const defaultLocality=localities.find(x=>String(x.name).trim().toLowerCase()===String(person.permanent_residence||'').trim().toLowerCase());
  const panel=document.createElement('div');panel.id='military-leave-summary';panel.className='md:col-span-3 rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-4';
  panel.innerHTML=`<div class="mb-3 flex items-center justify-between"><div><h3 class="font-extrabold text-slate-900">ThÃ´ng tin phÃ©p nÄƒm cÃ¡ nhÃ¢n</h3><p class="text-xs text-slate-500">ÄÆ¡n phÃ©p nÃ y ch%E1%BB%89 dÃ nh cho quÃ¢n nhÃ¢n Ä‘ang Ä‘Äƒng nh%E1%BA%ADp.</p></div><span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">${serviceYears} nÄƒm thÃ¢m niÃªn</span></div><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"><div><div class="text-xs text-slate-500">QuÃ¢n nhÃ¢n</div><div class="font-bold text-slate-800">${person.name||''}</div></div><div><div class="text-xs text-slate-500">MÃ£ quÃ¢n nhÃ¢n</div><div class="font-semibold text-slate-700">${person.staff_code||'â€”'}</div></div><div><div class="text-xs text-slate-500">Cáº¥p báº­c / chá»©c vá»¥</div><div class="font-semibold text-slate-700">${person.rank||'â€”'} / ${person.position||'â€”'}</div></div><div><div class="text-xs text-slate-500">PhÃ©p theo thÃ¢m niÃªn</div><div class="font-bold text-blue-700">${annualDays} ngÃ y</div></div></div><div class="mt-4 grid gap-3 md:grid-cols-2"><label class="block"><span class="mb-1 block text-sm font-bold text-slate-700">Äá»‹a chá»‰ nghá»‰ phÃ©p</span><select id="military-locality" name="locality_id" class="w-full rounded-xl border-slate-200 px-3 py-2.5"><option value="">Chá»n Ä‘á»‹a chá»‰</option>${localities.map(x=>`<option value="${x.id}" ${defaultLocality&&String(defaultLocality.id)===String(x.id)?'selected':''}>${x.name}</option>`).join('')}</select><span class="mt-1 block text-xs text-slate-500">Máº·c Ä‘á»‹nh theo thÆ°á»ng trÃº: ${person.permanent_residence||'chÆ°a cÃ³'}; cÃ³ thá»ƒ sá»­a.</span></label><div><span class="mb-1 block text-sm font-bold text-slate-700">Tá»•ng ngÃ y phÃ©p</span><div id="military-total-days" class="rounded-xl border border-blue-200 bg-white px-3 py-2.5 text-lg font-extrabold text-blue-700">${annualDays} ngÃ y</div><p class="mt-1 text-xs text-slate-500">PhÃ©p thÃ¢m niÃªn + Ä‘i Ä‘Æ°á»ng + phÃ©p thÃªm</p></div></div><div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white"><table class="w-full text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">Chá»n</th><th class="p-3">PhÃ©p thÃªm theo quy Ä‘á»‹nh</th><th class="p-3">Sá»‘ ngÃ y</th></tr></thead><tbody>${extras.length?extras.map(x=>`<tr class="border-t"><td class="p-3"><input type="checkbox" name="extra_standard_ids[]" value="${x.id}" data-days="${x.days}" class="military-extra h-4 w-4 accent-blue-600"></td><td class="p-3 font-semibold text-slate-700">${x.label||'PhÃ©p thÃªm'}</td><td class="p-3">${x.days} ngÃ y</td></tr>`).join(''):'<tr><td colspan="3" class="p-3 text-slate-500">ChÆ°a cÃ³ quy Ä‘á»‹nh phÃ©p thÃªm phÃ¹ há»£p.</td></tr>'}</tbody></table></div>`;
   from?.closest('label')?.before(panel);
   panel.innerHTML=`<div class="mb-4 flex items-center justify-between gap-3"><div><h3 class="text-lg font-extrabold text-slate-900">Thông tin phép của quân nhân</h3><p class="mt-1 text-sm text-slate-600">Đơn phép này dành cho quân nhân đang đăng nhập.</p></div><span class="rounded-full bg-blue-100 px-4 py-2 text-sm font-extrabold text-blue-700">${serviceYears} năm thâm niên</span></div><div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><div><div class="text-xs font-semibold text-slate-500">Quân nhân</div><div class="font-bold text-slate-800">${person.name||''}</div></div><div><div class="text-xs font-semibold text-slate-500">Mã quân nhân</div><div class="font-semibold text-slate-700">${person.staff_code||'—'}</div></div><div><div class="text-xs font-semibold text-slate-500">Cấp bậc / chức vụ</div><div class="font-semibold text-slate-700">${person.rank||'—'} / ${person.position||'—'}</div></div><div><div class="text-xs font-semibold text-slate-500">Phép theo thâm niên</div><div class="font-extrabold text-blue-700">${annualDays} ngày</div></div></div><div class="mt-5 grid gap-4 md:grid-cols-2"><label class="block"><span class="mb-1.5 block text-sm font-bold text-slate-700">Địa chỉ nghỉ phép</span><select id="military-locality" name="locality_id" class="w-full rounded-xl border-slate-200 px-3 py-2.5"><option value="">Chọn địa chỉ nghỉ phép</option>${localities.map(x=>`<option value="${x.id}" ${defaultLocality&&String(defaultLocality.id)===String(x.id)?'selected':''}>${x.name}</option>`).join('')}</select></label><div><span class="mb-1.5 block text-sm font-bold text-slate-700">Tổng ngày phép</span><div id="military-total-days" class="rounded-xl border border-blue-200 bg-white px-4 py-3 text-xl font-extrabold text-blue-700">${annualDays} ngày</div></div></div><div id="personal-extra-box" class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white"></div>`;
    const militaryExtraBox=panel.querySelector('#personal-extra-box');
   if(militaryExtraBox){
     const tabGroups=[5,10].map(days=>({days,items:extras.filter(x=>Number(x.days)===days)})).filter(group=>group.items.length);
     militaryExtraBox.innerHTML=tabGroups.length?`<div class="border-b border-slate-200 bg-slate-50 px-3 pt-3"><div class="flex gap-2">${tabGroups.map((group,index)=>`<button type="button" data-extra-tab="${group.days}" class="rounded-t-lg px-4 py-2 text-sm font-extrabold ${index===0?'bg-white text-blue-700':'text-slate-500'}">${group.days} ngày</button>`).join('')}</div></div>${tabGroups.map((group,index)=>`<div data-extra-panel="${group.days}" class="space-y-2 p-3 ${index===0?'':'hidden'}">${group.items.map(x=>`<label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-white p-3"><input type="checkbox" name="extra_standard_ids[]" value="${x.id}" data-days="${x.days}" class="military-extra mt-1 h-4 w-4 accent-blue-600"><span><span class="block font-semibold text-slate-700">${x.label||'Phép thêm'}</span><span class="mt-1 block text-xs text-slate-500">${x.days} ngày</span></span></label>`).join('')}</div>`).join('')}`:'<div class="p-3 text-sm text-slate-500">Chưa có quy định phép thêm phù hợp.</div>';
     militaryExtraBox.querySelectorAll('[data-extra-tab]').forEach(tab=>tab.addEventListener('click',()=>{militaryExtraBox.querySelectorAll('[data-extra-tab]').forEach(item=>{item.classList.remove('bg-white','text-blue-700');item.classList.add('text-slate-500');});tab.classList.add('bg-white','text-blue-700');tab.classList.remove('text-slate-500');militaryExtraBox.querySelectorAll('[data-extra-panel]').forEach(item=>item.classList.toggle('hidden',item.dataset.extraPanel!==tab.dataset.extraTab));}));
   }
    panel.querySelector('h3')?.replaceChildren(Object.assign(document.createElement('span'),{innerHTML:'Th&ocirc;ng tin ph&eacute;p n&#x103;m c&aacute; nh&acirc;n'}));
    const panelSubtitle=panel.querySelector('h3')?.nextElementSibling;if(panelSubtitle)panelSubtitle.innerHTML='&#x110;&#x1a1;n ph&eacute;p n&agrave;y d&agrave;nh cho qu&acirc;n nh&acirc;n &#x111;ang &#x111;&#x103;ng nh&#x1ead;p.';
    const panelLabels=Array.from(panel.querySelectorAll('div')).filter(item=>item.className.includes('text-xs')&&item.className.includes('font-semibold'));['Qu&acirc;n nh&acirc;n','M&atilde; qu&acirc;n nh&acirc;n','C&#x1ea5;p b&#x1ead;c / ch&#x1ee9;c v&#x1ee5;','Ph&eacute;p theo th&acirc;m ni&ecirc;n'].forEach((label,index)=>{if(panelLabels[index])panelLabels[index].innerHTML=label;});
    const panelFieldLabels=Array.from(panel.querySelectorAll('span')).filter(item=>item.className.includes('text-sm')&&item.className.includes('font-bold'));if(panelFieldLabels[0])panelFieldLabels[0].innerHTML='&#x110;&#x1ecb;a ch&#x1ec9; ngh&#x1ec9; ph&eacute;p';if(panelFieldLabels[1])panelFieldLabels[1].innerHTML='T&#x1ed5;ng ng&agrave;y ph&eacute;p';
    panel.querySelectorAll('[data-extra-tab]').forEach(tab=>{tab.innerHTML=`${tab.dataset.extraTab} ng&agrave;y`;});
    const commander=(person.commander&&person.commander.name)||person.commander_name||'';
   const manager=person.managing_agency==='CO_QUAN_CAN_BO'?'Cơ quan cán bộ':'Quân lực';
  const info=document.createElement('div');
  info.className='mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5';
   info.innerHTML=`<label class="block text-xs font-bold text-slate-600">Họ tên<input value="${person.name||''}" readonly class="mt-1 w-full rounded-xl border-slate-200 bg-slate-100 px-3 py-2.5 text-sm font-semibold text-slate-800"></label><label class="block text-xs font-bold text-slate-600">Cấp bậc<input value="${person.rank||''}" readonly class="mt-1 w-full rounded-xl border-slate-200 bg-slate-100 px-3 py-2.5 text-sm"></label><label class="block text-xs font-bold text-slate-600">Chức vụ<input value="${person.position||''}" readonly class="mt-1 w-full rounded-xl border-slate-200 bg-slate-100 px-3 py-2.5 text-sm"></label><label class="block text-xs font-bold text-slate-600">Cơ quan quản lý<input value="${manager}" readonly class="mt-1 w-full rounded-xl border-slate-200 bg-slate-100 px-3 py-2.5 text-sm"></label><label class="block text-xs font-bold text-slate-600">Cơ quan chỉ huy<input value="${commander}" readonly class="mt-1 w-full rounded-xl border-slate-200 bg-slate-100 px-3 py-2.5 text-sm"></label>`;
   info.innerHTML=`<label class="block text-xs font-bold text-slate-600">Họ tên<input value="${person.name||''}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label><label class="block text-xs font-bold text-slate-600">Cấp bậc<input value="${person.rank||''}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label><label class="block text-xs font-bold text-slate-600">Chức vụ<input value="${person.position||''}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label><label class="block text-xs font-bold text-slate-600">Đơn vị<input value="${manager}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label><label class="block text-xs font-bold text-slate-600">Cơ quan chỉ huy<input value="${commander}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label>`;
   info.className='mt-4 grid gap-3 sm:grid-cols-2';
   info.innerHTML=`<label class="block text-xs font-bold text-slate-600">Họ tên<input value="${person.name||''}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label><label class="block text-xs font-bold text-slate-600">Ngày nhập ngũ / tuyển dụng<input value="${person.enlistment_date||''}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label><label class="block text-xs font-bold text-slate-600">Cấp bậc<input value="${person.rank||''}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label><label class="block text-xs font-bold text-slate-600">Chức vụ<input value="${person.position||''}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label><label class="block text-xs font-bold text-slate-600">Đơn vị<input value="${manager}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label><label class="block text-xs font-bold text-slate-600">Thâm niên (năm) / Ngày phép cơ bản<input value="${serviceYears} năm — ${annualDays} ngày" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"><span class="mt-1 block text-xs font-normal text-slate-500">Tự tính theo đối tượng + thâm niên tại ngày bắt đầu nghỉ.</span></label>`;
   if(document.getElementById('military-profile-fields'))info.classList.add('hidden');
   panel.querySelector('.mt-4')?.before(info);
   const detailFields=document.createElement('div');
   detailFields.className='mt-4 grid gap-3 md:grid-cols-2';
   detailFields.innerHTML=`<label class="block text-xs font-bold text-slate-600">Ngày nhập ngũ / tuyển dụng<input value="${person.enlistment_date||''}" readonly class="mt-1 w-full rounded-xl border-slate-200 bg-slate-100 px-3 py-2.5 text-sm"></label><label class="block text-xs font-bold text-slate-600">Thâm niên (năm) / Ngày phép cơ bản<input value="${serviceYears} năm — ${annualDays} ngày" readonly class="mt-1 w-full rounded-xl border-slate-200 bg-slate-100 px-3 py-2.5 text-sm"></label><label class="block text-xs font-bold text-slate-600 md:col-span-2">Địa chỉ cụ thể<input name="permanent_residence" value="${person.permanent_residence||''}" placeholder="Số nhà, đường, tổ, thôn..." class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm"></label>`;
   panel.querySelector('.mt-4')?.before(detailFields);
   detailFields.innerHTML=`<label class="block text-xs font-bold text-slate-600 md:col-span-2">Địa chỉ cụ thể<input name="permanent_residence" value="${person.permanent_residence||''}" placeholder="Số nhà, đường, tổ, thôn..." class="mt-1 w-full rounded-lg border-slate-200 bg-white px-3 py-2.5 text-sm"></label>`;
   const dateRow=document.createElement('div');
   dateRow.className='mt-4 grid gap-3 md:grid-cols-2';
   const travelField=travel?.closest('label'),fromField=from?.closest('label'),toField=to?.closest('label'),reasonField=reason?.closest('label');
   const locationAnchor=panel.querySelector('#military-locality')?.closest('label');
   if(locationAnchor){locationAnchor.querySelector('span')?.replaceChildren(document.createTextNode('Tỉnh / Thành phố'));const localitySelect=locationAnchor.querySelector('#military-locality');const wardLabel=document.createElement('label');wardLabel.className='block';wardLabel.innerHTML='<span class="mb-1 block text-sm font-bold text-slate-700">Xã / Phường</span><select id="military-ward" name="locality_id" class="w-full rounded-lg border-slate-200 px-3 py-2.5"><option value="">Chọn xã / phường</option></select>';if(localitySelect){localitySelect.name='locality_parent_id';localitySelect.innerHTML='<option value="">Chọn tỉnh / thành phố</option>'+localities.filter(x=>!x.parent_id).map(x=>`<option value="${x.id}">${x.name}</option>`).join('');}locationAnchor.parentElement?.insertBefore(wardLabel,locationAnchor.nextElementSibling);}
   if(locationAnchor&&militaryExtraBox)locationAnchor.parentElement?.after(militaryExtraBox);
   if(travelField)dateRow.appendChild(travelField);
   if(fromField)dateRow.appendChild(fromField);
   locationAnchor?.before(dateRow);
   if(toField){const endRow=document.createElement('div');endRow.className='mt-3 md:w-1/2';endRow.appendChild(toField);dateRow.after(endRow);}
   if(reasonField){reasonField.classList.add('mt-3');panel.appendChild(reasonField);}
  if(window.initTomSelects) window.initTomSelects(panel);
  if(to){to.readOnly=false;to.disabled=false;to.classList.remove('bg-slate-100','cursor-not-allowed');to.title='Có thể sửa ngày kết thúc nếu cần';}
  const update=()=>{const days=Number(annualDays)+Number(travel?.value||0)+Array.from(form.querySelectorAll('.military-extra:checked')).reduce((s,x)=>s+Number(x.dataset.days||0),0),total=document.getElementById('military-total-days');if(total)total.textContent=`${days} ngÃ y`;if(from?.value&&to&&to.dataset.manualEdited!=='1'){const d=new Date(`${from.value}T00:00:00`);d.setDate(d.getDate()+Math.max(0,days-1));const iso=d.toISOString().slice(0,10);window.setDateInputValue?window.setDateInputValue(to,iso,false):to.value=iso;}};
   from?.addEventListener('change',update);travel?.addEventListener('input',update);form.addEventListener('change',e=>{if(e.target.matches('.military-extra'))update();});
   const syncStats=()=>{const extraDays=Array.from(form.querySelectorAll('.military-extra:checked')).reduce((s,x)=>s+Number(x.dataset.days||0),0),travelDays=Number(travel?.value||0);panel.dataset.extraDays=String(extraDays);panel.dataset.travelDays=String(travelDays);panel.dataset.totalDays=String(Number(annualDays)+travelDays+extraDays);};
    form.addEventListener('change',syncStats);travel?.addEventListener('input',syncStats);
    const syncEndDate=()=>{const days=Number(annualDays)+Number(travel?.value||0)+Array.from(form.querySelectorAll('.military-extra:checked')).reduce((sum,item)=>sum+Number(item.dataset.days||0),0);if(from?.value&&to&&to.dataset.manualEdited!=='1'){const date=new Date(`${from.value}T00:00:00`);date.setDate(date.getDate()+Math.max(0,days-1));const iso=date.toISOString().slice(0,10);window.setDateInputValue?window.setDateInputValue(to,iso,false):to.value=iso;}};
    from?.addEventListener('change',syncEndDate);travel?.addEventListener('input',syncEndDate);form.addEventListener('change',event=>{if(event.target.matches('.military-extra'))syncEndDate();});
    document.getElementById('military-profile-fields')?.classList.add('hidden');
    const finalExtraBox=panel.querySelector('#personal-extra-box'),finalTravel=travel,finalFrom=from,finalTo=to,finalLocality=panel.querySelector('#military-locality'),finalWard=panel.querySelector('#military-ward');
    panel.innerHTML=`<div class="mb-4 flex items-start justify-between gap-4"><div><h3 class="text-lg font-extrabold leading-7 text-slate-900">Th&ocirc;ng tin ph&eacute;p n&#x103;m c&aacute; nh&acirc;n</h3><p class="mt-1 text-sm leading-6 text-slate-600">&#x110;&#x1a1;n ph&eacute;p n&agrave;y d&agrave;nh cho qu&acirc;n nh&acirc;n<br>&#x111;ang &#x111;&#x103;ng nh&#x1ead;p.</p></div><span class="rounded-full bg-blue-100 px-4 py-3 text-sm font-extrabold leading-5 text-blue-700">${serviceYears} n&#x103;m<br>th&acirc;m ni&ecirc;n</span></div><div class="grid gap-x-8 gap-y-5 sm:grid-cols-2"><div><div class="text-xs font-semibold text-slate-500">Qu&acirc;n nh&acirc;n</div><div class="font-bold text-slate-900">${person.name||''}</div></div><div><div class="text-xs font-semibold text-slate-500">M&atilde; qu&acirc;n nh&acirc;n</div><div class="font-semibold text-slate-700">${person.staff_code||'—'}</div></div><div><div class="text-xs font-semibold text-slate-500">C&#x1ea5;p b&#x1ead;c / ch&#x1ee9;c v&#x1ee5;</div><div class="font-semibold text-slate-700">${person.rank||'—'} / ${person.position||'—'}</div></div><div><div class="text-xs font-semibold text-slate-500">Ph&eacute;p theo th&acirc;m ni&ecirc;n</div><div class="font-extrabold text-blue-700">${annualDays} ng&agrave;y</div></div></div><div class="mt-6 grid grid-cols-3 gap-3"><label id="final-travel" class="block"><span class="mb-1.5 block text-sm font-bold text-slate-700">Ng&agrave;y &#x111;i &#x111;&#x432;&#7901;ng</span></label><label id="final-from" class="block"><span class="mb-1.5 block text-sm font-bold text-slate-700">Ng&agrave;y b&#x7855;t &#x111;&#x7847;u <b class="text-rose-500">*</b></span></label><label id="final-to" class="block"><span class="mb-1.5 block text-sm font-bold text-slate-700">Ng&agrave;y k&#x1ebft th&uacute;c <b class="text-rose-500">*</b></span></label></div><div class="mt-5 grid gap-4 sm:grid-cols-2"><label id="final-locality" class="block"><span class="mb-1.5 block text-sm font-bold text-slate-700">T&#x1ec9;nh / Th&agrave;nh ph&#x1ed1;</span></label><label id="final-ward" class="block"><span class="mb-1.5 block text-sm font-bold text-slate-700">X&atilde; / Ph&#x432;&#7901;ng</span></label></div><div class="mt-6"><div class="text-sm font-bold text-slate-700">T&#x1ed5;ng ng&agrave;y ph&eacute;p</div><div id="military-total-days" class="mt-2 inline-block rounded-xl border border-blue-200 bg-white px-4 py-3 text-xl font-extrabold text-blue-700">${annualDays} ng&agrave;y</div></div><div id="final-extra" class="mt-5"></div>`;
     document.getElementById('final-travel')?.append(finalTravel);document.getElementById('final-from')?.append(finalFrom);document.getElementById('final-to')?.append(finalTo);document.getElementById('final-locality')?.append(finalLocality);document.getElementById('final-ward')?.append(finalWard);if(finalLocality&&finalWard){const fillWards=()=>{const parentId=Number(finalLocality.value||0);finalWard.innerHTML='<option value="">Chọn xã / phường</option>'+localities.filter(x=>Number(x.parent_id)===parentId).map(x=>`<option value="${x.id}">${x.name}</option>`).join('');};finalLocality.name='locality_parent_id';finalWard.name='locality_id';finalLocality.addEventListener('change',fillWards);fillWards();const fallback=document.getElementById('proposal-locality');if(fallback){fallback.disabled=true;fallback.closest('label')?.classList.add('hidden');}}document.getElementById('final-extra')?.before(finalExtraBox);
     const extraSlot=panel.querySelector('#final-extra');if(extraSlot){finalExtraBox.remove();const extraTitle=document.createElement('div');extraTitle.className='mb-2 mt-5 text-sm font-extrabold text-slate-800';extraTitle.innerHTML='Ph&eacute;p th&ecirc;m';extraSlot.replaceWith(extraTitle,finalExtraBox);}
     const meta=document.createElement('div');meta.className='mb-5 grid gap-3 md:grid-cols-3';const scopeWrap=scope?.closest('label'),typeWrap=document.getElementById('proposal-leave-type')?.closest('label'),replacementWrap=document.getElementById('proposal-replacement')?.closest('label');[scopeWrap,typeWrap,replacementWrap].forEach(item=>item?.classList.remove('hidden'));if(scopeWrap)meta.append(scopeWrap);if(typeWrap)meta.append(typeWrap);if(replacementWrap)meta.append(replacementWrap);panel.insertBefore(meta,panel.firstChild);
     const infoGrid=Array.from(panel.children).find(item=>item.className.includes('sm:grid-cols-2')&&item.children.length===4);if(infoGrid&&infoGrid.children[2]){const rankCell=infoGrid.children[2],rank=person.rank||'—',position=person.position||'—';rankCell.innerHTML=\`<div class="text-xs font-semibold text-slate-500">C&#x1ea5;p b&#x1ead;c</div><div class="font-semibold text-slate-700">\${rank}</div>\`;const positionCell=document.createElement('div');positionCell.innerHTML=\`<div class="text-xs font-semibold text-slate-500">Ch&#x1ee9;c v&#x1ee5;</div><div class="font-semibold text-slate-700">\${position}</div>\`;rankCell.after(positionCell);}
     const baseLabel=Array.from(panel.querySelectorAll('div')).find(item=>item.textContent.includes('Ph')&&item.textContent.includes('th'));if(baseLabel)baseLabel.innerHTML='<div class="text-xs font-semibold text-slate-500">Ph&eacute;p c&#x1a1; b&#x1ea3;n</div><div class="font-extrabold text-blue-700">'+annualDays+' ng&agrave;y</div>';
     const totalBox=panel.querySelector('#military-total-days');if(totalBox){totalBox.classList.remove('text-blue-700');totalBox.classList.add('text-slate-900','whitespace-nowrap');}
    syncStats();update();syncEndDate();
})();
</script>
@endif
<script>
(()=>{
  const scope = document.getElementById('proposal-scope');
  const form = document.getElementById('leave-proposal-form');
  if (!scope || !form) return;
  const note = Array.from(form.querySelectorAll(':scope > div')).find(el => el.textContent.includes('Phép lớp tạo'));
  if (!note) return;
  const syncNote = (value) => {
    const personal = value === 'PERSONAL' || value === 'ADMIN_EXTRA';
    note.classList.toggle('hidden', personal);
  };
  if (scope.tomselect) scope.tomselect.on('change', syncNote);
  scope.addEventListener('change', () => syncNote(scope.value));
  syncNote(scope.tomselect ? scope.tomselect.getValue() : scope.value);
})();
</script>
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form'),summary=document.getElementById('leave-summary-text');
  if(!form||!summary)return;
  const sync=()=>{
    const from=document.getElementById('proposal-from')?.value,to=document.getElementById('proposal-to')?.value;
    const range=from&&to?Math.max(0,Math.round((new Date(to)-new Date(from))/86400000)+1):0;
    const travel=Number(document.getElementById('proposal-travel')?.value||0);
    const extra=Array.from(form.querySelectorAll('.military-extra:checked,.generic-extra:checked')).reduce((n,x)=>n+Number(x.dataset.days||0),0);
    const service=document.getElementById('personal-service-years')?.textContent||`${window.leaveServiceYears||0} năm`;
    const base=document.getElementById('military-total-days')?.textContent||document.getElementById('personal-total-days')?.textContent||`${range} ngày`;
    summary.textContent=`Thâm niên: ${service}   Phép cơ bản: ${base}   Đi đường: ${travel} ngày   Nghỉ thêm: ${extra} ngày   Tổng: ${range+travel+extra} ngày`;
  };
  form.addEventListener('input',sync);form.addEventListener('change',sync);sync();
 })();
</script>
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form'),summary=document.getElementById('leave-summary-text');
  if(!form||!summary)return;
  const syncReadableSummary=()=>{
    const panel=document.getElementById('military-leave-summary');
    if(!panel)return;
    const base=Number(panel.dataset.baseDays||0),service=Number(panel.dataset.serviceYears||0),travel=Number(panel.dataset.travelDays||0),extra=Number(panel.dataset.extraDays||0),total=Number(panel.dataset.totalDays||base+travel+extra);
    summary.innerHTML=`<div class="flex min-w-max items-center gap-8 text-sm font-bold"><div class="whitespace-nowrap"><span class="mr-2 text-xs text-slate-500">Thâm niên</span><span>${service} năm</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs text-slate-500">Phép cơ bản</span><span>${base} ngày</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs text-slate-500">Đi đường</span><span>${travel} ngày</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs text-slate-500">Nghỉ thêm</span><span>${extra} ngày</span></div><div class="whitespace-nowrap rounded-lg bg-blue-100 px-3 py-2 text-blue-800"><span class="mr-2 text-xs">Tổng</span><span>${total} ngày</span></div></div>`;
  };
   form.addEventListener('input',syncReadableSummary);form.addEventListener('change',syncReadableSummary);syncReadableSummary();
 })();
</script>
<script>
(()=>{const form=document.getElementById('leave-proposal-form'),summary=document.getElementById('leave-summary-text');if(!form||!summary||!@json($isMilitaryAccount ?? false))return;const base=Number(@json($militaryAnnualDays ?? 0)),service=Number(@json($militaryServiceYears ?? 0)),note=document.getElementById('proposal-reason'),serverBox=document.getElementById('server-military-extra'),dynamicBox=document.getElementById('military-extra-standards');
  if(serverBox&&note?.closest('label'))note.closest('label').after(serverBox);
  if(dynamicBox&&dynamicBox!==serverBox)dynamicBox.remove();
  serverBox?.querySelectorAll('[data-tab-days]').forEach(tab=>tab.addEventListener('click',event=>{event.preventDefault();event.stopPropagation();serverBox.querySelectorAll('[data-tab-days]').forEach(item=>{item.classList.remove('bg-white','text-blue-700');item.classList.add('text-slate-500');});tab.classList.add('bg-white','text-blue-700');tab.classList.remove('text-slate-500');serverBox.querySelectorAll('[data-panel-days]').forEach(panel=>panel.classList.toggle('hidden',panel.dataset.panelDays!==tab.dataset.tabDays));}));
  serverBox?.querySelectorAll('.server-extra-check').forEach(check=>check.addEventListener('change',()=>{if(check.checked)serverBox.querySelectorAll('.server-extra-check').forEach(other=>{if(other!==check)other.checked=false;});render();}));
  const render=()=>{const travel=Number(document.getElementById('proposal-travel')?.value||0),extra=Array.from(form.querySelectorAll('.server-extra-check:checked')).reduce((sum,item)=>sum+Number(item.dataset.days||0),0),total=base+travel+extra;summary.innerHTML='<div class="flex min-w-max items-center gap-8 text-sm font-bold text-slate-900"><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Th&acirc;m ni&ecirc;n</span><span>'+service+' n&#x103;m</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Ph&eacute;p c&#x1a1; b&#x1ea3;n</span><span>'+base+' ng&agrave;y</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">&#x110;i &#x111;&#x1b0;&#x7901;ng</span><span>'+travel+' ng&agrave;y</span></div><div class="whitespace-nowrap"><span class="mr-2 text-xs font-semibold text-slate-500">Ngh&#x1ec9; th&ecirc;m</span><span>'+extra+' ng&agrave;y</span></div><div class="whitespace-nowrap rounded-lg bg-blue-50 px-3 py-2 text-slate-900"><span class="mr-2 text-xs font-semibold text-slate-500">T&#x1ed5;ng</span><span>'+total+' ng&agrave;y</span></div></div>';const from=document.getElementById('proposal-from'),to=document.getElementById('proposal-to');if(to){to.readOnly=false;to.disabled=false;to.classList.remove('bg-slate-100','cursor-not-allowed');if(from?.value&&to.dataset.manualEdited!=='1'){const date=new Date(from.value+'T00:00:00');date.setDate(date.getDate()+Math.max(0,total-1));const iso=date.toISOString().slice(0,10);window.setDateInputValue?window.setDateInputValue(to,iso,false):to.value=iso;}}};form.addEventListener('input',render);form.addEventListener('change',render);render();})();
</script>
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form');
  const to=document.getElementById('proposal-to');
  if(!form||!to)return;
  let manualValue='';
  let manualEdited=false;
  const hidden=document.getElementById('proposal-to-hidden');
  const syncEditableDatePicker=()=>{
    window.initDateInputs?.(form);
    form.querySelectorAll('input[type="date"],#proposal-from,#proposal-to,#class-from,#class-to').forEach(input=>{
      if(input.closest('#class-students'))return;
      input.disabled=false;
      input.readOnly=false;
      input.removeAttribute('disabled');
      input.removeAttribute('readonly');
      if(input._flatpickr?.altInput){
        input._flatpickr.altInput.disabled=false;
        input._flatpickr.altInput.readOnly=false;
        input._flatpickr.altInput.removeAttribute('disabled');
        input._flatpickr.altInput.removeAttribute('readonly');
      }
    });
  };
  const unlock=()=>{
    syncEditableDatePicker();
    to.name='to_date';
    to.disabled=false;
    to.readOnly=false;
    to.removeAttribute('disabled');
    to.removeAttribute('readonly');
    to.removeAttribute('aria-readonly');
    to.style.pointerEvents='';
    to.style.userSelect='';
    to.tabIndex=0;
    to.classList.remove('bg-slate-100','cursor-not-allowed');
    to.title='Có thể sửa ngày kết thúc nếu cần';
    if(hidden){hidden.name='';hidden.disabled=true;hidden.value=to.value||'';}
    if(manualEdited&&manualValue)(window.setDateInputValue?window.setDateInputValue(to,manualValue,false):to.value=manualValue);
  };
  to.addEventListener('input',()=>{manualEdited=true;manualValue=to.value;to.dataset.manualEdited='1';unlock();});
  to.addEventListener('change',()=>{manualEdited=true;manualValue=to.value;to.dataset.manualEdited='1';unlock();});
  form.addEventListener('input',event=>{if(event.target!==to)setTimeout(unlock,0);});
  form.addEventListener('change',event=>{if(event.target!==to)setTimeout(unlock,0);});
  form.addEventListener('submit',unlock);
  unlock();
  [50,250,750].forEach(delay=>setTimeout(unlock,delay));
  form.addEventListener('change',event=>{if(event.target.matches('input[type="date"], .flatpickr-input,#proposal-from,#proposal-to,#class-from,#class-to'))setTimeout(syncEditableDatePicker,0);});
})();
</script>
<script>
(()=>{
  const form=document.getElementById('leave-proposal-form');
  const scope=document.getElementById('proposal-scope');
  if(!form||!scope)return;
  const currentScope=()=>{
    const classScope=document.getElementById('class-scope-select');
    const classPanel=document.getElementById('class-scope-panel');
    if(classScope&&classPanel&&!classPanel.classList.contains('hidden'))return classScope.value;
    return scope.tomselect?scope.tomselect.getValue():scope.value;
  };
  const setHidden=(el,hidden)=>{
    if(!el)return;
    el.classList.toggle('hidden',hidden);
    el.style.display=hidden?'none':'';
  };
  const syncHsqbsDateFields=()=>{
    const scopeValue=currentScope();
    const tableScope=['CLASS','SHORT_LEAVE','HSQBS_ANNUAL','HSQBS_SPECIAL'].includes(scopeValue);
    const hsqbs=['HSQBS_ANNUAL','HSQBS_SPECIAL'].includes(scopeValue);
    const proposalTo=document.getElementById('proposal-to');
    const proposalFrom=document.getElementById('proposal-from');
    const proposalTravel=document.getElementById('proposal-travel');
    const proposalLocality=document.getElementById('proposal-locality');
    [
      document.getElementById('class-travel-field'),
      document.getElementById('class-from-field'),
      document.getElementById('class-to-field'),
      document.getElementById('proposal-locality-field'),
      document.getElementById('final-travel'),
      document.getElementById('final-from'),
      document.getElementById('final-to'),
      proposalFrom?.closest('label'),
      proposalTo?.closest('label'),
      proposalTravel?.closest('label'),
      proposalLocality?.closest('label')
    ].forEach(el=>setHidden(el,tableScope));
    Array.from(form.querySelectorAll('label[data-hidden-by-hsqbs="1"]')).forEach(label=>{
      if(!tableScope){setHidden(label,false);label.removeAttribute('data-hidden-by-hsqbs');}
    });
    if(tableScope){
      Array.from(form.querySelectorAll('label')).forEach(label=>{
        if(label.closest('#class-students'))return;
        const text=label.textContent.replace(/\s+/g,' ').trim();
        if(text.includes('Ngày kết thúc')||text.includes('Ngày bắt đầu')||text.includes('Ngày đi đường')||text.includes('Nơi nghỉ')){
          label.setAttribute('data-hidden-by-hsqbs','1');
          setHidden(label,true);
        }
      });
    }
    if(hsqbs){
      if(proposalTravel)proposalTravel.value=0;
    }
    if(proposalFrom)proposalFrom.required=!tableScope;
    if(proposalTo)proposalTo.required=!tableScope;
    document.querySelectorAll('#class-students tr').forEach(row=>{
      const checked=tableScope&&!!row.querySelector('.selected-person')?.checked;
      row.querySelectorAll('.row-from,.flatpickr-input').forEach(input=>input.removeAttribute('required'));
      row.querySelectorAll('.row-from,.row-to,select[name^="class_leave_locations"]').forEach(input=>input.disabled=!checked);
    });
  };
  scope.addEventListener('change',()=>[0,50,250,750].forEach(delay=>setTimeout(syncHsqbsDateFields,delay)));
  scope.tomselect?.on('change',()=>[0,50,250,750].forEach(delay=>setTimeout(syncHsqbsDateFields,delay)));
  form.addEventListener('input',event=>{if(event.target.closest('#class-students')||event.target===scope)setTimeout(syncHsqbsDateFields,0);});
  form.addEventListener('change',()=>setTimeout(syncHsqbsDateFields,0));
  [0,50,250,750,1200].forEach(delay=>setTimeout(syncHsqbsDateFields,delay));
})();
</script>
@endif
