<?php
namespace Modules\ExamOrganization\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Subject\Models\Subject;
use Modules\Class\Models\ClassModel;
use Modules\Instructor\Models\Instructor;
use Modules\ExamOrganization\Models\ExamOrganizationPlan;
use Modules\ExamOrganization\Models\ExamOrganizationAction;
use Modules\ExamOrganization\Models\ExamOrganizationCandidate;
use Modules\ExamOrganization\Models\ExamOrganizationLog;
use Modules\DatabaseManagement\Models\DatabaseManagementAudit;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Modules\Classroom\Models\Classroom;

class ExamOrganizationController extends Controller
{
    public function index(Request $r)
    {
        $section = $r->string('section')->toString() ?: 'planning';
        $plans = ExamOrganizationPlan::with(['subject','class'])->latest()->paginate(20)->withQueryString();
        $allPlans = ExamOrganizationPlan::with(['subject','class'])->latest()->get();
        $actionsQuery = ExamOrganizationAction::with(['plan.subject','plan.class','instructor'])->latest();
        if ($r->filled('plan_id')) $actionsQuery->where('plan_id', $r->integer('plan_id'))->whereNotNull('instructor_id');
        else $actionsQuery->whereRaw('1 = 0');
        $actions = $actionsQuery->get();
        $instructors = Instructor::orderBy('name')->get();
        $subjects = Subject::active()->orderBy('name')->get();
        $classes = ClassModel::where('is_active', true)->orderBy('name')->get();
        $classrooms = Classroom::where('status', true)->orderBy('name')->get();
        $selectedPlanId = $r->integer('plan_id') ?: null;
        $selectedPlan = $selectedPlanId ? ExamOrganizationPlan::with('class')->find($selectedPlanId) : null;
        $selectedLogs = $selectedPlanId ? ExamOrganizationLog::where('plan_id', $selectedPlanId)->latest()->get() : collect();
        $packetLogs = $selectedPlanId ? ExamOrganizationLog::where('plan_id', $selectedPlanId)->where('process_type', 'PACKET')->latest()->get() : collect();
        $packetCandidateCounts = $selectedPlanId ? ExamOrganizationCandidate::where('plan_id', $selectedPlanId)->whereNotNull('packet_number')->selectRaw('packet_number, COUNT(*) as total')->groupBy('packet_number')->pluck('total', 'packet_number') : collect();
        $allPacketLogs = $section === 'post_exam' ? ExamOrganizationLog::with(['plan.class','plan.subject'])->where('process_type', 'PACKET')->latest()->limit(200)->get() : collect();
        $allPacketCandidateCounts = $section === 'post_exam' ? ExamOrganizationCandidate::whereIn('plan_id', $allPacketLogs->pluck('plan_id')->unique())->whereNotNull('packet_number')->selectRaw('plan_id, packet_number, COUNT(*) as total')->groupBy('plan_id','packet_number')->get()->mapWithKeys(fn($row) => [$row->plan_id.'|'.$row->packet_number => $row->total]) : collect();
        $allPacketCandidates = $section === 'post_exam' ? ExamOrganizationCandidate::whereIn('plan_id', $allPacketLogs->pluck('plan_id')->unique())->whereNotNull('packet_number')->orderBy('candidate_number')->get()->groupBy(fn($candidate) => $candidate->plan_id.'|'.$candidate->packet_number) : collect();
        $preExamLogs = $section === 'pre_exam' ? ExamOrganizationLog::with(['plan.class','plan.subject'])->whereIn('process_type', ['CANDIDATE_NUMBER','ROOM_REGISTER','ROOM_ASSIGN'])->latest()->limit(200)->get() : collect();
        $preExamCandidates = $section === 'pre_exam' ? ExamOrganizationCandidate::whereIn('plan_id', $preExamLogs->pluck('plan_id')->unique())->orderBy('candidate_number')->get()->groupBy('plan_id') : collect();
        $allGradingLogs = $section === 'grading' ? ExamOrganizationLog::with(['plan.class','plan.subject'])->whereIn('process_type', ['GRADING_DIRECT','GRADING_ROOM','GRADING_PACKET'])->latest()->limit(200)->get() : collect();
        $allGradingCandidates = $section === 'grading' ? ExamOrganizationCandidate::whereIn('plan_id', $allGradingLogs->pluck('plan_id')->unique())->orderBy('candidate_number')->get()->groupBy('plan_id') : collect();
        $gradingMode = $r->string('grading_mode')->toString() ?: 'direct';
        $gradingRoom = $r->string('room_name')->toString();
        $gradingPacket = $r->string('packet_number')->toString();
        $gradingClassId = $r->integer('grading_class_id') ?: ($selectedPlan?->class_id);
        $gradingClass = $classes->firstWhere('id', $gradingClassId);
        $candidates = $selectedPlanId ? ExamOrganizationCandidate::where('plan_id', $selectedPlanId)->when($section === 'grading' && $gradingMode === 'room' && $gradingRoom !== '', fn($q) => $q->where('room_name', $gradingRoom))->when($section === 'grading' && $gradingMode === 'packet' && $gradingPacket !== '', fn($q) => $q->where('packet_number', $gradingPacket))->when($section === 'grading' && $gradingMode === 'direct' && $gradingClass, fn($q) => $q->where('class_name', $gradingClass->name))->orderBy('candidate_number')->get() : collect();
        $rooms = $selectedPlanId ? ExamOrganizationCandidate::where('plan_id', $selectedPlanId)->whereNotNull('room_name')->distinct()->orderBy('room_name')->pluck('room_name') : collect();
        $packets = $selectedPlanId ? ExamOrganizationCandidate::where('plan_id', $selectedPlanId)->whereNotNull('packet_number')->distinct()->orderBy('packet_number')->pluck('packet_number') : collect();
        $registeredRooms = $selectedPlanId ? ExamOrganizationLog::where('plan_id', $selectedPlanId)->where('process_type', 'ROOM_REGISTER')->whereNotNull('method')->pluck('method')->unique()->values() : collect();
        return view('exam-organization::index', compact('plans','allPlans','actions','instructors','subjects','classes','classrooms','section','selectedPlanId','selectedPlan','selectedLogs','packetLogs','packetCandidateCounts','allPacketLogs','allPacketCandidateCounts','allPacketCandidates','preExamLogs','preExamCandidates','allGradingLogs','allGradingCandidates','gradingMode','gradingRoom','gradingPacket','gradingClassId','gradingClass','candidates','rooms','packets','registeredRooms'));
    }

