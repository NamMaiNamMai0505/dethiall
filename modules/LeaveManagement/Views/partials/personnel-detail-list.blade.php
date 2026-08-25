@php
    $approverUsers = ($users ?? collect())->filter(fn ($candidate) => \App\Support\PermissionCheck::can($candidate, 'leave-management.approve') || \App\Support\PermissionCheck::can($candidate, 'leave-management.approvals.approve'));
    $commanderRecords = \Modules\LeaveManagement\Models\LeavePersonnel::withoutGlobalScopes()->with('user')->where('active', true)->get()->filter(fn ($person) => \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $person->position)), 'chi huy'))->map(function ($person) {
        $account = $person->user?->status === 1 ? $person->user : \App\Models\User::where('status', 1)->where('name', $person->name)->first();
        return ['id' => $account?->id, 'name' => $account?->name ?: $person->name, 'code' => $account?->code ?: $person->staff_code, 'unit_id' => (int) $person->unit_id];
    })->filter(fn ($record) => $record['id'])->unique('id')->values();
@endphp
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 bg-gradient-to-r from-blue-50 to-white px-5 py-4">
        <h2 class="text-lg font-extrabold text-slate-900">Danh sách quân nhân</h2>
        <p class="mt-1 text-sm font-medium text-slate-500">Thông tin hồ sơ được tách riêng theo từng trường quản lý.</p>
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
        <label class="min-w-[260px] flex-1 text-xs font-bold text-slate-600">Tìm quân nhân
            <input id="personnel-filter-text" type="search" placeholder="Họ tên, mã quân nhân, đơn vị..." class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal">
        </label>
        <label class="min-w-[200px] text-xs font-bold text-slate-600">Cơ quan tiếp nhận phép
            <select id="personnel-filter-agency" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal">
                <option value="">Tất cả cơ quan</option><option value="QUAN_LUC">Quân lực</option><option value="CO_QUAN_CAN_BO">Cơ quan cán bộ</option>
            </select>
        </label>
        <button type="button" id="personnel-filter-reset" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">Xóa lọc</button>
        <span id="personnel-filter-count" class="pb-2 text-xs font-semibold text-slate-500"></span>
    </div>
    @php
        $personnelObjectGroups = $items->groupBy(fn ($person) => trim((string) ($person->object_type ?: '__EMPTY__')));
    @endphp
    <div class="mb-4 flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-white p-2" role="tablist" aria-label="Lọc quân nhân theo đối tượng">
        <button type="button" class="personnel-object-tab rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white" data-object-tab="__ALL__">Tất cả ({{ $items->count() }})</button>
        @foreach($personnelObjectGroups->sortKeys() as $objectCode => $objectPeople)
            @php
                $object = ($objects ?? collect())->firstWhere('code', $objectCode);
            @endphp
            <button type="button" class="personnel-object-tab rounded-lg px-4 py-2 text-sm font-bold text-slate-600 hover:bg-blue-50" data-object-tab="{{ $objectCode }}">{{ $object?->name ?: ($objectCode === '__EMPTY__' ? 'Chưa phân loại' : $objectCode) }} ({{ $objectPeople->count() }})</button>
        @endforeach
    </div>
    <div class="personnel-table-scroll overflow-x-hidden">
        <table class="personnel-directory-table w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-extrabold text-slate-700">
                <tr>
                    <th class="whitespace-nowrap px-4 py-3">STT</th>
                    <th class="whitespace-nowrap px-4 py-3">Họ tên</th>
                    <th class="whitespace-nowrap px-4 py-3">Mã quân nhân</th>
                    <th class="whitespace-nowrap px-4 py-3">Đối tượng</th>
                    <th class="whitespace-nowrap px-4 py-3">Cấp bậc</th>
                    <th class="whitespace-nowrap px-4 py-3">Chức vụ</th>
                    <th class="whitespace-nowrap px-4 py-3">Ngày nhập ngũ</th>
                    <th class="whitespace-nowrap px-4 py-3">Đơn vị</th>
                    <th class="whitespace-nowrap px-4 py-3">Quê quán</th>
                    <th class="whitespace-nowrap px-4 py-3">Thường trú</th>
                    <th class="whitespace-nowrap px-4 py-3">Chỉ huy đơn vị</th>
                    <th class="whitespace-nowrap px-4 py-3">Gmail</th>
                    <th class="min-w-[620px] whitespace-nowrap px-4 py-3">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $i => $person)
                    @php
                        $isTopLeadership = \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $person->position)), ['hieu truong', 'pho hieu truong']);
                        $isDepartmentCommander = \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $person->position)), 'chi huy') && \Modules\LeaveManagement\Support\LeaveAccess::isDepartmentUnitName($person->unitRelation?->name ?? $person->unit);
                        $unitCommanders = $commanderRecords->where('unit_id', (int) $person->unit_id);
                        $positionKey = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $person->position));
                        $isAgencyPosition = \Illuminate\Support\Str::contains($positionKey, ['quan luc', 'co quan can bo']);
                        $isNoCommanderRequired = $isTopLeadership || $isDepartmentCommander || $isAgencyPosition;
                        $defaultCommanderId = !$isTopLeadership && !$isDepartmentCommander && $unitCommanders->contains('id', (int) $person->commander_user_id)
                            ? (int) $person->commander_user_id
                            : (!$isTopLeadership && !$isDepartmentCommander ? data_get($unitCommanders->first(), 'id') : null);
                        if ($isTopLeadership) $defaultCommanderId = null;
                    @endphp
                    <tr class="personnel-row align-top hover:bg-blue-50/40" data-object="{{ trim((string) ($person->object_type ?: '__EMPTY__')) }}" data-search="{{ mb_strtolower(($person->name ?? '').' '.($person->staff_code ?? '').' '.($person->unitRelation?->name ?? $person->unit ?? ''), 'UTF-8') }}" data-agency="{{ \Modules\LeaveManagement\Support\LeaveAccess::agencyForObject($person->object_type) }}">
                        <td class="whitespace-nowrap px-4 py-3 font-bold text-blue-700">{{ $i + 1 }}</td>
                        <td class="whitespace-nowrap px-4 py-3"><div class="font-bold text-slate-900">{{ $person->name }}</div><div class="text-xs text-slate-500">{{ $person->staff_code ?: 'Chưa có mã' }}</div></td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $person->staff_code ?: '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $person->object_type ?: '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $person->rank ?: '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $person->position ?: '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $person->enlistment_date?->format('d/m/Y') ?: '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $person->unitRelation?->name ?? $person->unit ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $person->hometown ?: '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $person->permanent_residence ?: '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ data_get($unitCommanders->firstWhere('id', $defaultCommanderId), 'name') ?? $person->commander_name ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $person->gmail ?: $person->email ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <form method="POST" action="{{ route('leave-management.personnel.delete', $person) }}" onsubmit="return confirm('Xóa hồ sơ quân nhân này?');">@csrf @method('DELETE')<button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100">Xóa</button></form>
                                <button type="button" class="personnel-edit-toggle rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100" aria-expanded="false">Sửa</button>
                            </div>
                        </td>
                    </tr>
                    <tr class="personnel-edit-row hidden bg-slate-50/70">
                        <td colspan="13" class="px-4 py-4">
                            <form method="POST" action="{{ route('leave-management.personnel.update', $person) }}" class="grid w-full gap-3 rounded-xl border border-blue-100 bg-blue-50/40 p-4 md:grid-cols-2">
                                    @csrf
                                    @method('PATCH')
                                    <label class="block text-xs font-bold text-slate-600">Mã quân nhân<input name="staff_code" value="{{ $person->staff_code }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                                    <label class="block text-xs font-bold text-slate-600">Họ tên<input name="name" value="{{ $person->name }}" required class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                                    <label class="block text-xs font-bold text-slate-600">Đối tượng<input name="object_type" value="{{ $person->object_type }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                                    <label class="block text-xs font-bold text-slate-600">Cấp bậc<input name="rank" value="{{ $person->rank }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                                    <label class="block text-xs font-bold text-slate-600">Chức vụ<input name="position" value="{{ $person->position }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                                    <label class="block text-xs font-bold text-slate-600">Ngày nhập ngũ<input name="enlistment_date" type="date" value="{{ $person->enlistment_date?->format('Y-m-d') }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                                    <label class="block text-xs font-bold text-slate-600">Đơn vị<input name="unit" value="{{ $person->unitRelation?->name ?? $person->unit }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                                    <label class="block text-xs font-bold text-slate-600">Quê quán<input name="hometown" value="{{ $person->hometown }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                                    <label class="block text-xs font-bold text-slate-600">Thường trú<input name="permanent_residence" value="{{ $person->permanent_residence }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                                     <label class="block text-xs font-bold text-slate-600">Cơ quan đang chỉ huy<input name="commander_name" value="{{ $person->commander_name ?: ($person->unitRelation?->name ?? $person->unit) }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                                     <label class="block text-xs font-bold text-slate-600">Tài khoản chỉ huy nhận đề xuất
                                         @if($isNoCommanderRequired)
                                             <input type="hidden" name="commander_user_id" value="">
                                             <select name="commander_user_display" disabled class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm"><option value="">Không cần tài khoản chỉ huy</option></select>
                                         @else
                                             <select name="commander_user_id" required class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><option value="">Chọn tài khoản chỉ huy</option>@foreach($unitCommanders as $commander)<option value="{{ $commander['id'] }}" @selected((int) $defaultCommanderId === (int) $commander['id'])>{{ $commander['code'] }} — {{ $commander['name'] }}</option>@endforeach</select>
                                         @endif
                                         <span class="mt-1 block text-xs font-normal text-slate-500">Có thể thay đổi trong danh sách chỉ huy của đúng đơn vị này.</span>
                                     </label>
                                    <label class="block text-xs font-bold text-slate-600">Gmail<input name="gmail" type="email" value="{{ $person->gmail ?: $person->email }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                                    <div class="flex justify-end md:col-span-2"><button class="rounded-lg bg-blue-700 px-5 py-2.5 font-bold text-white hover:bg-blue-800">Lưu thay đổi</button></div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="13" class="px-5 py-10 text-center text-slate-500">Chưa có quân nhân.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
<style>
    .personnel-directory-table { width: 100% !important; min-width: 0 !important; table-layout: fixed; }
    .personnel-directory-table th:first-child,
    .personnel-directory-table td:first-child { min-width: 42px; width: 4%; }
    .personnel-directory-table th:nth-child(2),
    .personnel-directory-table td:nth-child(2) { width: 11%; }
    .personnel-directory-table thead th,
    .personnel-directory-table tbody td { white-space: normal !important; line-height: 1.3; vertical-align: top; }
    .personnel-directory-table th,
    .personnel-directory-table td { overflow-wrap: anywhere; word-break: break-word; padding-left: .55rem; padding-right: .55rem; }
    .personnel-directory-table th:last-child,
    .personnel-directory-table td:last-child { width: 9% !important; min-width: 0 !important; max-width: none !important; }
    .personnel-directory-table .personnel-edit-row > td { padding: .75rem !important; }
    .personnel-directory-table .personnel-edit-row form { min-width: 0 !important; max-width: none !important; box-sizing: border-box; }
</style>
<script>
(() => {
    const text = document.getElementById('personnel-filter-text');
    const agency = document.getElementById('personnel-filter-agency');
    const reset = document.getElementById('personnel-filter-reset');
    const count = document.getElementById('personnel-filter-count');
    const tabs = [...document.querySelectorAll('.personnel-object-tab')];
    const rows = [...document.querySelectorAll('.personnel-row')];
    if (!text || !agency) return;
    let selectedObject = '__ALL__';
    const apply = () => {
        const needle = text.value.trim().toLocaleLowerCase('vi');
        const selected = agency.value;
        let visible = 0;
        rows.forEach(row => {
            const match = (selectedObject === '__ALL__' || row.dataset.object === selectedObject) && (!needle || (row.dataset.search || '').toLocaleLowerCase('vi').includes(needle)) && (!selected || row.dataset.agency === selected);
            row.classList.toggle('hidden', !match);
            row.nextElementSibling?.classList.toggle('hidden', !match || !row.nextElementSibling.classList.contains('personnel-edit-row') || row.nextElementSibling.dataset.open !== 'true');
            if (match) visible++;
        });
        count.textContent = `Hiển thị ${visible}/${rows.length} quân nhân`;
    };
    tabs.forEach(tab => tab.addEventListener('click', () => {
        selectedObject = tab.dataset.objectTab || '__ALL__';
        tabs.forEach(item => { const active = item === tab; item.classList.toggle('bg-blue-700', active); item.classList.toggle('text-white', active); item.classList.toggle('text-slate-600', !active); });
        apply();
    }));
    document.querySelectorAll('.personnel-edit-toggle').forEach(button => button.addEventListener('click', () => {
        const row = button.closest('.personnel-row');
        const editRow = row?.nextElementSibling;
        if (!row || !editRow?.classList.contains('personnel-edit-row')) return;
        const opening = editRow.dataset.open !== 'true';
        document.querySelectorAll('.personnel-edit-row').forEach(item => {
            item.dataset.open = 'false';
            item.classList.add('hidden');
            item.previousElementSibling?.querySelector('.personnel-edit-toggle')?.setAttribute('aria-expanded', 'false');
        });
        if (opening) {
            editRow.dataset.open = 'true';
            editRow.classList.remove('hidden');
            button.setAttribute('aria-expanded', 'true');
        }
    }));
    text.addEventListener('input', apply); agency.addEventListener('change', apply);
    reset.addEventListener('click', () => { text.value = ''; agency.value = ''; apply(); });
    apply();
})();
</script>
