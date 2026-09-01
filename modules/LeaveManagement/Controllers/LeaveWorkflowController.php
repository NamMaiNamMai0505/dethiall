<?php
namespace Modules\LeaveManagement\Controllers;
use App\Http\Controllers\ModuleBaseController;
use App\Support\ManagerUnitScope;
use App\Support\PermissionCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Class\Models\ClassModel;
use Modules\LeaveManagement\Models\{LeaveAlert,LeaveAuditLog,LeaveBatch,LeaveClass,LeaveExtraStandard,LeaveLocality,LeaveObjectType,LeavePosition,LeaveRecord,LeaveRegulation,LeaveReportTemplate,LeaveRequest,LeavePersonnel};
use Modules\LeaveManagement\Support\LeaveAccess;
use Modules\StandardHours\Models\Position;
use Modules\Unit\Models\Unit;
class LeaveWorkflowController extends ModuleBaseController {
    public function __construct(){
        $this->middleware('permission:leave-management.personnel.index|leave-management.personnel.show|leave-management.show')->only(['personnel']);
        $this->middleware('permission:leave-management.requests.index|leave-management.requests.show|leave-management.show|leave-management.create|leave-management.requests.create')->only(['requests','requestDetail']);
        $this->middleware('permission:leave-management.catalogs.index|leave-management.show')->only(['units','classes','localities','positions']);
        $this->middleware('permission:leave-management.regulations.index|leave-management.show')->only(['regulations']);
        $this->middleware('permission:leave-management.batches.index|leave-management.show')->only(['batches']);
        $this->middleware('permission:leave-management.records.index|leave-management.show')->only(['records']);
        $this->middleware('permission:leave-management.audit.index|leave-management.show')->only(['auditLogs']);
        $this->middleware('permission:leave-management.alerts.index|leave-management.show')->only(['alerts']);
        $this->middleware('permission:leave-management.reports.index|leave-management.show')->only(['reports']);
        $this->middleware('permission:leave-management.mail.index|leave-management.show')->only(['mail']);
        $this->middleware('permission:leave-management.approvals.index|leave-management.approve')->only(['approvals']);
    }
    private function resolveAcademicUnit(string $managementUnit, int $fallbackUnitId=0): int
    {
        $normalize=static fn(string $value): string => trim((string) preg_replace('/\s+/', ' ', Str::ascii(mb_strtolower($value,'UTF-8'))));
        $source=$normalize($managementUnit);
        $units=\Modules\Unit\Models\Unit::query()->where('status','active')->get();
        preg_match('/tieu\s*doan\s*([a-z0-9]+)/i',$source,$parentMatch);
        preg_match('/dai\s*doi\s*([a-z0-9]+)/i',$source,$childMatch);
        $parentNumber=$parentMatch[1]??null;
        $childNumber=$childMatch[1]??null;
        $parent=$parentNumber?$units->first(fn($unit)=>$normalize((string)$unit->name)==='tieu doan '.$parentNumber||strtolower((string)$unit->code)==='d'.$parentNumber):null;
        if($parent&&$childNumber){
            $child=$units->first(fn($unit)=>(int)$unit->parent_id===(int)$parent->id&&$normalize((string)$unit->name)==='dai doi '.$childNumber);
            if(!$child){
                $child=\Modules\Unit\Models\Unit::firstOrCreate(['parent_id'=>$parent->id,'name'=>'Đại đội '.$childNumber],['code'=>'D'.$parentNumber.'-DD'.$childNumber,'level'=>((int)$parent->level)+1,'status'=>'active','created_by'=>auth()->id(),'updated_by'=>auth()->id()]);
            }
            return (int)$child->id;
        }
        $exact=$units->first(fn($unit)=>$normalize((string)$unit->name)===$source);
        return (int)($exact?->id??$fallbackUnitId);
    }