    public function store(Request $r)
    {
        $d = $r->validate(['name'=>'nullable|string|max:255','exam_category'=>'required|in:REGULAR,PERIODIC,FINAL_1,FINAL_2,OTHER','custom_exam_name'=>'nullable|string|max:255','subject_id'=>'required|exists:subjects,id','class_id'=>'required|exists:classes,id','exam_date'=>'required|date','exam_time'=>'nullable','exam_form'=>'required|string|max:50','exam_type'=>'required|in:TRẮC NGHIỆM,THỰC HÀNH,TỰ LUẬN','note'=>'nullable|string']);
        if ($d['exam_category'] === 'OTHER') {
            abort_unless(filled($d['custom_exam_name']), 422, 'Hãy nhập tên kỳ thi khác.');
            $d['name'] = $d['custom_exam_name'];
        } else {
            $d['name'] = ['REGULAR'=>'Kiểm tra thường xuyên','PERIODIC'=>'Kiểm tra định kỳ','FINAL_1'=>'Thi kết thúc môn lần 1','FINAL_2'=>'Thi kết thúc môn lần 2'][$d['exam_category']];
        }
        unset($d['custom_exam_name']);
        $createdPlan = ExamOrganizationPlan::create($d + ['status'=>'PLANNED']);
        DatabaseManagementAudit::query()->create(['actor_id'=>$r->user()->id,'action'=>'exam_organization.plan_created','table_name'=>'exam_organization_plans','record_key'=>(string)$createdPlan->id,'after_values'=>['name'=>$createdPlan->name,'class_id'=>$createdPlan->class_id,'subject_id'=>$createdPlan->subject_id],'request_id'=>$r->header('X-Request-Id')]);
        return back()->with('success','Đã lập kế hoạch thi.');
    }

