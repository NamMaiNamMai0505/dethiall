<?php

namespace Modules\LeaveManagement\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\LeaveManagement\Models\{LeaveAlert, LeaveAuditLog, LeaveClass, LeaveLocality, LeavePersonnel, LeaveRegulation, LeaveRequest};
use Modules\LeaveManagement\Support\LeaveAccess;

class LeaveProposalController extends ModuleBaseController
{
    public function store(Request $request)
    {
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
            return back()->withErrors(['reason' => 'Phép cá nhân phải nhập lý do nghỉ.'])->withInput();
        }

        $class = !empty($data['class_id']) ? LeaveClass::with('personnel')->findOrFail($data['class_id']) : null;
        $people = match ($scope) {
            'CLASS' => $class->personnel->where('active', true)->values(),
            'SHORT_LEAVE' => LeavePersonnel::with(['unitRelation', 'commander'])->whereIn('id', $data['personnel_ids'])->where('active', true)->get(),
            default => collect([LeavePersonnel::with(['unitRelation', 'commander'])->findOrFail($data['personnel_id'])]),
        };

        if ($people->isEmpty()) {
            return back()->withErrors(['class_id' => 'Lớp chưa có học viên để đề xuất phép.'])->withInput();
        }
        foreach ($people as $person) {
            LeaveAccess::ensurePersonnel((int) $person->id, $request->user());
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

        DB::transaction(function () use ($people, $scope, $class, $data, $request, $from, $to, $totalDays, $extraDays, $travelDays, $standards, $locality, &$created): void {
            foreach ($people as $person) {
                $serviceYears = $person->enlistment_date ? Carbon::parse($person->enlistment_date)->diffInYears(now()) : 0;
                $baseDays = $scope === 'CLASS' || $scope === 'SHORT_LEAVE' ? $totalDays : (int) (LeaveRegulation::where('leave_type', $data['leave_type'])->where('active', true)->where(function ($q) use ($person) { $q->whereNull('object_type')->orWhere('object_type', $person->object_type); })->where(function ($q) use ($serviceYears) { $q->whereNull('min_years')->orWhere('min_years', '<=', $serviceYears); })->where(function ($q) use ($serviceYears) { $q->whereNull('max_years')->orWhere('max_years', '>=', $serviceYears); })->orderByDesc('base_days')->value('base_days') ?? 0);
                $commander = $person->commander_user_id;
                if (!$commander) {
                    abort(422, 'Quân nhân '.$person->name.' chưa được gán chỉ huy cơ quan.');
                }
                $payload = [
                    'personnel_id' => $scope === 'CLASS' ? null : $person->id,
                    'class_id' => $class?->id,
                    'class_name' => $class?->name,
                    'personnel_code' => $scope === 'CLASS' ? null : $person->staff_code,
                    'personnel_name' => $scope === 'CLASS' ? 'Toàn bộ lớp '.$class?->name : $person->name,
                    'from_date' => $from->toDateString(), 'to_date' => $to->toDateString(),
                    'leave_type' => $data['leave_type'], 'request_scope' => $scope,
                    'reason' => $data['reason'] ?? ($scope === 'CLASS' ? 'Nghỉ hè theo lớp' : 'Nghỉ tranh thủ cuối tuần'),
                    'note' => $data['reason'] ?? null, 'status' => 'PENDING_COMMANDER',
                    'created_by' => $request->user()->id, 'proposed_by_user_id' => $request->user()->id,
                    'proposed_by_username' => $request->user()->email, 'proposed_by_display_name' => $request->user()->name,
                    'proposer_email' => $request->user()->email, 'object_type' => $person->object_type,
                    'rank' => $person->rank, 'position' => $person->position, 'enlistment_date' => $person->enlistment_date,
                    'unit_id' => $person->unit_id, 'unit_name' => $person->unitRelation?->name,
                    'service_years' => $serviceYears, 'base_days' => $baseDays, 'travel_days' => $travelDays,
                    'extra_days' => $extraDays, 'extra_reasons' => $standards->map(fn ($s) => ['id' => $s->id, 'label' => $s->label, 'days' => $s->base_days])->values()->all(),
                    'total_days' => $scope === 'CLASS' || $scope === 'SHORT_LEAVE' ? $totalDays : $baseDays + $travelDays + $extraDays,
                    'leave_year' => $from->year, 'locality_id' => $locality?->id, 'locality_path' => $locality?->name,
                    'replacement_personnel_id' => $data['replacement_personnel_id'] ?? null,
                    'commander_user_id' => $commander, 'commander_name' => $person->commander?->name ?: $person->commander_name,
                ];
                $leave = LeaveRequest::create($payload);
                LeaveAuditLog::create(['user_id' => $request->user()->id, 'action' => 'CREATE', 'entity_type' => 'request', 'entity_id' => $leave->id, 'details' => $payload]);
                LeaveAlert::create(['user_id' => $commander, 'request_id' => $leave->id, 'kind' => 'PENDING_COMMANDER', 'title' => 'Đề xuất nghỉ phép cần chỉ huy xem xét', 'body' => $payload['personnel_name'].' đã gửi đề xuất nghỉ phép #'.$leave->id.'.']);
                $created++;
                if ($scope === 'CLASS') {
                    break;
                }
            }
        });

        return back()->with('success', $scope === 'SHORT_LEAVE' ? "Đã tạo {$created} đề xuất nghỉ tranh thủ cho học viên được chọn." : 'Đã tạo đề xuất nghỉ phép và gửi chờ chỉ huy duyệt.');
    }
}