    private function syncAcademicClasses(): void
    {
        $hasSourceLink=Schema::hasColumn('leave_classes','source_class_id');
        $academicClasses=ClassModel::query()->where('is_active',true)->get(['id','name','management_unit']);
        $syncedSourceIds=[];
        foreach($academicClasses as $academicClass){
            $unitId=$this->resolveAcademicUnit((string)$academicClass->management_unit);
            if(!$unitId)$unitId=\App\Models\User::query()->where('class_id',$academicClass->id)->whereNotNull('unit_id')->value('unit_id');
            if(!$unitId)continue;
            $syncedSourceIds[]=$academicClass->id;
            $leaveClass=$hasSourceLink
                ? LeaveClass::withoutGlobalScopes()->updateOrCreate(['source_class_id'=>$academicClass->id],['unit_id'=>$unitId,'name'=>$academicClass->name,'active'=>true])
                : LeaveClass::withoutGlobalScopes()->firstOrCreate(['unit_id'=>$unitId,'name'=>$academicClass->name],['active'=>true]);
            // Lớp học dùng users.class_id, còn danh sách lớp phép dùng
            // leave_personnel.class_id. Trước đây chỉ update các bản ghi
            // leave_personnel đã tồn tại nên học viên mới luôn bị đếm là 0.
            $students=\App\Models\User::query()
                ->where('class_id',$academicClass->id)
                ->where('user_type','student')
                ->where('status',1)
                ->get(['id','name','code','email','unit_id']);
            foreach($students as $student){
                LeavePersonnel::withoutGlobalScopes()->updateOrCreate(
                    ['user_id'=>$student->id],
                    [
                        'class_id'=>$leaveClass->id,
                        'class_name'=>$leaveClass->name,
                        'unit_id'=>$unitId,
                        'unit'=>$academicClass->management_unit,
                        'staff_code'=>$student->code,
                        'name'=>$student->name,
                        'email'=>$student->email,
                        'active'=>true,
                    ]
                );
            }
        }
        if($hasSourceLink&&$syncedSourceIds){LeaveClass::withoutGlobalScopes()->whereNotIn('source_class_id',$syncedSourceIds)->update(['active'=>false]);}
    }
    public function syncClasses(){ $this->syncAcademicClasses(); return back()->with('success','Đã đồng bộ lớp và học viên từ dữ liệu đào tạo.'); }
    public function personnel(){ $users=\App\Models\User::where('status',1)->orderBy('name')->get(); $positions=Position::active()->orderBy('name')->get(); if($positions->isEmpty())$positions=LeavePosition::orderBy('sort_order')->orderBy('name')->get(); return view('leave-management::feature',['section'=>'personnel','title'=>'Quân nhân / nhân sự','items'=>LeavePersonnel::with(['user','unitRelation.parent.parent.parent','commander'])->where('active',true)->orderBy('name')->get(),'users'=>$users,'units'=>\Modules\Unit\Models\Unit::active()->with('parent.parent.parent')->orderBy('level')->orderBy('name')->get(),'objects'=>LeaveObjectType::where('active',true)->orderBy('sort_order')->get(),'positions'=>$positions]);}
    public function syncPersonnelPositions(Request $request){$count=0;$personnel=LeavePersonnel::with('user.position')->whereNotNull('user_id')->where('active',true)->get();foreach($personnel as $person){$account=$person->user;$positionId=$account?->position_id;$positionName=$account?->position?->name;if((int)$person->position_id!==(int)$positionId||$person->position!==$positionName){$person->update(['position_id'=>$positionId,'position'=>$positionName]);$count++;}}return back()->with('success',"Đã đồng bộ chức vụ cho {$count} hồ sơ quân nhân từ Dashboard.");}
     public function personnelUpdate(Request $request, LeavePersonnel $personnel){$data=$request->validate(['user_id'=>'nullable|exists:users,id','staff_code'=>'nullable|string|max:80','name'=>'nullable|string|max:255','position'=>'nullable|string|max:255','object_type'=>'nullable|string|max:50','rank'=>'nullable|string|max:80','unit'=>'nullable|string|max:255','unit_id'=>'nullable|exists:units,id','email'=>'nullable|email|max:255','gmail'=>'nullable|email|max:255','enlistment_date'=>'nullable|date','hometown'=>'nullable|string|max:255','permanent_residence'=>'nullable|string|max:255','commander_name'=>'nullable|string|max:255','commander_user_id'=>'nullable|exists:users,id']);$preserve=['name','staff_code','position','object_type','rank','unit','unit_id','email','gmail','enlistment_date','hometown','permanent_residence','commander_name','commander_user_id'];foreach($preserve as $field){if(array_key_exists($field,$data)&&($data[$field]===null||$data[$field]===''))$data[$field]=$personnel->{$field};}if(!trim((string)($data['name']??'')))$data['name']=$personnel->name;if(!empty($data['unit_id']))$data['unit']=\Modules\Unit\Models\Unit::find($data['unit_id'])?->name ?: ($data['unit']??$personnel->unit);$request->merge($data);$personnel->update($data);LeaveAuditLog::create(['user_id'=>$request->user()->id,'action'=>'UPDATE','entity_type'=>'personnel','entity_id'=>$personnel->id,'details'=>$data]);return back()->with('success','Đã cập nhật nhân sự và giữ nguyên các thông tin không thay đổi.');}
    public function personnelDelete(LeavePersonnel $personnel){if($personnel->requests()->exists())return back()->withErrors(['personnel'=>'Không thể xóa nhân sự đã có đơn phép.']);$personnel->delete();return back()->with('success','Đã xóa nhân sự.');}
    public function units(){return view('leave-management::feature',['section'=>'units','title'=>'Đơn vị quản lý phép','items'=>\Modules\Unit\Models\Unit::active()->withCount('instructors')->orderBy('name')->get()]);}
    public function requests(){
        $user=request()->user();
        $personnel=LeaveAccess::personnel(LeavePersonnel::where('active',true),$user)->with(['unitRelation','leaveClass'])->orderBy('name')->get();
        $classes=LeaveClass::with(['unit.parent','personnel'=>fn($q)=>$q->where('active',true)->orderBy('name')])->where('active',true)->when(LeaveAccess::isScoped($user),fn($q)=>$q->whereIn('unit_id',LeaveAccess::unitIds($user)))->orderBy('name')->get();
        $localities=LeaveLocality::with('parent')->orderBy('level')->orderBy('name')->get();
         $linkedMilitaryPersonnel=LeaveAccess::personnelForUser($user);
        $canProposeForUnit=$user->isSuperAdmin()||$user->can('leave-management.create')||$user->can('leave-management.requests.create')||$user->can('leave-management.approvals.approve')||$user->can('leave-management.approve');
        $isMilitaryAccount=!$canProposeForUnit&&($user->hasRole(\App\Support\RoleCatalog::LEAVE_MILITARY)||(bool)$linkedMilitaryPersonnel);
        $militaryPersonnel=$isMilitaryAccount ? $linkedMilitaryPersonnel : $personnel->firstWhere('user_id',$user->id);
        if($isMilitaryAccount && $militaryPersonnel && !$personnel->contains('id',$militaryPersonnel->id))$personnel->push($militaryPersonnel);
         $militaryServiceYears=$militaryPersonnel?->enlistment_date ? max(0, now()->year - \Carbon\Carbon::parse($militaryPersonnel->enlistment_date)->year) : 0;
        $regulationMatches=function($query,$type)use($militaryPersonnel,$militaryServiceYears){return $query->where('leave_type',$type)->where('active',true)->when($militaryPersonnel,fn($q)=>$q->where(function($x)use($militaryPersonnel){$x->whereNull('object_type')->orWhere('object_type',$militaryPersonnel->object_type);})->where(function($x)use($militaryServiceYears){$x->whereNull('min_years')->orWhere('min_years','<=',$militaryServiceYears);})->where(function($x)use($militaryServiceYears){$x->whereNull('max_years')->orWhere('max_years','>=',$militaryServiceYears);}));};
         $annualRules=$regulationMatches(LeaveRegulation::query(),'ANNUAL');
         if($militaryPersonnel)$annualRules->orderByRaw('CASE WHEN object_type = ? THEN 0 ELSE 1 END',[$militaryPersonnel->object_type]);
         $militaryAnnualDays=(int)($annualRules->orderByDesc('min_years')->value('base_days')??0);
        $extraStandards=$regulationMatches(LeaveRegulation::query(),'EXTRA')->orderBy('sort_order')->orderBy('id')->get()->map(function($rule){$rule->days=$rule->base_days;$rule->label=$rule->description?:$rule->label;return $rule;});
        $itemsQuery=LeaveAccess::requests(LeaveRequest::with('personnel'),$user);
        if($isMilitaryAccount)$itemsQuery->whereHas('personnel',fn($q)=>$q->where('user_id',$user->id));
        return view('leave-management::feature',['section'=>'requests','title'=>'Đề xuất nghỉ phép','items'=>$itemsQuery->latest()->get(),'personnel'=>$personnel,'replacementPersonnel'=>$personnel,'classes'=>$classes,'localities'=>$localities,'extraStandards'=>$extraStandards,'regulations'=>LeaveRegulation::where('active',true)->get(),'militaryPersonnel'=>$militaryPersonnel,'militaryServiceYears'=>$militaryServiceYears,'militaryAnnualDays'=>$militaryAnnualDays,'isMilitaryAccount'=>$isMilitaryAccount,'canProposeForUnit'=>$canProposeForUnit]);
    }
    public function requestDetail(LeaveRequest $leaveRequest){
        $user=request()->user();
        $personnel=LeaveAccess::personnel(LeavePersonnel::where('active',true),$user)->with('unitRelation')->orderBy('name')->get();
        return view('leave-management::feature',['section'=>'request-detail','title'=>'Chi tiết đơn phép #'.$leaveRequest->id,'request'=>$leaveRequest->load(['personnel','commander','replacement']),'personnel'=>$personnel,'replacementPersonnel'=>$personnel,'localities'=>LeaveLocality::with('parent')->orderBy('level')->orderBy('name')->get(),'extraStandards'=>LeaveRegulation::where('leave_type','EXTRA')->where('active',true)->orderBy('sort_order')->orderBy('id')->get(),'audit'=>LeaveAuditLog::where('entity_type','request')->where('entity_id',$leaveRequest->id)->with('user')->latest()->get()]);
    }
    public function printRequest(LeaveRequest $leaveRequest){
        $httpRequest=request();
        $user=request()->user();
        abort_unless(PermissionCheck::isLeaveAgency($user) || LeaveAccess::canApprove($user) || LeaveAccess::canHeadSign($user),403,'Chỉ tài khoản Cơ quan cán bộ, Quân lực hoặc thủ trưởng được in giấy nghỉ phép.');
        $leaveRequest->forceFill(['printed_at'=>now()])->save();
        LeaveAuditLog::create(['user_id'=>$user->id,'action'=>'PRINT','entity_type'=>'request','entity_id'=>$leaveRequest->id,'details'=>['printed_at'=>now()->toDateTimeString()]]);
        $leaveRequest->load(['personnel.unitRelation.parent.parent.parent','commander']);
        $printLocalityPath=$leaveRequest->locality_id ? (LeaveLocality::with('parent')->find($leaveRequest->locality_id)?->pathName() ?: $leaveRequest->locality_path) : $leaveRequest->locality_path;
        $unit=$leaveRequest->personnel?->unitRelation ?: ($leaveRequest->unit_id ? Unit::with('parent.parent.parent')->find($leaveRequest->unit_id) : null);
        $printUnitPath=$unit?->leafFirstHierarchyPath() ?: ($leaveRequest->unit_name ?? $leaveRequest->personnel?->unit ?? null);
        if(in_array($httpRequest->query('format'),['word','pdf','print'],true)){
            $template=$this->activePermitTemplate();
            if($template)return match($httpRequest->query('format')){
                'pdf'=>$this->printPermitPdfFromTemplate($template,$leaveRequest,$printLocalityPath,$printUnitPath),
                'print'=>$this->showPermitPdfPrintPage($template,$leaveRequest,$printLocalityPath,$printUnitPath),
                default=>$this->downloadPermitFromTemplate($template,$leaveRequest,$printLocalityPath,$printUnitPath),
            };
        }
        return view('leave-management::print-request',['request'=>$leaveRequest,'printLocalityPath'=>$printLocalityPath,'printUnitPath'=>$printUnitPath]);
    }
    public function printRecord(Request $request, LeaveRecord $record){
        $user=$request->user();
        abort_unless(PermissionCheck::isLeaveAgency($user) || LeaveAccess::canApprove($user) || LeaveAccess::canHeadSign($user),403,'Chỉ tài khoản Cơ quan cán bộ, Quân lực hoặc thủ trưởng được in giấy nghỉ phép.');
        $record->loadMissing(['personnel.unitRelation']);
        $this->ensureRecordAccess($record,$user);
        $source=$record->request_id?LeaveRequest::withoutGlobalScopes()->with(['personnel.unitRelation','replacement'])->find($record->request_id):null;
        if($source)$source->forceFill(['printed_at'=>now()])->save();
        LeaveAuditLog::create(['user_id'=>$user->id,'action'=>'PRINT','entity_type'=>'record','entity_id'=>$record->id,'details'=>['request_id'=>$record->request_id,'printed_at'=>now()->toDateTimeString()]]);
        $printLocalityPath=$record->locality_id ? (LeaveLocality::with('parent')->find($record->locality_id)?->pathName() ?: $record->locality_path) : $record->locality_path;
        $unit=$record->unit_id?\Modules\Unit\Models\Unit::find($record->unit_id):null;
        $printUnitPath=$unit?->leafFirstHierarchyPath() ?: ($record->unit_name ?? $record->personnel?->unitRelation?->name ?? $record->personnel?->unit ?? null);
        $leaveRequest=new LeaveRequest();
        if($source)$leaveRequest->forceFill($source->getAttributes());
        $leaveRequest->forceFill([
            'id'=>$source?->id ?: ('HS'.$record->id),
            'personnel_id'=>$record->personnel_id ?: $source?->personnel_id,
            'personnel_code'=>$record->personnel_code ?: $source?->personnel_code,
            'personnel_name'=>$record->personnel_name ?: $source?->personnel_name,
            'from_date'=>$record->start_date ?: $source?->from_date,
            'to_date'=>$record->end_date ?: $source?->to_date,
            'leave_type'=>$record->leave_type ?: $source?->leave_type,
            'reason'=>$record->note ?: $source?->reason,
            'note'=>$record->note ?: $source?->note,
            'status'=>$record->status ?: $source?->status,
            'object_type'=>$record->object_type ?: $source?->object_type,
            'rank'=>$record->rank ?: $source?->rank,
            'position'=>$record->position ?: $source?->position,
            'enlistment_date'=>$record->enlistment_date ?: $source?->enlistment_date,
            'unit_id'=>$record->unit_id ?: $source?->unit_id,
            'unit_name'=>$record->unit_name ?: $source?->unit_name,
            'service_years'=>$record->service_years ?: $source?->service_years,
            'base_days'=>$record->base_days ?: $source?->base_days,
            'travel_days'=>$record->travel_days ?: $source?->travel_days,
            'extra_days'=>$record->extra_days ?: $source?->extra_days,
            'extra_reasons'=>$record->extra_reasons ?: $source?->extra_reasons,
            'total_days'=>$record->total_days ?: $source?->total_days,
            'leave_year'=>$record->leave_year ?: $source?->leave_year,
            'locality_id'=>$record->locality_id ?: $source?->locality_id,
            'locality_path'=>$record->locality_path ?: $source?->locality_path,
            'replacement_personnel_id'=>$record->replacement_personnel_id ?: $source?->replacement_personnel_id,
            'replacement_personnel_name'=>$record->replacement_personnel_name ?: $source?->replacement_personnel_name,
            'replacement_position'=>$record->replacement_position ?: $source?->replacement_position,
            'proposed_by_user_id'=>$record->proposed_by_user_id ?: $source?->proposed_by_user_id,
            'proposed_by_username'=>$record->proposed_by_username ?: $source?->proposed_by_username,
            'proposed_by_display_name'=>$record->proposed_by_display_name ?: $source?->proposed_by_display_name,
            'decided_by_user_id'=>$record->decided_by_user_id ?: $source?->decided_by_user_id,
            'decided_by_username'=>$record->decided_by_username ?: $source?->decided_by_username,
            'decided_at'=>$record->decided_at ?: $source?->decided_at,
            'approved_at'=>$source?->approved_at ?: $record->decided_at,
            'created_at'=>$source?->created_at ?: $record->created_at,
        ]);
        $leaveRequest->setRelation('personnel',$source?->personnel ?: $record->personnel);
        if($source?->relationLoaded('replacement'))$leaveRequest->setRelation('replacement',$source->replacement);
        if(in_array($request->query('format'),['word','pdf','print'],true)){
            $template=$this->activePermitTemplate();
            if($template)return match($request->query('format')){
                'pdf'=>$this->printPermitPdfFromTemplate($template,$leaveRequest,$printLocalityPath,$printUnitPath),
                'print'=>$this->showPermitPdfPrintPage($template,$leaveRequest,$printLocalityPath,$printUnitPath),
                default=>$this->downloadPermitFromTemplate($template,$leaveRequest,$printLocalityPath,$printUnitPath),
            };
        }
        return view('leave-management::print-request',['request'=>$leaveRequest,'printLocalityPath'=>$printLocalityPath,'printUnitPath'=>$printUnitPath]);
    }
    public function approvals(){
        $user=request()->user();
         $agency=LeaveAccess::agencyForUser($user); $isUnitManager=LeaveAccess::isCommanderAccount($user); $isHeadSigner=LeaveAccess::canHeadSign($user); $canApprove=PermissionCheck::can($user,'leave-management.approvals.approve')||PermissionCheck::can($user,'leave-management.approve'); $commandUnitIds=LeaveAccess::commandUnitIds($user); $items=LeaveRequest::with(['personnel','leaveClass'])->where(function($q)use($user,$agency,$isHeadSigner,$canApprove,$commandUnitIds){if($user->isSuperAdmin()){$q->whereIn('status',['PENDING_COMMANDER','PENDING_AGENCY','PENDING_HEAD']);return;}$q->where(fn($x)=>$x->where('status','PENDING_COMMANDER')->where(function($y)use($user,$commandUnitIds){$y->where('commander_user_id',$user->id);if($commandUnitIds)$y->orWhereIn('unit_id',$commandUnitIds)->orWhereHas('personnel',fn($p)=>$p->whereIn('unit_id',$commandUnitIds));}))->orWhere(fn($x)=>$x->where('status','PENDING_AGENCY')->when(in_array($agency,[LeaveAccess::QUAN_LUC,LeaveAccess::CO_QUAN_CAN_BO],true),fn($y)=>$y->where('managing_agency',$agency),fn($y)=>$canApprove?$y:$y->whereRaw('1=0')));if($isHeadSigner)$q->orWhere('status','PENDING_HEAD');})->when(!$user->isSuperAdmin()&&!$isUnitManager&&!$isHeadSigner&&!$canApprove,fn($q)=>$q->whereRaw('1=0'))->latest()->get();
        return view('leave-management::feature',['section'=>'approvals','title'=>'Duyệt nghỉ phép','items'=>$items]);
    }
    public function regulations(){return view('leave-management::feature',['section'=>'regulations','title'=>'Quy định phép','items'=>LeaveRegulation::latest()->get(),'objects'=>LeaveObjectType::where('active',true)->orderBy('sort_order')->get()]);}
    public function regulationStore(Request $r){$d=$r->validate(['leave_type'=>'required|string|max:50','object_type'=>'nullable|string|max:50','min_years'=>'nullable|integer|min:0','max_years'=>'nullable|integer|min:0','base_days'=>'required|integer|min:0','label'=>'nullable|string|max:255','description'=>'nullable|string']);LeaveRegulation::create($d);return back()->with('success','Đã thêm quy định phép.');}
    public function regulationUpdate(Request $r, LeaveRegulation $regulation){$regulation->update($r->validate(['leave_type'=>'required|string|max:50','object_type'=>'nullable|string|max:50','min_years'=>'nullable|integer|min:0','max_years'=>'nullable|integer|min:0','base_days'=>'required|integer|min:0','label'=>'nullable|string|max:255','description'=>'nullable|string']));return back()->with('success','Đã cập nhật quy định.');}
    public function regulationDelete(LeaveRegulation $regulation){$regulation->delete();return back()->with('success','Đã xóa quy định.');}
    public function objectTypeStore(Request $r){$d=$r->validate(['code'=>'required|string|max:50|unique:leave_object_types,code','name'=>'required|string|max:255','sort_order'=>'nullable|integer|min:0','active'=>'nullable|boolean']);$d['code']=strtoupper(trim($d['code']));$d['active']=$r->boolean('active',true);LeaveObjectType::create($d);return back()->with('success','Đã thêm đối tượng phép.');}
    public function objectTypeUpdate(Request $r, LeaveObjectType $object){$d=$r->validate(['code'=>['required','string','max:50',\Illuminate\Validation\Rule::unique('leave_object_types','code')->ignore($object->id)],'name'=>'required|string|max:255','sort_order'=>'nullable|integer|min:0','active'=>'nullable|boolean']);$d['code']=strtoupper(trim($d['code']));$d['active']=$r->boolean('active',false);$object->update($d);return back()->with('success','Đã cập nhật đối tượng phép.');}
    public function objectTypeDelete(LeaveObjectType $object){$object->delete();return back()->with('success','Đã xóa đối tượng phép.');}
    public function localities(){return view('leave-management::feature',['section'=>'localities','title'=>'Địa phương','items'=>LeaveLocality::with('parent')->latest()->get()]);}
    public function localityStore(Request $r){LeaveLocality::create($r->validate(['name'=>'required|string|max:255','level'=>'required|string|max:30','parent_id'=>'nullable|exists:leave_localities,id','code'=>'nullable|string|max:50']));return back()->with('success','Đã thêm địa phương.');}
    public function localityUpdate(Request $r, LeaveLocality $locality){$locality->update($r->validate(['name'=>'required|string|max:255','level'=>'required|string|max:30','parent_id'=>'nullable|exists:leave_localities,id','code'=>'nullable|string|max:50']));return back()->with('success','Đã cập nhật địa phương.');}
    public function localityDelete(LeaveLocality $locality){if($locality->children()->exists())return back()->withErrors(['locality'=>'Không thể xóa địa phương còn cấp con.']);$locality->delete();return back()->with('success','Đã xóa địa phương.');}
    public function localityReset(){\DB::transaction(function(){LeaveLocality::query()->update(['parent_id'=>null]);LeaveLocality::query()->delete();});return back()->with('success','Đã xóa toàn bộ danh mục địa phương.');}
    public function localityImport(Request $r){
        $r->validate(['file'=>'required|file|mimes:xlsx,xls,csv,txt|max:20480']);
        $rows=\PhpOffice\PhpSpreadsheet\IOFactory::load($r->file('file')->getRealPath())->getActiveSheet()->toArray(null,true,true,true);
        if(!$rows)return back()->withErrors(['file'=>'File địa phương không có dữ liệu.']);
        $normalize=function($v){
            $value=mb_strtolower(trim((string)$v),'UTF-8');
            $value=preg_replace('/^\xEF\xBB\xBF/u','',$value);
            $value=preg_replace('/[^\p{L}\p{N}]+/u','_',$value);
            return trim(preg_replace('/_+/','_',$value),'_');
        };
        $headerRow=null;$headerIndex=null;
        foreach($rows as $rowIndex=>$candidate){$candidateHeaders=array_map($normalize,$candidate);if(in_array('tên_tỉnh_tp_mới',$candidateHeaders,true)||in_array('tên_tỉnh_tp',$candidateHeaders,true)||in_array('tên_phường_xã_mới',$candidateHeaders,true)||in_array('tên_phường_xã',$candidateHeaders,true)||in_array('ten_tinh_tp_moi',$candidateHeaders,true)||in_array('ten_tinh_tp',$candidateHeaders,true)||in_array('ten_phuong_xa_moi',$candidateHeaders,true)||in_array('ten_phuong_xa',$candidateHeaders,true)||in_array('name',$candidateHeaders,true)){$headerRow=$candidateHeaders;$headerIndex=$rowIndex;break;}}
        if($headerRow===null)return back()->withErrors(['file'=>'Không tìm thấy dòng tiêu đề dữ liệu trong file địa phương.']);
        $headers=$headerRow;$rows=array_slice($rows,$headerIndex+1);
        $aliases=[
            'name'=>['name','ten','ten_dia_phuong','dia_phuong','locality','locality_name','ten_phuong_xa_moi','ten_phuong_xa','ten_xa_moi','tên_phường_xã_mới','tên_phường_xã'],
            'level'=>['level','cap','cap_do','cap_hanh_chinh','loai','cấp','cấp_độ'],
            'code'=>['code','ma','ma_dia_phuong','ma_phuong_xa_moi','ma_phuong_xa','ma_xa','mã','mã_phường_xã_mới','mã_phường_xã'],
            'parent_code'=>['parent_code','ma_cha','ma_cap_tren','ma_tinh_cha','ma_huyen_cha','ma_tinh_tms','mã_tỉnh_tms'],
            'province_name'=>['province_name','ten_tinh','ten_tinh_tp_moi','ten_tinh_tp','tinh_tp','tên_tỉnh_tp_mới','tên_tỉnh_tp'],
            'province_code'=>['province_code','ma_tinh','ma_tinh_tms','ma_tinh_moi','mã_tỉnh_tms','mã_tỉnh'],
        ];
        $index=[];foreach($aliases as $field=>$names){foreach($names as $name){$key=array_search($name,$headers,true);if($key!==false){$index[$field]=$key;break;}}}
        if(!isset($index['name']))return back()->withErrors(['file'=>'Không tìm thấy cột tên địa phương. Hãy dùng cột: Tên địa phương hoặc name.']);
        $created=0;
        foreach($rows as $row){
            $name=trim((string)($row[$index['name']]??''));
            $provinceName=isset($index['province_name'])?trim((string)($row[$index['province_name']]??'')):'';
            $provinceCode=isset($index['province_code'])?trim((string)($row[$index['province_code']]??'')):'';
            $code=isset($index['code'])?trim((string)($row[$index['code']]??'')):'';
            $level=isset($index['level'])?trim((string)($row[$index['level']]??'')):'';
            $parentCode=isset($index['parent_code'])?trim((string)($row[$index['parent_code']]??'')):'';
            if($name!=='' && $provinceName!=='' && mb_strtolower($name,'UTF-8')===mb_strtolower($provinceName,'UTF-8'))continue;
            // Chỉ nhận bản ghi có đủ tỉnh/thành; bỏ qua dòng thiếu tỉnh hoặc mã tỉnh.
            if($provinceName==='' || $provinceCode==='')continue;
            if($provinceName!==''){
                $province= $provinceCode!=='' ? LeaveLocality::updateOrCreate(['code'=>$provinceCode],['name'=>$provinceName,'level'=>'PROVINCE','parent_id'=>null]) : LeaveLocality::firstOrCreate(['name'=>$provinceName,'level'=>'PROVINCE','parent_id'=>null]);
                if($parentCode==='')$parentCode=$provinceCode;
            }
            $parent=$parentCode!==''?LeaveLocality::where('code',$parentCode)->first():null;
            if($name==='')continue;
            $data=['name'=>$name,'level'=>$level!==''?$level:'WARD','parent_id'=>$parent?->id];
            if($code!=='')LeaveLocality::updateOrCreate(['code'=>$code],$data+['code'=>$code]);else LeaveLocality::create($data);
            $created++;
        }
        return back()->with('success',"Đã import {$created} địa phương.");
    }
    public function positions(){return view('leave-management::feature',['section'=>'positions','title'=>'Chức vụ','items'=>LeavePosition::orderBy('sort_order')->get()]);}
    public function positionStore(Request $r){LeavePosition::create($r->validate(['name'=>'required|string|max:255','sort_order'=>'nullable|integer|min:0']));return back()->with('success','Đã thêm chức vụ.');}
    public function positionUpdate(Request $r, LeavePosition $position){$position->update($r->validate(['name'=>'required|string|max:255','sort_order'=>'nullable|integer|min:0']));return back()->with('success','Đã cập nhật chức vụ.');}
    public function positionDelete(LeavePosition $position){$position->delete();return back()->with('success','Đã xóa chức vụ.');}
    public function batches(){
        $today=now()->startOfDay();
        $items=LeaveBatch::with('request.personnel')->latest()->get()->map(function($item)use($today){
            $start=$item->start_date?->copy()->startOfDay();
            $end=$item->end_date?->copy()->startOfDay();
            $total=(int)$item->total_days;
            $used=0;
            if($start&&!$today->lt($start))$used=$end&&!$today->lt($end)?$total:min($total,$start->diffInDays($today)+1);
            $item->days_used=max(0,$used);
            $item->days_remaining=max(0,$total-$item->days_used);
            $item->leave_progress=$item->days_used<=0?'Chưa nghỉ':($item->days_remaining>0?'Đang nghỉ':'Đã nghỉ hết');
            return $item;
        });
        return view('leave-management::feature',['section'=>'batches','title'=>'Đợt nghỉ','items'=>$items,'requests'=>LeaveRequest::where('status','APPROVED')->latest()->get()]);
    }
    public function batchStore(Request $r){$d=$r->validate(['request_id'=>'required|exists:leave_requests,id','label'=>'required|string|max:255','start_date'=>'required|date','end_date'=>'required|date|after_or_equal:start_date','total_days'=>'required|integer|min:1','note'=>'nullable|string']);LeaveBatch::create($d+['created_by'=>$r->user()->id]);return back()->with('success','Đã tạo đợt nghỉ.');}
    public function batchUpdate(Request $r, LeaveBatch $batch){$batch->update($r->validate(['label'=>'required|string|max:255','start_date'=>'required|date','end_date'=>'required|date|after_or_equal:start_date','total_days'=>'required|integer|min:1','note'=>'nullable|string']));return back()->with('success','Đã cập nhật đợt nghỉ.');}
    public function batchDelete(LeaveBatch $batch){$batch->delete();return back()->with('success','Đã xóa đợt nghỉ.');}
    public function records(Request $request){
        $user=$request->user();
        $year=(int)($request->input('year')?:now()->year);
        $unitId=$request->input('unit_id')?(int)$request->input('unit_id'):null;
        $selectedUnitIds=$unitId?ManagerUnitScope::unitAndDescendantIds($unitId):[];
        $selectedUnitNames=$selectedUnitIds?Unit::whereIn('id',$selectedUnitIds)->pluck('name')->filter()->values()->all():[];
        $keyword=trim((string)$request->input('q',''));
        $scopedUnitIds=LeaveAccess::isScoped($user)?LeaveAccess::unitIds($user):[];
        if($unitId&&$scopedUnitIds)abort_unless(in_array($unitId,$scopedUnitIds,true),403);
        if($selectedUnitIds&&$scopedUnitIds)$selectedUnitIds=array_values(array_intersect($selectedUnitIds,$scopedUnitIds));
        $scopeFilter=function($q)use($scopedUnitIds){if($scopedUnitIds)$q->where(function($x)use($scopedUnitIds){$x->whereIn('unit_id',$scopedUnitIds)->orWhereHas('personnel',fn($p)=>$p->whereIn('unit_id',$scopedUnitIds));});};
        $unitFilter=function($q)use($unitId,$selectedUnitIds,$selectedUnitNames){if($unitId)$q->where(function($x)use($selectedUnitIds,$selectedUnitNames){$x->whereIn('unit_id',$selectedUnitIds);if($selectedUnitNames)$x->orWhereIn('unit_name',$selectedUnitNames);$x->orWhereHas('personnel',fn($p)=>$p->whereIn('unit_id',$selectedUnitIds)->when($selectedUnitNames,fn($p)=>$p->orWhereIn('unit',$selectedUnitNames)));});};
        $nameFilter=function($q)use($keyword){if($keyword!=='')$q->where(function($x)use($keyword){$x->where('personnel_name','like','%'.$keyword.'%')->orWhere('personnel_code','like','%'.$keyword.'%')->orWhereHas('personnel',fn($p)=>$p->where('name','like','%'.$keyword.'%')->orWhere('staff_code','like','%'.$keyword.'%'));});};
        $units=Unit::active()->with('parent.parent.parent')->when(LeaveAccess::isScoped($user),fn($q)=>$q->whereIn('id',LeaveAccess::unitIds($user)))->orderBy('level')->orderBy('name')->get();
        $items=LeaveRecord::with(['request.personnel','decidedBy','personnel.unitRelation'])
            ->when($year,fn($q)=>$q->where(function($x)use($year){$x->where('leave_year',$year)->orWhereYear('start_date',$year);}))
            ->when($scopedUnitIds,$scopeFilter)
            ->when($unitId,$unitFilter)
            ->when($keyword,$nameFilter)
            ->latest()
            ->get();
        return view('leave-management::feature',['section'=>'records','title'=>'Hồ sơ phép','items'=>$items,'units'=>$units,'year'=>$year,'recordKeyword'=>$keyword,'recordUnitId'=>$unitId]);
    }
    public function auditLogs(){return view('leave-management::feature',['section'=>'audit','title'=>'Nhật ký quản lý phép','items'=>LeaveAuditLog::with('user')->latest()->limit(200)->get()]);}
    public function alerts(Request $request){$alerts=LeaveAlert::with('request.personnel')->where('user_id',$request->user()->id)->latest()->paginate(30);return view('leave-management::feature',['section'=>'alerts','title'=>'Thông báo quản lý phép','items'=>$alerts]);}
    public function alertRead(Request $request, LeaveAlert $alert){abort_unless((int)$alert->user_id===(int)$request->user()->id,403);$now=now();$alert->update(['read_at'=>$now]);if(Schema::hasTable('system_notifications'))\App\Models\SystemNotification::where('user_id',$alert->user_id)->where('meta->leave_alert_id',$alert->id)->whereNull('read_at')->update(['read_at'=>$now]);return back();}
    public function archiveRecord(LeaveRecord $record){$record->update(['archived_at'=>now()]);return back()->with('success','Đã lưu trữ bản ghi phép.');}
    public function recordUpdate(Request $request, LeaveRecord $record){
        $this->ensureRecordAccess($record,$request->user());
        $data=$request->validate(['leave_type'=>'required|in:ANNUAL,EXTRA,SICK,PERSONAL,SHORT_LEAVE,SPECIAL,UNIT','start_date'=>'required|date','end_date'=>'required|date|after_or_equal:start_date','total_days'=>'required|integer|min:1','note'=>'nullable|string|max:2000','admin_note'=>'nullable|string|max:2000']);
        $data['leave_year']=(int)\Carbon\Carbon::parse($data['start_date'])->year;
        \DB::transaction(function()use($record,$data,$request):void{
            $record->update($data);
            if($record->request_id){
                LeaveRequest::whereKey($record->request_id)->update(['leave_type'=>$data['leave_type'],'from_date'=>$data['start_date'],'to_date'=>$data['end_date'],'total_days'=>$data['total_days'],'leave_year'=>$data['leave_year'],'note'=>$data['note']??null,'admin_note'=>$data['admin_note']??null]);
                LeaveBatch::where('request_id',$record->request_id)->update(['leave_type'=>$data['leave_type'],'start_date'=>$data['start_date'],'end_date'=>$data['end_date'],'total_days'=>$data['total_days'],'note'=>$data['note']??null]);
            }
            LeaveAuditLog::create(['user_id'=>$request->user()->id,'action'=>'RECORD_UPDATE','entity_type'=>'record','entity_id'=>$record->id,'details'=>$data]);
        });
        return back()->with('success','Đã cập nhật hồ sơ phép.');
    }
    public function recordDelete(Request $request, LeaveRecord $record){
        $this->ensureRecordAccess($record,$request->user());
        \DB::transaction(function()use($record,$request):void{
            if($record->request_id)LeaveBatch::where('request_id',$record->request_id)->delete();
            LeaveAuditLog::create(['user_id'=>$request->user()->id,'action'=>'RECORD_DELETE','entity_type'=>'record','entity_id'=>$record->id,'details'=>$record->toArray()]);
            $record->delete();
        });
        return back()->with('success','Đã xóa hồ sơ phép.');
    }
    private function ensureRecordAccess(LeaveRecord $record,$user):void{
        if(LeaveAccess::isScoped($user)){
            $unitIds=LeaveAccess::unitIds($user);
            $unitId=(int)($record->unit_id ?: $record->personnel?->unit_id);
            abort_unless($unitId&&in_array($unitId,$unitIds,true),403,'Bạn chỉ được xử lý hồ sơ phép thuộc đơn vị được phân công.');
        }
    }
    public function reports(Request $request){
        $year=(int)($request->input('year')?:now()->year);$today=now()->startOfDay();$agency=(string)$request->input('agency','');$unitId=$request->input('unit_id')?(int)$request->input('unit_id'):null;$selectedUnitIds=$unitId?ManagerUnitScope::unitAndDescendantIds($unitId):[];$selectedUnitNames=$selectedUnitIds?Unit::whereIn('id',$selectedUnitIds)->pluck('name')->filter()->values()->all():[];$keyword=trim((string)$request->input('q',''));
        $agencyFilter=function($q)use($agency){if(in_array($agency,[LeaveAccess::QUAN_LUC,LeaveAccess::CO_QUAN_CAN_BO],true))$q->where(function($x)use($agency){$x->where('managing_agency',$agency)->orWhereHas('personnel',fn($p)=>$p->where('managing_agency',$agency));});};
        $unitFilter=function($q)use($unitId,$selectedUnitIds,$selectedUnitNames){if($unitId)$q->where(function($x)use($selectedUnitIds,$selectedUnitNames){$x->whereIn('unit_id',$selectedUnitIds);if($selectedUnitNames)$x->orWhereIn('unit_name',$selectedUnitNames);$x->orWhereHas('personnel',fn($p)=>$p->whereIn('unit_id',$selectedUnitIds)->when($selectedUnitNames,fn($p)=>$p->orWhereIn('unit',$selectedUnitNames)));});};
        $personUnitFilter=function($q)use($unitId,$selectedUnitIds,$selectedUnitNames){if($unitId)$q->where(function($x)use($selectedUnitIds,$selectedUnitNames){$x->whereIn('unit_id',$selectedUnitIds);if($selectedUnitNames)$x->orWhereIn('unit',$selectedUnitNames);});};
        $nameFilter=function($q)use($keyword){if($keyword!=='')$q->where(function($x)use($keyword){$x->where('personnel_name','like','%'.$keyword.'%')->orWhere('personnel_code','like','%'.$keyword.'%')->orWhereHas('personnel',fn($p)=>$p->where('name','like','%'.$keyword.'%')->orWhere('staff_code','like','%'.$keyword.'%'));});};
        $personNameFilter=function($q)use($keyword){if($keyword!=='')$q->where(function($x)use($keyword){$x->where('name','like','%'.$keyword.'%')->orWhere('staff_code','like','%'.$keyword.'%');});};
        $approved=LeaveRequest::with(['personnel.unitRelation'])->where('status','APPROVED')->whereYear('from_date',$year)->when($agency,$agencyFilter)->when($unitId,$unitFilter)->when($keyword,$nameFilter)->latest()->get()->map(function($item)use($today){$start=$item->from_date?->copy()->startOfDay();$end=$item->to_date?->copy()->startOfDay();$item->days_used=($start&&!$today->lt($start))?min((int)$item->total_days,$end&&!$today->gte($end)?$start->diffInDays($today)+1:(int)$item->total_days):0;$item->days_remaining=$end&&!$today->gte($end)?$today->diffInDays($end):0;return $item;});
        $registered=LeaveRequest::with(['personnel.unitRelation'])->whereYear('from_date',$year)->whereNotIn('status',['REJECTED'])->when($agency,$agencyFilter)->when($unitId,$unitFilter)->when($keyword,$nameFilter)->latest()->get();
        $taken=$approved->filter(fn($item)=>$item->from_date&&!$today->lt($item->from_date->copy()->startOfDay()))->values();$usedPersonnel=$taken->pluck('personnel_id')->filter()->unique();
        $notYet=LeavePersonnel::with(['unitRelation','requests'])->where('active',true)->when(in_array($agency,[LeaveAccess::QUAN_LUC,LeaveAccess::CO_QUAN_CAN_BO],true),fn($q)=>$q->where('managing_agency',$agency))->when($unitId,$personUnitFilter)->when($keyword,$personNameFilter)->when($usedPersonnel->isNotEmpty(),fn($q)=>$q->whereNotIn('id',$usedPersonnel->all()))->orderBy('name')->get();
        $yearSummary=$approved->groupBy('personnel_id')->map(fn($rows)=>['personnel'=>$rows->first()->personnel,'days'=>$rows->sum('total_days'),'quota'=>$rows->max('base_days')]);
        $countBase=fn()=>LeaveRequest::query()->whereYear('from_date',$year)->when($agency,$agencyFilter)->when($unitId,$unitFilter)->when($keyword,$nameFilter);
        $currentReportType=(string)$request->input('report_type','');
        if(!in_array($currentReportType,['used','unused','tracking','registered'],true))$currentReportType='';
        $leaveNotifications=LeaveAlert::with('request')->where('user_id',$request->user()->id)->latest()->limit(50)->get();
        return view('leave-management::feature',['section'=>'reports','title'=>'Báo cáo phép','year'=>$year,'taken'=>$taken,'notYet'=>$notYet,'yearSummary'=>$yearSummary,'comparison'=>$approved,'registered'=>$registered,'leaveNotifications'=>$leaveNotifications,'pending'=>$countBase()->whereIn('status',['PENDING','PENDING_COMMANDER','PENDING_AGENCY','PENDING_HEAD','RETURNED'])->count(),'approved'=>$countBase()->where('status','APPROVED')->count(),'rejected'=>$countBase()->where('status','REJECTED')->count(),'days'=>$approved->sum('total_days'),'reportTemplates'=>LeaveReportTemplate::where('template_kind','report')->where('active',true)->orderBy('name')->get()]);
    }
    public function reportTemplates(){return view('leave-management::feature',['section'=>'report-templates','title'=>'Mẫu báo cáo phép','items'=>LeaveReportTemplate::latest()->get()]);}
    public function reportTemplateStore(Request $request){
        $data=$request->validate(['name'=>'required|string|max:255','template_kind'=>'nullable|in:report,permit','report_type'=>'nullable|required_if:template_kind,report|in:used,unused,tracking,registered','managing_agency'=>'nullable|required_if:template_kind,report|in:QUAN_LUC,CO_QUAN_CAN_BO','description'=>'nullable|string|max:2000','file'=>'required|file|mimes:docx|max:20480','active'=>'nullable|boolean']);
        $data['template_kind']=$data['template_kind']??'report';
        if($data['template_kind']==='permit'){$data['report_type']='permit';$data['managing_agency']='ALL';}
        $file=$request->file('file');$path=$file->store('leave-report-templates','local');$active=$request->boolean('active',true);
        \DB::transaction(function()use($data,$file,$path,$active,$request):void{if($active){$query=LeaveReportTemplate::where('template_kind',$data['template_kind']);if($data['template_kind']==='report')$query->where('report_type',$data['report_type'])->where('managing_agency',$data['managing_agency']);$query->update(['active'=>false]);}LeaveReportTemplate::create(['name'=>$data['name'],'template_kind'=>$data['template_kind'],'report_type'=>$data['report_type'],'managing_agency'=>$data['managing_agency'],'description'=>$data['description']??null,'disk'=>'local','file_path'=>$path,'original_name'=>$file->getClientOriginalName(),'mime'=>$file->getMimeType(),'file_size'=>$file->getSize(),'active'=>$active,'created_by'=>$request->user()->id,'updated_by'=>$request->user()->id]);});
        return back()->with('success',$data['template_kind']==='permit'?'Đã thêm mẫu in giấy phép nghỉ.':'Đã thêm mẫu báo cáo phép.');
    }
    public function reportTemplateUpdate(Request $request, LeaveReportTemplate $template){
        $data=$request->validate(['name'=>'required|string|max:255','template_kind'=>'nullable|in:report,permit','report_type'=>'nullable|required_if:template_kind,report|in:used,unused,tracking,registered','managing_agency'=>'nullable|required_if:template_kind,report|in:QUAN_LUC,CO_QUAN_CAN_BO','description'=>'nullable|string|max:2000','file'=>'nullable|file|mimes:docx|max:20480','active'=>'nullable|boolean']);
        $data['template_kind']=$data['template_kind']??($template->template_kind?:'report');
        if($data['template_kind']==='permit'){$data['report_type']='permit';$data['managing_agency']='ALL';}
        $active=$request->boolean('active',false);$payload=['name'=>$data['name'],'template_kind'=>$data['template_kind'],'report_type'=>$data['report_type'],'managing_agency'=>$data['managing_agency'],'description'=>$data['description']??null,'active'=>$active,'updated_by'=>$request->user()->id];$oldDisk=null;$oldPath=null;
        if($request->hasFile('file')){$file=$request->file('file');$oldDisk=$template->disk?:'local';$oldPath=$template->file_path;$payload+=['disk'=>'local','file_path'=>$file->store('leave-report-templates','local'),'original_name'=>$file->getClientOriginalName(),'mime'=>$file->getMimeType(),'file_size'=>$file->getSize()];}
        \DB::transaction(function()use($template,$payload,$active,$data,$oldDisk,$oldPath):void{if($active){$query=LeaveReportTemplate::where('template_kind',$data['template_kind'])->whereKeyNot($template->id);if($data['template_kind']==='report')$query->where('report_type',$data['report_type'])->where('managing_agency',$data['managing_agency']);$query->update(['active'=>false]);}$template->update($payload);if($oldPath)\Illuminate\Support\Facades\Storage::disk($oldDisk?:'local')->delete($oldPath);});
        return back()->with('success',$data['template_kind']==='permit'?'Đã cập nhật mẫu in giấy phép nghỉ.':'Đã cập nhật mẫu báo cáo phép.');
    }
    public function reportTemplateDelete(LeaveReportTemplate $template){$path=$template->file_path;$disk=$template->disk?:'local';$template->delete();if($path)\Illuminate\Support\Facades\Storage::disk($disk)->delete($path);return back()->with('success','Đã xóa mẫu báo cáo phép.');}
    public function reportTemplateDownload(LeaveReportTemplate $template){$path=$template->absolutePath();abort_unless($path&&is_file($path),404,'Không tìm thấy file mẫu.');return response()->download($path,$template->original_name?:('mau-bao-cao-'.$template->id.'.docx'));}
    private function activePermitTemplate():?LeaveReportTemplate{
        return LeaveReportTemplate::where('template_kind','permit')->where('active',true)->latest()->get()->first(fn($template)=>$template->absolutePath()&&is_file($template->absolutePath()));
    }
    private function buildPermitDocxFromTemplate(LeaveReportTemplate $template,LeaveRequest $leaveRequest,?string $printLocalityPath,?string $printUnitPath):string{
        $path=$template->absolutePath();abort_unless($path&&is_file($path),404,'Không tìm thấy file mẫu giấy phép.');
        $processor=new \PhpOffice\PhpWord\TemplateProcessor($path);
        $person=$leaveRequest->personnel;
        $from=$leaveRequest->from_date;$to=$leaveRequest->to_date;$created=$leaveRequest->created_at ?: now();
        $signer=$leaveRequest->bgh_signed_by_user_id ? \App\Models\User::find($leaveRequest->bgh_signed_by_user_id) : null;
        $reason=trim((string)($leaveRequest->reason??''));
        if($leaveRequest->leave_type==='ANNUAL')$reason=($reason?:'Nghỉ phép năm').' '.($leaveRequest->leave_year?:now()->year);
        elseif($reason==='')$reason='Nghỉ phép.';
        $signedDate=$leaveRequest->bgh_signed_at?->format('d/m/Y') ?: ($leaveRequest->approved_at?->format('d/m/Y') ?: '');
        $values=[
            'so_giay_phep'=>$leaveRequest->id.'/GNP-CDHC',
            'ma_don'=>(string)$leaveRequest->id,
            'ngay'=>now()->format('d'),
            'thang'=>now()->format('m'),
            'nam'=>now()->format('Y'),
            'ngay_lap'=>$created->format('d/m/Y'),
            'ngay_lap_ngay'=>$created->format('d'),
            'ngay_lap_thang'=>$created->format('m'),
            'ngay_lap_nam'=>$created->format('Y'),
            'ho_ten'=>mb_strtoupper((string)($leaveRequest->personnel_name?:$person?->name),'UTF-8'),
            'ho_ten_thuong'=>(string)($leaveRequest->personnel_name?:$person?->name),
            'ma_quan_nhan'=>(string)($leaveRequest->personnel_code?:$person?->staff_code),
            'cap_bac'=>(string)($leaveRequest->rank?:$person?->rank),
            'chuc_vu'=>(string)($leaveRequest->position?:$person?->position),
            'don_vi'=>(string)($printUnitPath?:$leaveRequest->unit_name?:$person?->unit),
            'tu_ngay'=>$from?$from->format('d/m/Y'):'',
            'den_ngay'=>$to?$to->format('d/m/Y'):'',
            'tu_gio'=>'07h00',
            'den_gio'=>'17h00',
            'tu_gio_so'=>'07',
            'den_gio_so'=>'17',
            'thoi_gian_nghi'=>$from&&$to?'07h00 ngày '.$from->format('d/m/Y').' đến 17h00 ngày '.$to->format('d/m/Y'):'',
            'tong_ngay'=>(string)$leaveRequest->total_days,
            'noi_nghi_phep'=>(string)($printLocalityPath?:$leaveRequest->locality_path),
            'ly_do'=>$reason,
            'loai_phep'=>(string)$leaveRequest->leave_type,
            'nguoi_thay_the'=>(string)($leaveRequest->replacement_personnel_name?:$leaveRequest->replacement?->name),
            'chuc_vu_thay_the'=>(string)($leaveRequest->replacement_position?:$leaveRequest->replacement?->position),
            'ghi_chu'=>(string)($leaveRequest->note??''),
            'y_kien_xu_ly'=>(string)($leaveRequest->decision_note??''),
            'so_ngay_van_ban_ky'=>(string)($leaveRequest->bgh_note??''),
            'ngay_ky'=>$signedDate,
            'nguoi_ky'=>(string)($signer?->name??$leaveRequest->decided_by_username??''),
            'thu_truong'=>(string)($signer?->name??''),
        ];
        foreach($values as $macro=>$value)$processor->setValue($macro,$value===''?' ':$value);
        $output=storage_path('app/giay-nghi-phep-'.$leaveRequest->id.'-'.now()->format('YmdHis').'.docx');
        $processor->saveAs($output);
        return $output;
    }
    private function downloadPermitFromTemplate(LeaveReportTemplate $template,LeaveRequest $leaveRequest,?string $printLocalityPath,?string $printUnitPath){
        $output=$this->buildPermitDocxFromTemplate($template,$leaveRequest,$printLocalityPath,$printUnitPath);
        return response()->download($output,'giay-nghi-phep-'.$leaveRequest->id.'.docx')->deleteFileAfterSend(true);
    }
    private function printPermitWordDirect(LeaveReportTemplate $template,LeaveRequest $leaveRequest,?string $printLocalityPath,?string $printUnitPath){
        $docx=$this->buildPermitDocxFromTemplate($template,$leaveRequest,$printLocalityPath,$printUnitPath);
        $script=storage_path('app/word-print-'.bin2hex(random_bytes(6)).'.ps1');
        file_put_contents($script, <<<'PS1'
param(
    [Parameter(Mandatory=$true)][string]$Docx
)
$ErrorActionPreference = 'Stop'
$word = $null
$doc = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $doc = $word.Documents.Open($Docx, $false, $true)
    $doc.PrintOut()
    Start-Sleep -Seconds 5
} finally {
    if ($doc) {
        $doc.Close($false)
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($doc) | Out-Null
    }
    if ($word) {
        $word.Quit()
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
    Remove-Item -LiteralPath $Docx -Force -ErrorAction SilentlyContinue
    Remove-Item -LiteralPath $PSCommandPath -Force -ErrorAction SilentlyContinue
}
PS1);
        $process=new \Symfony\Component\Process\Process([
            'C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe',
            '-NoProfile',
            '-ExecutionPolicy',
            'Bypass',
            '-File',
            $script,
            '-Docx',
            $docx,
        ],storage_path('app'));
        $process->setTimeout(10);
        try{$process->start();usleep(300000);}catch(\Throwable $e){\Log::error('Leave permit Word print launch crashed',['error'=>$e->getMessage()]);@unlink($script);@unlink($docx);abort(500,'Không gửi được lệnh in Word: '.$e->getMessage());}
        if(!$process->isRunning()&&!$process->isSuccessful())\Log::error('Leave permit Word print exited early',['exit_code'=>$process->getExitCode(),'stdout'=>$process->getOutput(),'stderr'=>trim($process->getErrorOutput())]);
        return view('leave-management::print-permit-done',['title'=>'Đã gửi lệnh in giấy nghỉ phép #'.$leaveRequest->id,'requestId'=>$leaveRequest->id]);
    }
    private function buildPermitPdfFromTemplate(LeaveReportTemplate $template,LeaveRequest $leaveRequest,?string $printLocalityPath,?string $printUnitPath):string{
        $docx=$this->buildPermitDocxFromTemplate($template,$leaveRequest,$printLocalityPath,$printUnitPath);
        $pdf=storage_path('app/giay-nghi-phep-'.$leaveRequest->id.'-'.now()->format('YmdHis').'.pdf');
        if(class_exists(\COM::class)){
            $word=null;
            $doc=null;
            try{
                $word=new \COM('Word.Application');
                $word->Visible=false;
                $word->DisplayAlerts=0;
                $doc=$word->Documents->Open($docx,false,true);
                $doc->ExportAsFixedFormat($pdf,17);
            }catch(\Throwable $e){
                \Log::error('Leave permit PHP COM Word to PDF failed',['error'=>$e->getMessage(),'docx'=>$docx,'pdf'=>$pdf]);
                abort(500,'Không chuyển được mẫu Word sang PDF qua PHP COM: '.$e->getMessage());
            }finally{
                try{if($doc)$doc->Close(false);}catch(\Throwable $e){}
                try{if($word)$word->Quit();}catch(\Throwable $e){}
                @unlink($docx);
            }
            abort_unless(is_file($pdf),500,'Không tạo được file PDF từ mẫu Word.');
            return $pdf;
        }
        $script=storage_path('app/word-export-'.bin2hex(random_bytes(6)).'.ps1');
        file_put_contents($script, <<<'PS1'
param(
    [Parameter(Mandatory=$true)][string]$Docx,
    [Parameter(Mandatory=$true)][string]$Pdf
)
$ErrorActionPreference = 'Stop'
$word = $null
$doc = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $doc = $word.Documents.Open($Docx, $false, $true)
    $doc.ExportAsFixedFormat($Pdf, 17)
} finally {
    if ($doc) {
        $doc.Close($false)
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($doc) | Out-Null
    }
    if ($word) {
        $word.Quit()
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}
PS1);
        $process=new \Symfony\Component\Process\Process([
            'C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe',
            '-NoProfile',
            '-ExecutionPolicy',
            'Bypass',
            '-File',
            $script,
            '-Docx',
            $docx,
            '-Pdf',
            $pdf,
        ]);
        $process->setTimeout(90);
        try{$process->run();}catch(\Throwable $e){\Log::error('Leave permit Word to PDF process crashed',['error'=>$e->getMessage()]);@unlink($script);@unlink($docx);abort(500,'Không chuyển được mẫu Word sang PDF để in: '.$e->getMessage());}
        $error=trim($process->getErrorOutput());
        if(!$process->isSuccessful()||!is_file($pdf))\Log::error('Leave permit Word to PDF failed',['exit_code'=>$process->getExitCode(),'stdout'=>$process->getOutput(),'stderr'=>$error,'pdf_exists'=>is_file($pdf)]);
        @unlink($script);
        @unlink($docx);
        abort_unless($process->isSuccessful()&&is_file($pdf),500,'Không chuyển được mẫu Word sang PDF để in. '.$error);
        return $pdf;
    }
    private function printPermitPdfFromTemplate(LeaveReportTemplate $template,LeaveRequest $leaveRequest,?string $printLocalityPath,?string $printUnitPath){
        $pdf=$this->buildPermitPdfFromTemplate($template,$leaveRequest,$printLocalityPath,$printUnitPath);
        return response()->file($pdf,[
            'Content-Type'=>'application/pdf',
            'Content-Disposition'=>'inline; filename=\"giay-nghi-phep-'.$leaveRequest->id.'.pdf\"',
        ])->deleteFileAfterSend(true);
    }
    private function showPermitPdfPrintPage(LeaveReportTemplate $template,LeaveRequest $leaveRequest,?string $printLocalityPath,?string $printUnitPath){
        $pdf=$this->buildPermitPdfFromTemplate($template,$leaveRequest,$printLocalityPath,$printUnitPath);
        $content=base64_encode((string)file_get_contents($pdf));
        @unlink($pdf);
        return view('leave-management::print-permit-pdf',['pdfBase64'=>$content,'title'=>'Giấy nghỉ phép #'.$leaveRequest->id]);
    }
    public function reportWord(Request $request){return $this->reportWordFixed($request);}
    public function reportCsv(Request $request){$year=(int)($request->input('year')?:now()->year);$agency=(string)$request->input('agency','');$unitId=$request->input('unit_id')?(int)$request->input('unit_id'):null;$selectedUnit=$unitId?\Modules\Unit\Models\Unit::find($unitId):null;$keyword=trim((string)$request->input('q',''));$rows=LeaveRequest::with('personnel')->whereYear('from_date',$year)->when(in_array($agency,[LeaveAccess::QUAN_LUC,LeaveAccess::CO_QUAN_CAN_BO],true),fn($q)=>$q->where(function($x)use($agency){$x->where('managing_agency',$agency)->orWhereHas('personnel',fn($p)=>$p->where('managing_agency',$agency));}))->when($unitId,fn($q)=>$q->where(function($x)use($unitId,$selectedUnit){$x->where('unit_id',$unitId)->orWhere('unit_name',$selectedUnit?->name)->orWhereHas('personnel',fn($p)=>$p->where('unit_id',$unitId)->orWhere('unit',$selectedUnit?->name));}))->when($keyword,fn($q)=>$q->where(function($x)use($keyword){$x->where('personnel_name','like','%'.$keyword.'%')->orWhere('personnel_code','like','%'.$keyword.'%')->orWhereHas('personnel',fn($p)=>$p->where('name','like','%'.$keyword.'%')->orWhere('staff_code','like','%'.$keyword.'%'));}))->latest()->get();$callback=function()use($rows){$out=fopen('php://output','w');fputcsv($out,['STT','Nhan su','Loai','Tu ngay','Den ngay','So ngay','Trang thai']);foreach($rows as $i=>$item)fputcsv($out,[$i+1,$item->personnel?->name,$item->leave_type,$item->from_date?->format('d/m/Y'),$item->to_date?->format('d/m/Y'),$item->total_days,$item->status]);fclose($out);};return response()->streamDownload($callback,'bao-cao-nghi-phep-'.$year.'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);}
    public function classes(){ return view('leave-management::feature',['section'=>'classes','title'=>'Lớp / đại đội','items'=>LeaveClass::with('unit.parent')->where('active',true)->when(LeaveAccess::isScoped(request()->user()),fn($q)=>$q->whereIn('unit_id',LeaveAccess::unitIds(request()->user())))->orderBy('name')->get(),'units'=>\Modules\Unit\Models\Unit::active()->when(LeaveAccess::isScoped(request()->user()),fn($q)=>$q->whereIn('id',LeaveAccess::unitIds(request()->user())))->orderBy('name')->get()]);}
    public function classStore(Request $request){$data=$request->validate(['unit_id'=>'required|exists:units,id','name'=>'required|string|max:255']);$unit=\Modules\Unit\Models\Unit::with('parent')->findOrFail($data['unit_id']);$isCompany=str_contains(mb_strtolower((string)$unit->name,'UTF-8'),'đại đội')&&$unit->parent&&str_contains(mb_strtolower((string)$unit->parent->name,'UTF-8'),'tiểu đoàn');abort_unless($isCompany,422,'Chỉ được tạo lớp thuộc Đại đội; Đại đội phải thuộc Tiểu đoàn.');abort_unless(LeaveAccess::canAccessUnit((int)$data['unit_id'],$request->user()),403);LeaveClass::create($data);return back()->with('success','Đã thêm lớp.');}
    public function classDelete(LeaveClass $class){if($class->personnel()->exists()||LeaveRequest::where('class_id',$class->id)->exists())return back()->withErrors(['class'=>'Không thể xóa lớp đã có dữ liệu.']);$class->delete();return back()->with('success','Đã xóa lớp.');}
    public function reportWordFixed(Request $request){
        $year=(int)($request->input('year')?:now()->year);
        $unitId=$request->input('unit_id')?(int)$request->input('unit_id'):null;
        $selectedUnit=$unitId?\Modules\Unit\Models\Unit::find($unitId):null;
        $reportType=(string)$request->input('report_type','used');
        $agency=(string)$request->input('agency','');
        $keyword=trim((string)$request->input('q',''));
        if(!in_array($reportType,['used','unused','tracking','registered'],true))$reportType='used';
        abort_unless(in_array($agency,[LeaveAccess::CO_QUAN_CAN_BO,LeaveAccess::QUAN_LUC],true),422,'Vui lòng chọn diện quản lý trước khi xuất báo cáo.');

        $today=now()->startOfDay();
        $agencyName=$agency===LeaveAccess::CO_QUAN_CAN_BO?'Cán bộ quản lý':'Quân lực quản lý';
        $signature=$agency===LeaveAccess::CO_QUAN_CAN_BO
            ? ['reporter'=>'Thiếu tá Trần Phương Tùng','commander'=>'Đại tá Nguyễn Viết Túy']
            : ['reporter'=>'Thiếu tá QNCN Nguyễn Trung Tuấn','commander'=>'Đại tá Đinh Văn Tuyên'];
        $titles=[
            'used'=>'DANH SÁCH QUÂN NHÂN ĐÃ NGHỈ PHÉP NĂM '.$year,
            'unused'=>'DANH SÁCH QUÂN NHÂN CHƯA NGHỈ PHÉP NĂM '.$year,
            'tracking'=>'DANH SÁCH THEO DÕI QUÂN NHÂN ĐÃ NGHỈ PHÉP NĂM '.$year,
            'registered'=>'DANH SÁCH QUÂN NHÂN ĐĂNG KÝ NGHỈ PHÉP NĂM '.$year,
        ];

        $agencyFilter=function($q)use($agency){$q->where(function($x)use($agency){$x->where('managing_agency',$agency)->orWhereHas('personnel',fn($p)=>$p->where('managing_agency',$agency));});};
        $unitFilter=function($q)use($unitId,$selectedUnit){if($unitId)$q->where(function($x)use($unitId,$selectedUnit){$x->where('unit_id',$unitId)->orWhere('unit_name',$selectedUnit?->name)->orWhereHas('personnel',fn($p)=>$p->where('unit_id',$unitId)->orWhere('unit',$selectedUnit?->name));});};
        $personUnitFilter=function($q)use($unitId,$selectedUnit){if($unitId)$q->where(function($x)use($unitId,$selectedUnit){$x->where('unit_id',$unitId)->orWhere('unit',$selectedUnit?->name);});};
        $nameFilter=function($q)use($keyword){if($keyword!=='')$q->where(function($x)use($keyword){$x->where('personnel_name','like','%'.$keyword.'%')->orWhere('personnel_code','like','%'.$keyword.'%')->orWhereHas('personnel',fn($p)=>$p->where('name','like','%'.$keyword.'%')->orWhere('staff_code','like','%'.$keyword.'%'));});};
        $personNameFilter=function($q)use($keyword){if($keyword!=='')$q->where(function($x)use($keyword){$x->where('name','like','%'.$keyword.'%')->orWhere('staff_code','like','%'.$keyword.'%');});};
        $formatDate=fn($value)=>$value?$value->format('d/m/Y'):'';
        $formatMonth=fn($value)=>$value?$value->format('m/Y'):'';
        $rankFor=function($rank)use($agency){$rank=(string)$rank;if($agency===LeaveAccess::CO_QUAN_CAN_BO)$rank=trim(str_replace(['QNCN','CNQP','VCQP'],'',$rank));return $rank;};
        $leaveReason=function($item)use($year){$type=strtoupper((string)$item->leave_type);if($type==='ANNUAL')return 'Phép năm '.$year;if(in_array($type,['SPECIAL','PERSONAL'],true))return 'Phép đặc biệt';return $item->reason?:$item->note?:$item->leave_type?:'';};
        $daysUsed=function($item)use($today){$start=$item->from_date?->copy()->startOfDay();$end=$item->to_date?->copy()->startOfDay();$total=(int)($item->total_days?:0);if(!$start||$today->lt($start))return 0;if($end&&$today->lt($end))return max(1,min($total,$start->diffInDays($today)+1));return $total;};

        $requests=LeaveRequest::with(['personnel.unitRelation'])->whereYear('from_date',$year)->when(true,$agencyFilter)->when($unitId,$unitFilter)->when($keyword,$nameFilter)->orderBy('unit_name')->orderBy('personnel_name')->get();
        $approved=$requests->where('status','APPROVED')->values();
        $taken=$approved->filter(fn($item)=>$item->from_date&&!$today->lt($item->from_date->copy()->startOfDay()))->values();
        $usedPersonnel=$taken->pluck('personnel_id')->filter()->unique();
        $personnel=LeavePersonnel::with('unitRelation')->where('active',true)->where('managing_agency',$agency)->when($unitId,$personUnitFilter)->when($keyword,$personNameFilter)->orderBy('unit')->orderBy('name')->get();

        $rows=collect();
        if($reportType==='unused'){
            $rows=$personnel->filter(fn($p)=>!$usedPersonnel->contains($p->id))->map(fn($p)=>['unit'=>$p->unitRelation?->name?:$p->unit?:'CHƯA CÓ ĐƠN VỊ','name'=>$p->name,'rank'=>$rankFor($p->rank),'enlistment'=>$formatMonth($p->enlistment_date),'hometown'=>$p->hometown,'permanent'=>$p->permanent_residence,'note'=>''])->values();
        }elseif($reportType==='registered'){
            $rows=$requests->whereNotIn('status',['REJECTED'])->map(function($item)use($formatDate,$formatMonth,$rankFor,$today){$p=$item->personnel;$started=$item->status==='APPROVED'&&$item->from_date&&!$today->lt($item->from_date->copy()->startOfDay());return ['unit'=>$item->unit_name?:$p?->unitRelation?->name?:$p?->unit?:'CHƯA CÓ ĐƠN VỊ','name'=>$item->personnel_name?:$p?->name,'rank'=>$rankFor($item->rank?:$p?->rank),'enlistment'=>$formatMonth($item->enlistment_date?:$p?->enlistment_date),'from'=>$formatDate($item->from_date),'to'=>$formatDate($item->to_date),'place'=>$item->locality_path?:$item->reason?:'','note'=>$started?'Đã nghỉ':'Chưa nghỉ'];})->values();
        }elseif($reportType==='tracking'){
            $rows=$approved->map(function($item)use($formatMonth,$rankFor,$daysUsed){$p=$item->personnel;$used=(int)$daysUsed($item);$total=(int)($item->total_days?:0);return ['unit'=>$item->unit_name?:$p?->unitRelation?->name?:$p?->unit?:'CHƯA CÓ ĐƠN VỊ','name'=>$item->personnel_name?:$p?->name,'rank'=>$rankFor($item->rank?:$p?->rank),'enlistment'=>$formatMonth($item->enlistment_date?:$p?->enlistment_date),'total'=>$total,'used'=>$used,'remaining'=>max(0,$total-$used),'place'=>$item->locality_path?:$item->reason?:'','reason'=>strtoupper((string)$item->leave_type)==='ANNUAL'?'Phép năm':'Phép đặc biệt'];})->values();
        }else{
            $rows=$taken->map(function($item)use($formatDate,$formatMonth,$rankFor,$leaveReason){$p=$item->personnel;return ['unit'=>$item->unit_name?:$p?->unitRelation?->name?:$p?->unit?:'CHƯA CÓ ĐƠN VỊ','name'=>$item->personnel_name?:$p?->name,'rank'=>$rankFor($item->rank?:$p?->rank),'enlistment'=>$formatMonth($item->enlistment_date?:$p?->enlistment_date),'from'=>$formatDate($item->from_date),'to'=>$formatDate($item->to_date),'place'=>$item->locality_path?:$item->reason?:'','reason'=>$leaveReason($item)];})->values();
        }

        $selectedTemplateId=(int)$request->input('template_id');
        $selectedTemplate=$selectedTemplateId?LeaveReportTemplate::whereKey($selectedTemplateId)->where('template_kind','report')->where('active',true)->where('report_type',$reportType)->where('managing_agency',$agency)->first():null;
        if($selectedTemplate){
            return $this->downloadReportFromTemplate($selectedTemplate,$rows,$reportType,$year,$agency,$agencyName,$signature,$titles[$reportType]);
        }

        $word=new \PhpOffice\PhpWord\PhpWord();$word->setDefaultFontName('Times New Roman');$word->setDefaultFontSize(12);
        $section=$word->addSection(['orientation'=>'landscape','pageSizeW'=>15840,'pageSizeH'=>12240,'marginLeft'=>1701,'marginRight'=>851,'marginTop'=>567,'marginBottom'=>567]);
        $center=['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER];$left=['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::LEFT];$bold=['bold'=>true];$italic=['italic'=>true];$title=['bold'=>true,'size'=>13];$cellFont=['size'=>12];$headerFont=['bold'=>true,'size'=>12];$border=['borderSize'=>6,'borderColor'=>'000000','cellMargin'=>60,'layout'=>\PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED];$cell=['valign'=>'center'];$personCell=$cell+['noWrap'=>true];$unitCell=$cell+['noWrap'=>false];$noBorder=['borderSize'=>0,'borderColor'=>'FFFFFF','borderInsideH'=>0,'borderInsideV'=>0,'cellMargin'=>40];
        $top=$section->addTable($noBorder);$top->addRow();
        $leftCell=$top->addCell(6200);$leftCell->addText('TỔNG CỤC HẬU CẦN KỸ THUẬT',[],$center);$leftCell->addText('TRƯỜNG CAO ĐẲNG HẬU CẦN 2',$bold,$center);
        $rightCell=$top->addCell(7800);$rightCell->addText('CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM',$bold,$center);$rightCell->addText('Độc lập – Tự do – Hạnh phúc',$bold,$center);$rightCell->addText('Thành phố Hồ Chí Minh, ngày '.now()->format('d').' tháng '.now()->format('m').' năm '.now()->format('Y'),$italic,$center);
        $section->addTextBreak(1);
        $section->addText('BÁO CÁO',$title,$center);$section->addText($titles[$reportType],$title,$center);$section->addText('(Diện '.$agencyName.')',$title,$center);$section->addTextBreak(1);

        $headers=[
            'used'=>[['TT','Họ và tên','Cấp bậc','Nhập ngũ','Đã nghỉ phép','','','Lý do'],['','','','','Từ ngày','Đến ngày','Nơi nghỉ phép',''],[600,3000,1100,1100,1300,1300,3700,1800]],
            'unused'=>[['TT','Họ và tên','Cấp bậc','Nhập ngũ','Quê quán','Trú quán','Ghi chú'],null,[600,3000,1100,1100,3000,3400,1800]],
            'tracking'=>[['TT','Họ và tên','Cấp bậc','Nhập ngũ','Đã nghỉ phép','','','','Lý do'],['','','','','Số ngày được nghỉ','Số ngày đã nghỉ','Số ngày còn lại','Nơi nghỉ phép',''],[600,2800,1000,1000,1300,1200,1200,3400,1600]],
            'registered'=>[['TT','Họ và tên','Cấp bậc','Nhập ngũ','Đăng ký nghỉ phép năm','','','Ghi chú'],['','','','','Từ ngày','Đến ngày','Nơi nghỉ phép',''],[600,3000,1100,1100,1300,1300,3700,1800]],
        ];
        [$h1,$h2,$widths]=$headers[$reportType];$table=$section->addTable($border+['alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]);
        $tr=$table->addRow();foreach($h1 as $i=>$text){$grid=($reportType==='tracking'&&$i===4)?4:(in_array($reportType,['used','registered'],true)&&$i===4?3:1);if($text===''&&$i>4)continue;$style=$cell+($grid>1?['gridSpan'=>$grid]:[])+(($h2&&($grid===1||$i<4||$i===count($h1)-1))?['vMerge'=>'restart']:[]);$tr->addCell($widths[$i],$style)->addText($text,$headerFont,$center);}
        if($h2){$tr=$table->addRow();foreach($h1 as $i=>$text){if(($reportType==='tracking'&&$i>4&&$i<8)||(in_array($reportType,['used','registered'],true)&&$i>4&&$i<7)||($i>=4&&$h2[$i])){$tr->addCell($widths[$i],$cell)->addText($h2[$i],$headerFont,$center);}else{$tr->addCell($widths[$i],$cell+['vMerge'=>'continue'])->addText('',[],$center);}}}
        $grouped=$rows->groupBy(fn($row)=>mb_strtoupper((string)($row['unit']?:'CHƯA CÓ ĐƠN VỊ'),'UTF-8'));
        foreach($grouped as $unitName=>$unitRows){$groupRow=$table->addRow();foreach($widths as $i=>$w)$groupRow->addCell($w,$unitCell)->addText($i===1?$unitName:'',$cellFont,$i===1?$left:$center);foreach($unitRows->values() as $i=>$item){$row=$table->addRow();$values=$reportType==='unused'?[$i+1,$item['name'],$item['rank'],$item['enlistment'],$item['hometown'],$item['permanent'],$item['note']]:($reportType==='tracking'?[$i+1,$item['name'],$item['rank'],$item['enlistment'],$item['total'],$item['used'],$item['remaining'],$item['place'],$item['reason']]:[$i+1,$item['name'],$item['rank'],$item['enlistment'],$item['from'],$item['to'],$item['place'],$item['reason']??$item['note']]);foreach($values as $j=>$value)$row->addCell($widths[$j],$personCell)->addText((string)($value!==''?$value:' '),$cellFont,$j===1?$left:$center);}}
        if($rows->isEmpty()){$row=$table->addRow();foreach($widths as $i=>$w)$row->addCell($w,$cell)->addText($i===1?'Không có dữ liệu trong năm '.$year.'.':'',$cellFont,$i===1?$left:$center);}

        $summarySource=$reportType==='used'?$taken:$approved;
        $annual=$summarySource->where('leave_type','ANNUAL')->count();$special=max(0,$summarySource->count()-$annual);
        $summary=$reportType==='unused'?'* Tổng số: '.$rows->count().' đ/c.':($reportType==='registered'?'* Tổng số: '.$rows->count().' đ/c, trong đó: Đã nghỉ phép = '.$rows->where('note','Đã nghỉ')->count().' đ/c, Chưa nghỉ phép = '.$rows->where('note','Chưa nghỉ')->count().' đ/c.':'* Tổng số: '.$rows->count().' đ/c, trong đó: Phép năm = '.$annual.' đ/c, Phép đặc biệt = '.$special.' đ/c.');
        $section->addText($summary,['size'=>14],$left);
        $footer=$section->addTable($noBorder);$footer->addRow();$reporter=$footer->addCell(7000);$commander=$footer->addCell(7000);
        foreach([[$reporter,'NGƯỜI BÁO CÁO',$signature['reporter']],[$commander,'THỦ TRƯỞNG ĐƠN VỊ',$signature['commander']]] as [$cellObj,$role,$name]){$cellObj->addText($role,$bold,$center);$cellObj->addTextBreak(3);$cellObj->addText($name,$bold,$center);}
        $path=storage_path('app/report-phep-'.now()->format('YmdHis').'.docx');(new \PhpOffice\PhpWord\Writer\Word2007($word))->save($path);
        return response()->download($path,'bao-cao-nghi-phep-'.$year.'-'.$reportType.'-'.$agency.'.docx')->deleteFileAfterSend(true);
    }

    private function downloadReportFromTemplate(LeaveReportTemplate $template,$rows,string $reportType,int $year,string $agency,string $agencyName,array $signature,string $title){
        $path=$template->absolutePath();abort_unless($path&&is_file($path),404,'Không tìm thấy file mẫu báo cáo.');
        $processor=new \PhpOffice\PhpWord\TemplateProcessor($path);
        $today=now();
        $annualCount=$rows->filter(fn($row)=>str_contains((string)($row['reason']??''),'Phép năm'))->count();
        $specialCount=max(0,$rows->count()-$annualCount);
        $registeredTaken=$rows->where('note','Đã nghỉ')->count();
        $registeredPending=$rows->where('note','Chưa nghỉ')->count();
        $processor->setValues([
            'nam'=>(string)$year,
            'ngay_bao_cao'=>$today->format('d/m/Y'),
            'ngay'=>$today->format('d'),
            'thang'=>$today->format('m'),
            'nam_hien_tai'=>$today->format('Y'),
            'tieu_de'=>$title,
            'loai_bao_cao'=>$title,
            'dien_quan_ly'=>$agencyName,
            'co_quan_quan_ly'=>$agency===LeaveAccess::CO_QUAN_CAN_BO?'Cơ quan cán bộ':'Quân lực',
            'nguoi_bao_cao'=>$signature['reporter'],
            'thu_truong'=>$signature['commander'],
            'tong_so'=>(string)$rows->count(),
            'so_phep_nam'=>(string)$annualCount,
            'so_phep_dac_biet'=>(string)$specialCount,
            'so_da_nghi'=>(string)$registeredTaken,
            'so_chua_nghi'=>(string)$registeredPending,
        ]);
        $makeTemplateRow=function(array $row,int $index,bool $isGroup=false)use($reportType){
            $data=[
                'stt'=>$isGroup?'':(string)($index+1),
                'ho_ten'=>$isGroup?mb_strtoupper((string)($row['unit']??'CHƯA CÓ ĐƠN VỊ'),'UTF-8'):(string)($row['name']??''),
                'cap_bac'=>$isGroup?'':(string)($row['rank']??''),
                'nhap_ngu'=>$isGroup?'':(string)($row['enlistment']??''),
                'don_vi'=>(string)($row['unit']??''),
                'don_vi_quan_nhan'=>$isGroup?mb_strtoupper((string)($row['unit']??'CHƯA CÓ ĐƠN VỊ'),'UTF-8'):'',
                'tu_ngay'=>$isGroup?'':(string)($row['from']??''),
                'den_ngay'=>$isGroup?'':(string)($row['to']??''),
                'noi_nghi_phep'=>$isGroup?'':(string)($row['place']??''),
                'ly_do'=>$isGroup?'':(string)($row['reason']??''),
                'que_quan'=>$isGroup?'':(string)($row['hometown']??''),
                'tru_quan'=>$isGroup?'':(string)($row['permanent']??''),
                'ghi_chu'=>$isGroup?'':(string)($row['note']??''),
                'tong_ngay'=>$isGroup?'':(string)($row['total']??''),
                'da_nghi'=>$isGroup?'':(string)($row['used']??''),
                'con_lai'=>$isGroup?'':(string)($row['remaining']??''),
            ];
            if($reportType!=='unused'){$data['que_quan']='';$data['tru_quan']='';}
            return $data;
        };
        $templateRows=[];
        foreach($rows->groupBy(fn($row)=>mb_strtoupper((string)($row['unit']?:'CHƯA CÓ ĐƠN VỊ'),'UTF-8')) as $unitName=>$unitRows){
            $templateRows[]=$makeTemplateRow(['unit'=>$unitName],0,true);
            foreach($unitRows->values() as $index=>$row)$templateRows[]=$makeTemplateRow($row,$index,false);
        }
        if(!$templateRows)$templateRows=[['stt'=>'','ho_ten'=>'','cap_bac'=>'','nhap_ngu'=>'','don_vi'=>'','don_vi_quan_nhan'=>'','tu_ngay'=>'','den_ngay'=>'','noi_nghi_phep'=>'','ly_do'=>'','que_quan'=>'','tru_quan'=>'','ghi_chu'=>'','tong_ngay'=>'','da_nghi'=>'','con_lai'=>'']];
        if(!$this->fillReportTemplateUnitRows($processor,$rows,$reportType,$makeTemplateRow)){
            try{$processor->cloneRowAndSetValues('stt',$templateRows);}catch(\Throwable $e){$processor->setValue('bang_du_lieu',$rows->map(fn($row,$i)=>($i+1).'. '.($row['name']??'').' - '.($row['unit']??'').' - '.($row['from']??'').' '.($row['to']??''))->implode("\n"));}
        }
        foreach(['stt','ho_ten','cap_bac','nhap_ngu','don_vi','don_vi_quan_nhan','tu_ngay','den_ngay','noi_nghi_phep','ly_do','que_quan','tru_quan','ghi_chu','tong_ngay','da_nghi','con_lai','bang_du_lieu'] as $macro)$processor->setValue($macro,'');
        $output=storage_path('app/report-phep-template-'.now()->format('YmdHis').'-'.$template->id.'.docx');
        $processor->saveAs($output);
        return response()->download($output,'bao-cao-nghi-phep-'.$year.'-'.$reportType.'-'.$agency.'-mau-'.$template->id.'.docx')->deleteFileAfterSend(true);
    }
    private function fillReportTemplateUnitRows(\PhpOffice\PhpWord\TemplateProcessor $processor,$rows,string $reportType,\Closure $makeTemplateRow):bool{
        if(!in_array('don_vi_quan_nhan',$processor->getVariables(),true))return false;
        try{
            $property=new \ReflectionProperty($processor,'tempDocumentMainPart');$property->setAccessible(true);$xml=$property->getValue($processor);
            $xml=$this->fixReportTemplateTableLayout($xml);
            $unitPos=strpos($xml,'${don_vi_quan_nhan}');$rowPos=strpos($xml,'${stt}');
            if($unitPos===false||$rowPos===false||$unitPos>$rowPos)return false;
            $unitStart=$this->findReportTemplateRowStart($xml,$unitPos);$unitEnd=strpos($xml,'</w:tr>',$unitPos)+7;
            $rowStart=$this->findReportTemplateRowStart($xml,$rowPos);$rowEnd=strpos($xml,'</w:tr>',$rowPos)+7;
            if($unitStart===false||$rowStart===false||$unitEnd<7||$rowEnd<7||$unitStart>$rowStart)return false;
            $unitXml=$this->normalizeReportTemplateTableRow(substr($xml,$unitStart,$unitEnd-$unitStart),true);
            $rowXml=$this->normalizeReportTemplateTableRow(substr($xml,$rowStart,$rowEnd-$rowStart),false);
            $replacement='';
            $groups=$rows->groupBy(fn($row)=>mb_strtoupper((string)($row['unit']?:'CHƯA CÓ ĐƠN VỊ'),'UTF-8'));
            if($groups->isEmpty())$groups=collect([''=>collect([])]);
            foreach($groups as $unitName=>$unitRows){
                $replacement.=$this->replaceReportTemplateMacros($unitXml,['don_vi_quan_nhan'=>$unitName?:'CHƯA CÓ ĐƠN VỊ']);
                foreach($unitRows->values() as $index=>$row)$replacement.=$this->replaceReportTemplateMacros($rowXml,$makeTemplateRow($row,$index,false));
            }
            $property->setValue($processor,substr($xml,0,$unitStart).$replacement.substr($xml,$rowEnd));
            return true;
        }catch(\Throwable $e){return false;}
    }
    private function replaceReportTemplateMacros(string $xml,array $values):string{
        foreach($values as $macro=>$value)$xml=str_replace('${'.$macro.'}',htmlspecialchars((string)$value,ENT_QUOTES|ENT_XML1,'UTF-8'),$xml);
        return $xml;
    }
    private function normalizeReportTemplateTableRow(string $xml,bool $allowWrap):string{
        $xml=$this->setReportTemplateRowFontSize($xml,24);
        if($allowWrap){
            return preg_replace('/<w:noWrap\s*\/>/', '', $xml);
        }
        if(!$allowWrap){
            $xml=preg_replace_callback('/<w:tcPr>([\s\S]*?)<\/w:tcPr>/',function($match){
                if(str_contains($match[1],'<w:noWrap'))return $match[0];
                if(preg_match('/<w:tcW\b[^>]*\/>/', $match[1], $width)){
                    return '<w:tcPr>'.preg_replace('/<w:tcW\b[^>]*\/>/', $width[0].'<w:noWrap/>', $match[1], 1).'</w:tcPr>';
                }
                return '<w:tcPr><w:noWrap/>'.$match[1].'</w:tcPr>';
            },$xml);
        }
        return $xml;
    }
    private function fixReportTemplateTableLayout(string $xml):string{
        return preg_replace_callback('/<w:tbl\b[\s\S]*?<\/w:tbl>/',function($match){
            $table=$match[0];
            if(!str_contains($table,'${stt}'))return $table;
            return preg_replace_callback('/<w:tblPr>([\s\S]*?)<\/w:tblPr>/',function($pr){
                if(str_contains($pr[1],'<w:tblLayout'))return preg_replace('/<w:tblLayout\b[^>]*\/>/','<w:tblLayout w:type="fixed"/>',$pr[0]);
                return '<w:tblPr>'.$pr[1].'<w:tblLayout w:type="fixed"/></w:tblPr>';
            },$table,1);
        },$xml);
    }
    private function setReportTemplateRowFontSize(string $xml,int $halfPoints):string{
        $xml=preg_replace('/<w:sz w:val="[^"]*"\s*\/>/','<w:sz w:val="'.$halfPoints.'"/>',$xml);
        $xml=preg_replace('/<w:szCs w:val="[^"]*"\s*\/>/','<w:szCs w:val="'.$halfPoints.'"/>',$xml);
        return preg_replace_callback('/<w:rPr>([\s\S]*?)<\/w:rPr>/',function($match)use($halfPoints){
            $props=$match[1];
            if(!str_contains($props,'<w:sz '))$props.='<w:sz w:val="'.$halfPoints.'"/>';
            if(!str_contains($props,'<w:szCs '))$props.='<w:szCs w:val="'.$halfPoints.'"/>';
            return '<w:rPr>'.$props.'</w:rPr>';
        },$xml);
    }
    private function findReportTemplateRowStart(string $xml,int $position):int|false{
        $before=substr($xml,0,$position);
        $start=strrpos($before,'<w:tr ');
        $plain=strrpos($before,'<w:tr>');
        if($start===false)return $plain;
        if($plain===false)return $start;
        return max($start,$plain);
    }
}