    public function action(Request $r)
    {
        $d = $r->validate(['plan_id'=>'required|exists:exam_organization_plans,id','action_type'=>'required|in:ASSIGNMENT,EXECUTION,GRADING','name'=>'nullable|string|max:255','instructor_ids'=>'nullable|array','instructor_ids.*'=>'exists:instructors,id','role'=>'nullable|in:INVIGILATOR,GRADER,REVIEWER','note'=>'nullable|string']);
        $permission = ['ASSIGNMENT'=>'exam-organization.assignment','EXECUTION'=>'exam-organization.execution','GRADING'=>'exam-organization.grading'][$d['action_type']];
        abort_unless($r->user()->can($permission), 403, 'Bạn không có quyền thực hiện bước tổ chức thi này.');
        $name = $d['name'] ?? 'Phân công giáo viên';
        foreach (($d['instructor_ids'] ?? [null]) as $id) {
            ExamOrganizationAction::create(['plan_id'=>$d['plan_id'],'action_type'=>$d['action_type'],'name'=>$name,'instructor_id'=>$id,'role'=>$d['role'] ?? null,'note'=>$d['note'] ?? null,'status'=>'CREATED']);
        }
        return back()->with('success','Đã lưu phân công/ghi nhận sự cố.');
    }

    public function process(Request $r)
    {
        abort_unless($r->user()->can('exam-organization.execution'), 403, 'Bạn không có quyền xử lý kỳ thi.');
        $d = $r->validate([
            'plan_id'=>'required|exists:exam_organization_plans,id',
            'class_id'=>'nullable|required_if:process_type,CANDIDATE_NUMBER|exists:classes,id',
            'process_type'=>'required|in:CANDIDATE_NUMBER,ROOM_REGISTER,ROOM_ASSIGN,ROOM_REGISTER_ASSIGN,ABSENT,PACKET,CIPHER,GRADING_DIRECT,GRADING_PACKET,GRADING_ROOM,VERIFY_VIEW,VERIFY_PRINT,DOC_PACKET,DOC_CIPHER,DOC_MINUTES,RESULT_CLASS,RESULT_GOOD,RESULT_WEAK,RESULT_STATISTICS',
            'method'=>'nullable|string|max:80',
            'desks_horizontal'=>'nullable|required_if:process_type,ROOM_ASSIGN|required_if:process_type,ROOM_REGISTER_ASSIGN|integer|min:1|max:100',
            'desks_vertical'=>'nullable|required_if:process_type,ROOM_ASSIGN|required_if:process_type,ROOM_REGISTER_ASSIGN|integer|min:1|max:100',
            'student_file'=>'nullable|file|mimes:txt,csv,tsv|max:10240',
            'from_number'=>'nullable|string|max:30',
            'to_number'=>'nullable|string|max:30',
            'room_name'=>'nullable|required_if:process_type,ROOM_REGISTER|required_if:process_type,ROOM_REGISTER_ASSIGN|exists:classrooms,name|max:100',
            'candidate_number'=>'nullable|string|max:30',
            'packet_number'=>'nullable|string|max:30',
            'packet_mode'=>'nullable|required_if:process_type,PACKET|in:SINGLE,DOUBLE',
            'single_variant'=>'nullable|required_if:packet_mode,SINGLE|in:EVEN,ODD',
            'single_total'=>'nullable|integer|min:1', 'single_packet_count'=>'nullable|integer|min:1', 'single_per_packet'=>'nullable|integer|min:1', 'single_last_packet'=>'nullable|integer|min:1',
            'even_total'=>'nullable|integer|min:1', 'even_packet_count'=>'nullable|integer|min:1', 'even_per_packet'=>'nullable|integer|min:1', 'even_last_packet'=>'nullable|integer|min:1',
            'odd_total'=>'nullable|integer|min:1', 'odd_packet_count'=>'nullable|integer|min:1', 'odd_per_packet'=>'nullable|integer|min:1', 'odd_last_packet'=>'nullable|integer|min:1',
            'double_even_source'=>'nullable|in:EVEN,ODD',
            // Number inputs arrive as strings from the browser. Validate them as
            // numeric first, then cast them explicitly when assigning ciphers.
            'cipher_from'=>'nullable|numeric|min:1', 'cipher_to'=>'nullable|numeric|gte:cipher_from',
            'score'=>'nullable|numeric|min:0|max:10',
            'scores'=>'nullable|array',
            'scores.*'=>'nullable|numeric|min:0|max:10',
        ]);
        $plan = ExamOrganizationPlan::findOrFail($d['plan_id']);
        if ($d['process_type'] === 'CANDIDATE_NUMBER') {
            abort_unless($r->filled('class_id'), 422, 'Hãy chọn lớp trước khi đánh số báo danh.');
        }
        $query = $plan->candidates();
        $packetLogMethods = [];
        $assignSeats = function ($candidates, string $method, int $horizontal, int $vertical): void {
            $seatOrder = [];
            if ($method === 'VERTICAL') {
                for ($column = 0; $column < $horizontal; $column++) {
                    $rows = range(0, $vertical - 1);
                    if ($column % 2 === 1) $rows = array_reverse($rows);
                    foreach ($rows as $row) $seatOrder[] = [$row, $column];
                }
            } else {
                for ($row = 0; $row < $vertical; $row++) {
                    $columns = range(0, $horizontal - 1);
                    if ($row % 2 === 1) $columns = array_reverse($columns);
                    foreach ($columns as $column) $seatOrder[] = [$row, $column];
                }
            }
            abort_if($candidates->count() > count($seatOrder), 422, 'Số học viên vượt quá số bàn trong phòng thi.');
            foreach ($candidates as $index => $candidate) $candidate->update(['seat_number'=>(string)($index + 1)]);
        };

        if ($d['process_type'] === 'CANDIDATE_NUMBER' && $r->filled('class_id')) {
            $plan->candidates()->delete();
            $class = ClassModel::findOrFail($d['class_id']);
            User::query()->where('class_id', $class->id)->where('user_type', 'student')->orderBy('name')->get()->each(function ($student) use ($plan, $class): void {
                ExamOrganizationCandidate::create(['plan_id'=>$plan->id,'student_code'=>$student->code,'student_name'=>$student->name,'class_name'=>$class->name]);
            });
        } elseif ($d['process_type'] === 'CANDIDATE_NUMBER' && $r->hasFile('student_file')) {
            $contents = preg_split('/\r\n|\r|\n/', trim($r->file('student_file')->get()));
            $plan->candidates()->delete();
            foreach ($contents as $line) {
                $parts = array_map('trim', str_getcsv($line, str_contains($line, "\t") ? "\t" : ','));
                if (!array_filter($parts)) continue;
                $name = $parts[1] ?? $parts[0];
                if (mb_strtolower($name) === 'họ tên' || mb_strtolower($name) === 'ho ten') continue;
                ExamOrganizationCandidate::create(['plan_id'=>$plan->id,'student_code'=>$parts[0] ?? null,'student_name'=>$name,'class_name'=>$parts[2] ?? $plan->class?->name]);
            }
        }

        if ($d['process_type'] === 'CANDIDATE_NUMBER') {
            $candidates = $query->get();
            $method = $d['method'] ?? 'NAME_ASC';
            $candidates = match ($method) {
                'CLASS_ASC' => $candidates->sortBy(fn($c) => ($c->class_name ?? '').'|'.$c->student_name)->values(),
                'RANDOM' => $candidates->shuffle()->values(),
                default => $candidates->sortBy(fn($c) => $this->nameSortKey($c->student_name))->values(),
            };
            foreach ($candidates as $i => $candidate) $candidate->update(['candidate_number'=>str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT)]);
        } elseif ($d['process_type'] === 'ROOM_ASSIGN') {
            $registeredRooms = ExamOrganizationLog::where('plan_id', $plan->id)->where('process_type', 'ROOM_REGISTER')->pluck('method')->all();
            abort_unless(in_array($d['room_name'] ?? '', $registeredRooms, true), 422, 'Phòng thi chưa được đăng ký cho kế hoạch này.');
            $candidates = $query->orderBy('candidate_number')->get();
            $assignSeats($candidates, $d['method'] ?? 'HORIZONTAL', (int)$d['desks_horizontal'], (int)$d['desks_vertical']);
            $candidates->each(fn($candidate) => $candidate->update(['room_name'=>$d['room_name'] ?: 'Phòng 01']));
        } elseif ($d['process_type'] === 'ROOM_REGISTER') {
            abort_unless(filled($d['room_name'] ?? null), 422, 'Hãy nhập phòng thi cần đăng ký.');
        } elseif ($d['process_type'] === 'ROOM_REGISTER_ASSIGN') {
            abort_unless(filled($d['room_name'] ?? null), 422, 'Hãy chọn phòng thi.');
            $candidates = $query->orderBy('candidate_number')->get();
            $assignSeats($candidates, $d['method'] ?? 'HORIZONTAL', (int)$d['desks_horizontal'], (int)$d['desks_vertical']);
            $candidates->each(fn($candidate) => $candidate->update(['room_name'=>$d['room_name']]));
        } elseif ($d['process_type'] === 'ABSENT') {
            $query->where('candidate_number', $d['from_number'] ?? '')->update(['absent'=>true]);
        } elseif ($d['process_type'] === 'CIPHER') {
            $from = (int)($d['from_number'] ?? 1); $to = (int)($d['to_number'] ?? $from);
            abort_unless($to >= $from, 422, 'Khoảng số phách không hợp lệ.');
            foreach ($query->inRandomOrder()->get() as $i => $candidate) $candidate->update(['cipher_number'=>(string)($from + ($i % ($to - $from + 1)))]);
        } elseif ($d['process_type'] === 'PACKET') {
            $allCandidates = $query->orderBy('candidate_number')->get();
            $assignPacketGroup = function ($group, string $label, int $total, int $packetCount, int $perPacket, int $lastPacket) use (&$packetLogMethods): void {
                abort_unless($total <= $group->count(), 422, 'Số bài thi khai báo vượt quá số học viên của nhóm.');
                abort_unless($packetCount === (int)ceil($total / $perPacket), 422, 'Số túi hoặc số bài mỗi túi không khớp với tổng số bài thi.');
                abort_unless($lastPacket === ($total - ($packetCount - 1) * $perPacket), 422, 'Số bài thi trong túi cuối cùng không khớp.');
                $group = $group->take($total)->values();
                for ($packet = 0; $packet < $packetCount; $packet++) {
                    $items = $group->slice($packet * $perPacket, $packet === $packetCount - 1 ? $lastPacket : $perPacket);
                    $packetName = 'Túi '.str_pad((string)($packet + 1), 2, '0', STR_PAD_LEFT).' - '.$label;
                    foreach ($items as $candidate) $candidate->update(['packet_number'=>$packetName]);
                    $packetLogMethods[] = $packetName;
                }
            };
            if (($d['packet_mode'] ?? null) === 'SINGLE') {
                $variant = $d['single_variant']; $group = $allCandidates->filter(fn($candidate) => ((int)$candidate->candidate_number % 2 === ($variant === 'EVEN' ? 0 : 1)))->values();
                $assignPacketGroup($group, $variant === 'EVEN' ? 'Đề chẵn' : 'Đề lẻ', $group->count(), 1, max(1, $group->count()), $group->count());
            } elseif (($d['packet_mode'] ?? null) === 'DOUBLE') {
                $evenSource = $d['double_even_source'] ?? 'EVEN'; $oddSource = $evenSource === 'EVEN' ? 'ODD' : 'EVEN';
                $evenGroup = $allCandidates->filter(fn($candidate) => ((int)$candidate->candidate_number % 2 === ($evenSource === 'EVEN' ? 0 : 1)))->values();
                $oddGroup = $allCandidates->filter(fn($candidate) => ((int)$candidate->candidate_number % 2 === ($oddSource === 'EVEN' ? 0 : 1)))->values();
                $assignPacketGroup($evenGroup, 'Đề chẵn', $evenGroup->count(), 1, max(1, $evenGroup->count()), $evenGroup->count());
                $assignPacketGroup($oddGroup, 'Đề lẻ', $oddGroup->count(), 1, max(1, $oddGroup->count()), $oddGroup->count());
            }
            if (filled($d['cipher_from'] ?? null) && filled($d['cipher_to'] ?? null)) {
                $cipherPool = range((int)$d['cipher_from'], (int)$d['cipher_to']); $allCandidates = $allCandidates->shuffle();
                abort_unless(count($cipherPool) >= $allCandidates->count(), 422, 'Khoảng số phách không đủ cho số bài thi.');
                foreach ($allCandidates as $index => $candidate) $candidate->update(['cipher_number'=>(string)$cipherPool[$index]]);
            }
        } elseif (in_array($d['process_type'], ['GRADING_DIRECT','GRADING_PACKET','GRADING_ROOM'], true)) {
            if (isset($d['scores'])) {
                foreach ($d['scores'] as $candidateId => $score) {
                    if ($score === null || $score === '') continue;
                    $candidate = $plan->candidates()->whereKey($candidateId)->firstOrFail();
                    $candidate->update(['score'=>$score, 'score_method'=>$d['process_type']]);
                }
                ExamOrganizationLog::create(['plan_id'=>$plan->id,'process_type'=>$d['process_type'],'method'=>'Lưu bảng điểm','created_by'=>$r->user()->id]);
                DatabaseManagementAudit::query()->create(['actor_id'=>$r->user()->id,'action'=>'exam_organization.process','table_name'=>'exam_organization_candidates','record_key'=>(string)$plan->id,'after_values'=>['process_type'=>$d['process_type'],'scores_saved'=>count($d['scores'])],'request_id'=>$r->header('X-Request-Id')]);
                return back()->with('success','Đã lưu bảng điểm.');
            }
            abort_unless(filled($d['candidate_number']) && $d['score'] !== null, 422, 'Hãy nhập SBD và điểm.');
            $candidate = $query->where('candidate_number', $d['candidate_number'])->firstOrFail();
            $candidate->update(['score'=>$d['score'], 'score_method'=>$d['process_type']]);
        }

