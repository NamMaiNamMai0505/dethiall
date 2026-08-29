<?php

namespace Modules\LeaveManagement\Controllers;

use App\Http\Controllers\ModuleBaseController;
use App\Support\PermissionCheck;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\LeaveManagement\Models\{LeaveAlert, LeaveAuditLog, LeaveClass, LeaveLocality, LeavePersonnel, LeaveRegulation, LeaveRequest};
use Modules\LeaveManagement\Support\LeaveAccess;

class LeaveProposalController extends ModuleBaseController
{
    public function store(Request $request)
    {
        $travelInput = $request->input('travel_days');
        if (is_string($travelInput) && preg_match('/^\d+\.0+$/', trim($travelInput))) {
            $request->merge(['travel_days' => (int) $travelInput]);
        }
        $data = $request->validate([
            'request_scope' => 'required|in:PERSONAL,INDIVIDUAL,CLASS,SHORT_LEAVE',
            'personnel_id' => 'nullable|exists:leave_personnel,id',
            'personnel_ids' => 'nullable|array',
            'personnel_ids.*' => 'integer|exists:leave_personnel,id',
            'class_id' => 'nullable|exists:leave_classes,id',
            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'leave_type' => 'required|string|max:80',
            'travel_days' => 'nullable|integer|min:0',
            'extra_standard_ids' => 'nullable|array',
            'extra_standard_ids.*' => 'integer|exists:leave_regulations,id',
            'reason' => 'nullable|string|max:2000',
            'locality_id' => 'nullable|exists:leave_localities,id',
            'replacement_personnel_id' => 'nullable|exists:leave_personnel,id',
        ]);

          $scope = $data['request_scope'];
          $canProposeForUnit = $request->user()->isSuperAdmin()
              || $request->user()->can('leave-management.approvals.approve')
              || $request->user()->can('leave-management.approve');
           $linkedMilitaryPersonnel = LeaveAccess::personnelForUser($request->user());
           $isCommanderPersonnel = $linkedMilitaryPersonnel && LeaveAccess::isCommanderPersonnel($linkedMilitaryPersonnel->position);
           if ($isCommanderPersonnel) {
               abort(403, 'Tài khoản có chức vụ chỉ huy không được tự đề xuất phép.');
           }
           $canProposeForUnit = $request->user()->isSuperAdmin() || (!$linkedMilitaryPersonnel && ($request->user()->can('leave-management.approvals.approve') || $request->user()->can('leave-management.approve')));
          $isMilitaryAccount = !$request->user()->isSuperAdmin() && $linkedMilitaryPersonnel;
          $isMilitaryAccount = !$canProposeForUnit && ($request->user()->hasRole(\App\Support\RoleCatalog::LEAVE_MILITARY) || $linkedMilitaryPersonnel);
           $isMilitaryAccount = !$request->user()->isSuperAdmin()
               && ($request->user()->hasRole(\App\Support\RoleCatalog::LEAVE_MILITARY) || (bool) $linkedMilitaryPersonnel);
           if ($isMilitaryAccount && $linkedMilitaryPersonnel) {
               // Hồ sơ quân nhân của tài khoản là nguồn chuẩn; không tin personnel_id
               // có thể bị thay đổi bởi form/JavaScript phía trình duyệt.
               $data['personnel_id'] = $linkedMilitaryPersonnel->id;
           }
           $canSubmitProposal = $request->user()->isSuperAdmin()
               || $request->user()->can('leave-management.create')
               || $request->user()->can('leave-management.requests.create')
               || $request->user()->hasRole(\App\Support\RoleCatalog::LEAVE_MILITARY)
               || (bool) $linkedMilitaryPersonnel;
           if (!$canSubmitProposal) {
               abort(403, 'Tài khoản chưa được cấp quyền gửi đề xuất nghỉ phép.');
           }
          if ($isMilitaryAccount) {
              if ($scope !== 'PERSONAL') {
                  abort(403, 'Tài khoản quân nhân chỉ được đề xuất phép cá nhân.');
              }
              if (!in_array($data['leave_type'], ['ANNUAL', 'EXTRA'], true)) {
                  abort(422, 'Tài khoản quân nhân chỉ được chọn phép năm hoặc phép đặc biệt.');
              }
                if (empty($data['personnel_id']) || !$linkedMilitaryPersonnel || (int) $data['personnel_id'] !== (int) $linkedMilitaryPersonnel->id) {
                  abort(403, 'Tài khoản quân nhân chỉ được đề xuất phép cho chính mình.');
              }
          }
          if (in_array($scope, ['CLASS', 'SHORT_LEAVE'], true) && !$canProposeForUnit) {
            abort(403, 'Chỉ đại đội/chỉ huy cơ quan mới được đề xuất phép lớp hoặc phép tranh thủ.');
        }
        if (in_array($scope, ['PERSONAL', 'INDIVIDUAL'], true) && empty($data['personnel_id'])) {
            return back()->withErrors(['personnel_id' => 'Phép cá nhân phải chọn một quân nhân.'])->withInput();
        }
        if ($scope === 'CLASS' && empty($data['class_id'])) {
            return back()->withErrors(['class_id' => 'Phép lớp phải chọn lớp.'])->withInput();
        }
        if ($scope === 'SHORT_LEAVE' && (empty($data['class_id']) || empty($data['personnel_ids']))) {
            return back()->withErrors(['personnel_ids' => 'Phép tranh thủ phải chọn lớp và tích ít nhất một học viên.'])->withInput();
        }
        if (in_array($scope, ['PERSONAL', 'INDIVIDUAL'], true) && trim((string) ($data['reason'] ?? '')) === '') {
            if ($data['leave_type'] === 'ANNUAL') {
                $data['reason'] = 'Nghỉ phép năm';
            } else {
                return back()->withErrors(['reason' => 'Phép cá nhân phải nhập lý do nghỉ.'])->withInput();
            }
        }

        $class = !empty($data['class_id']) ? LeaveClass::with('personnel')->findOrFail($data['class_id']) : null;
        $people = match ($scope) {
            'CLASS' => $class->personnel->where('active', true)->values(),
            'SHORT_LEAVE' => LeavePersonnel::with(['unitRelation', 'commander'])->whereIn('id', $data['personnel_ids'])->where('active', true)->get(),
            default => collect([($isMilitaryAccount ? LeavePersonnel::withoutGlobalScopes() : LeavePersonnel::query())->with(['unitRelation', 'commander'])->findOrFail($data['personnel_id'])]),
        };

        if ($people->isEmpty()) {
            return back()->withErrors(['class_id' => 'Lớp chưa có học viên để đề xuất phép.'])->withInput();
        }
        if (!empty($data['replacement_personnel_id'])) {
            $replacement = LeavePersonnel::withoutGlobalScopes()->where('active', true)->findOrFail($data['replacement_personnel_id']);
            foreach ($people as $person) {
                abort_unless((int) $replacement->unit_id === (int) $person->unit_id, 422, 'Người thay thế phải thuộc cùng đơn vị với quân nhân nghỉ.');
            }
        }
        foreach ($people as $person) {
            // Quân nhân đã được xác thực ở trên là hồ sơ của chính tài khoản
            // đang đăng nhập; không áp dụng scope quản lý đơn vị của manager
            // lên trường hợp tự gửi đơn cá nhân này.
            if (!$isMilitaryAccount) {
                LeaveAccess::ensurePersonnel((int) $person->id, $request->user());
            }
        }

        $from = Carbon::parse($data['from_date']);
        $to = $data['to_date'] ? Carbon::parse($data['to_date']) : null;
        if ($scope === 'SHORT_LEAVE') {
            $from = $from->setTime(18, 0);
            $to = $from->copy()->addDays(2)->setTime(15, 0);
            $data['leave_type'] = 'SHORT_LEAVE';
        }
        if (!$to) {
            $to = $from->copy();
        }
        $totalDays = $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $locality = !empty($data['locality_id']) ? LeaveLocality::find($data['locality_id']) : null;
        $standards = LeaveRegulation::where('leave_type', 'EXTRA')->where('active', true)->whereIn('id', $data['extra_standard_ids'] ?? [])->orderBy('sort_order')->get();
        $extraDays = $standards->sum('base_days');
        $travelDays = (int) ($data['travel_days'] ?? 0);
        $created = 0;

        DB::transaction(function () use ($people, $scope, $class, $data, $request, $from, $to, $totalDays, $extraDays, $travelDays, $standards, $locality, $isMilitaryAccount, &$created): void {
            foreach ($people as $person) {
                // Thâm niên tính theo năm lịch: năm hiện tại - năm nhập ngũ.
                // Không dùng số năm tròn theo ngày/tháng và không lấy phần thập phân.
                $serviceYears = $person->enlistment_date
                    ? max(0, now()->year - Carbon::parse($person->enlistment_date)->year)
                    : 0;
                $baseDays = $scope === 'CLASS' || $scope === 'SHORT_LEAVE' ? $totalDays : (int) (LeaveRegulation::where('leave_type', $data['leave_type'])->where('active', true)->where(function ($q) use ($person) { $q->whereNull('object_type')->orWhere('object_type', $person->object_type); })->where(function ($q) use ($serviceYears) { $q->whereNull('min_years')->orWhere('min_years', '<=', $serviceYears); })->where(function ($q) use ($serviceYears) { $q->whereNull('max_years')->orWhere('max_years', '>=', $serviceYears); })->orderByRaw('CASE WHEN object_type = ? THEN 0 ELSE 1 END', [$person->object_type])->orderByDesc('min_years')->value('base_days') ?? 0);
                $personStandards = $standards->filter(function ($standard) use ($person, $serviceYears) {
                    return (!$standard->object_type || $standard->object_type === $person->object_type)
                        && (!$standard->min_years || $standard->min_years <= $serviceYears)
                        && (!$standard->max_years || $standard->max_years >= $serviceYears);
                })->values();
                $personExtraDays = (int) $personStandards->sum('base_days');
                $personTotalDays = $baseDays + $travelDays + $personExtraDays;
                $effectiveTo = $to;
                if ($isMilitaryAccount || ($scope === 'PERSONAL' && $data['leave_type'] === 'ANNUAL')) {
                    $effectiveTo = $from->copy()->addDays(max(0, $personTotalDays - 1));
                }
                $personLocality = $locality;
                if ($isMilitaryAccount && !$personLocality && $person->permanent_residence) {
                    $personLocality = LeaveLocality::where('name', $person->permanent_residence)->first();
                }
                  $positionKey = Str::lower(Str::ascii((string) $person->position));
                  $isTopLeadership = Str::contains($positionKey, ['hieu truong', 'pho hieu truong']);
                  // Không lưu liên kết chỉ huy trên từng hồ sơ. Tài khoản có role
                  // agency-commander và cùng đơn vị sẽ tự động nhận đề xuất.
                  $commanderUser = LeaveAccess::commanderForUnit((int) $person->unit_id);
                  $commander = $isTopLeadership ? null : $commanderUser?->id;
                 if (!$commander && in_array($scope, ['CLASS', 'SHORT_LEAVE'], true) && (int) $request->user()->unit_id === (int) $person->unit_id && $request->user()->hasRole(\App\Support\RoleCatalog::LEAVE_COMMANDER)) {
                     $commander = $request->user()->id;
                     $commanderUser = $request->user();
                 }
                  if (!$commander && !$isTopLeadership) {
                    abort(422, 'Chưa có tài khoản chỉ huy/cơ quan quản lý được cấp quyền duyệt phép để tiếp nhận đề xuất.');
                 }
                $payload = [
                    'personnel_id' => $scope === 'CLASS' ? null : $person->id,
                    'class_id' => $class?->id,
                    'class_name' => $class?->name,
                    'personnel_code' => $scope === 'CLASS' ? null : $person->staff_code,
                    'personnel_name' => $scope === 'CLASS' ? 'Toàn bộ lớp '.$class?->name : $person->name,
                    'from_date' => $from->toDateString(), 'to_date' => $effectiveTo->toDateString(),
                    'leave_type' => $data['leave_type'], 'request_scope' => $scope,
                    'reason' => $data['reason'] ?? ($scope === 'CLASS' ? 'Nghỉ hè theo lớp' : 'Nghỉ tranh thủ cuối tuần'),
                     'note' => $data['reason'] ?? null, 'status' => $isTopLeadership ? 'PENDING_AGENCY' : 'PENDING_COMMANDER',
                    'created_by' => $request->user()->id, 'proposed_by_user_id' => $request->user()->id,
                    'proposed_by_username' => $request->user()->email, 'proposed_by_display_name' => $request->user()->name,
                    'proposer_email' => $request->user()->email, 'object_type' => $person->object_type,
                    'rank' => $person->rank, 'position' => $person->position, 'enlistment_date' => $person->enlistment_date,
                    'unit_id' => $person->unit_id, 'unit_name' => $person->unitRelation?->name,
                    'service_years' => $serviceYears, 'base_days' => $baseDays, 'travel_days' => $travelDays,
                    'extra_days' => $personExtraDays, 'extra_reasons' => $personStandards->map(fn ($s) => ['id' => $s->id, 'label' => $s->label, 'days' => $s->base_days])->values()->all(),
                    'total_days' => $scope === 'CLASS' || $scope === 'SHORT_LEAVE' ? $totalDays : $personTotalDays,
                    'leave_year' => $from->year, 'locality_id' => $personLocality?->id, 'locality_path' => $personLocality?->name ?: $person->permanent_residence,
                    'replacement_personnel_id' => $data['replacement_personnel_id'] ?? null,
                    'commander_user_id' => $commander, 'commander_name' => $person->commander?->name ?: ($person->commander_name ?: $commanderUser?->name), 'managing_agency' => LeaveAccess::agencyForObject($person->object_type),
                ];
                $leave = LeaveRequest::create($payload);
                LeaveAuditLog::create(['user_id' => $request->user()->id, 'action' => 'CREATE', 'entity_type' => 'request', 'entity_id' => $leave->id, 'details' => $payload]);
                 if ($isTopLeadership) {
                     $this->notifyAgency($leave);
                     $created++;
                     continue;
                 }
                 LeaveAlert::create(['user_id' => $commander, 'request_id' => $leave->id, 'kind' => 'PENDING_COMMANDER', 'title' => 'Đề xuất nghỉ phép cần chỉ huy xem xét', 'body' => $payload['personnel_name'].' đã gửi đề xuất nghỉ phép #'.$leave->id.'.']);
                $created++;
                if ($scope === 'CLASS') {
                    break;
                }
            }
        });

        return back()->with('success', $scope === 'SHORT_LEAVE' ? "Đã tạo {$created} đề xuất nghỉ tranh thủ cho học viên được chọn." : 'Đã tạo đề xuất nghỉ phép và gửi chờ chỉ huy duyệt.');
    }

    private function notifyAgency(LeaveRequest $leave): void
    {
        $agency = (string) ($leave->managing_agency ?: LeaveAccess::QUAN_LUC);
        \App\Models\User::where('status', 1)->get()
            ->filter(fn ($user) => LeaveAccess::canHandleAgency($agency, $user))
            ->each(fn ($user) => LeaveAlert::create([
                'user_id' => $user->id,
                'request_id' => $leave->id,
                'kind' => 'PENDING_AGENCY',
                'title' => 'Đơn nghỉ phép chờ cơ quan tiếp nhận xử lý',
                'body' => $leave->personnel_name.' đã gửi đơn nghỉ phép #'.$leave->id.' và được chuyển thẳng đến cơ quan tiếp nhận.',
            ]));
    }
}