        $methodLabels = ['NAME_ASC'=>'Theo vần tên','CLASS_ASC'=>'Theo thứ tự vần lớp','RANDOM'=>'Hoàn toàn ngẫu nhiên','HORIZONTAL'=>'Theo hàng ngang','VERTICAL'=>'Theo hàng dọc'];
        if ($d['process_type'] === 'ROOM_REGISTER_ASSIGN') {
            ExamOrganizationLog::create(['plan_id'=>$plan->id,'process_type'=>'ROOM_REGISTER','method'=>$d['room_name'],'created_by'=>$r->user()->id]);
            ExamOrganizationLog::create(['plan_id'=>$plan->id,'process_type'=>'ROOM_ASSIGN','method'=>$methodLabels[$d['method'] ?? ''] ?? ($d['method'] ?? null),'created_by'=>$r->user()->id]);
        } elseif ($d['process_type'] === 'PACKET' && $packetLogMethods) {
            foreach ($packetLogMethods as $packetMethod) ExamOrganizationLog::create(['plan_id'=>$plan->id,'process_type'=>'PACKET','method'=>$packetMethod,'created_by'=>$r->user()->id]);
        } else {
            $logMethod = $d['process_type'] === 'ROOM_REGISTER' ? ($d['room_name'] ?? null) : ($d['process_type'] === 'PACKET' ? ($d['packet_number'] ?? null) : ($methodLabels[$d['method'] ?? ''] ?? ($d['method'] ?? null)));
            ExamOrganizationLog::create(['plan_id'=>$plan->id,'process_type'=>$d['process_type'],'method'=>$logMethod,'from_number'=>$d['from_number'] ?? null,'to_number'=>$d['to_number'] ?? null,'file_name'=>$r->file('student_file')?->getClientOriginalName(),'created_by'=>$r->user()->id]);
        }
        DatabaseManagementAudit::query()->create(['actor_id'=>$r->user()->id,'action'=>'exam_organization.process','table_name'=>'exam_organization_logs','record_key'=>(string)$plan->id,'after_values'=>['process_type'=>$d['process_type'],'method'=>$d['method'] ?? null,'packet_mode'=>$d['packet_mode'] ?? null],'request_id'=>$r->header('X-Request-Id')]);
        $section = match (true) {
            in_array($d['process_type'], ['CANDIDATE_NUMBER','ROOM_REGISTER','ROOM_ASSIGN','ROOM_REGISTER_ASSIGN'], true) => 'pre_exam',
            in_array($d['process_type'], ['ABSENT','PACKET','CIPHER'], true) => 'post_exam',
            str_starts_with($d['process_type'], 'GRADING_') || str_starts_with($d['process_type'], 'VERIFY_') => 'grading',
            str_starts_with($d['process_type'], 'DOC_') => 'marking_docs',
            default => 'results',
        };
        $params = ['section'=>$section, 'plan_id'=>$plan->id];
        if ($section === 'pre_exam') $params['pre_exam_mode'] = $d['process_type'];
        if ($section === 'post_exam') $params['post_exam_mode'] = $d['process_type'];
        return redirect()->route('exam-organization.index', $params)->with('success','Đã thực hiện và lưu nhật ký xử lý kỳ thi.');
    }

    private function nameSortKey(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        return mb_strtolower(($parts ? end($parts) : '').' '.$name);
    }
}
