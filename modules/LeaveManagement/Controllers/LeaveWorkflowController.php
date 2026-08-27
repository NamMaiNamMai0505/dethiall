<?php
namespace Modules\LeaveManagement\Controllers;
use App\Http\Controllers\ModuleBaseController;
use App\Support\PermissionCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Class\Models\ClassModel;
use Modules\LeaveManagement\Models\{LeaveAlert,LeaveAuditLog,LeaveBatch,LeaveClass,LeaveExtraStandard,LeaveLocality,LeaveObjectType,LeavePosition,LeaveRecord,LeaveRegulation,LeaveRequest,LeavePersonnel};
use Modules\LeaveManagement\Support\LeaveAccess;
class LeaveWorkflowController extends ModuleBaseController {
    public function __construct(){
        $this->middleware('permission:leave-management.personnel.index|leave-management.personnel.show|leave-management.show')->only(['personnel','directory']);
        $this->middleware('permission:leave-management.requests.index|leave-management.requests.show|leave-management.show')->only(['requests','requestDetail']);
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
    public function personnel(){ $users=\App\Models\User::where('status',1)->orderBy('name')->get(); $commanderUsers=$users->whereIn('id',LeaveAccess::commanderUserIds())->values(); return view('leave-management::feature',['section'=>'personnel','title'=>'Quân nhân / nhân sự','items'=>LeavePersonnel::with(['user','unitRelation','commander'])->where('active',true)->orderBy('name')->get(),'users'=>$users,'commanderUsers'=>$commanderUsers,'units'=>\Modules\Unit\Models\Unit::active()->orderBy('name')->get(),'objects'=>LeaveObjectType::where('active',true)->orderBy('sort_order')->get(),'positions'=>LeavePosition::orderBy('sort_order')->orderBy('name')->get()]);}
     public function personnelUpdate(Request $request, LeavePersonnel $personnel){$data=$request->validate(['user_id'=>'nullable|exists:users,id','staff_code'=>'nullable|string|max:80','name'=>'nullable|string|max:255','position'=>'nullable|string|max:255','object_type'=>'nullable|string|max:50','rank'=>'nullable|string|max:80','unit'=>'nullable|string|max:255','unit_id'=>'nullable|exists:units,id','email'=>'nullable|email|max:255','gmail'=>'nullable|email|max:255','enlistment_date'=>'nullable|date','hometown'=>'nullable|string|max:255','permanent_residence'=>'nullable|string|max:255','commander_name'=>'nullable|string|max:255','commander_user_id'=>'nullable|exists:users,id']);$preserve=['name','staff_code','position','object_type','rank','unit','unit_id','email','gmail','enlistment_date','hometown','permanent_residence','commander_name','commander_user_id'];foreach($preserve as $field){if(array_key_exists($field,$data)&&($data[$field]===null||$data[$field]===''))$data[$field]=$personnel->{$field};}if(!trim((string)($data['name']??'')))$data['name']=$personnel->name;$request->merge($data);$personnel->update($data);LeaveAuditLog::create(['user_id'=>$request->user()->id,'action'=>'UPDATE','entity_type'=>'personnel','entity_id'=>$personnel->id,'details'=>$data]);return back()->with('success','Đã cập nhật nhân sự và giữ nguyên các thông tin không thay đổi.');}
    public function personnelDelete(LeavePersonnel $personnel){if($personnel->requests()->exists())return back()->withErrors(['personnel'=>'Không thể xóa nhân sự đã có đơn phép.']);$personnel->delete();return back()->with('success','Đã xóa nhân sự.');}
    public function directory(){return view('leave-management::feature',['section'=>'directory','title'=>'Danh sách nghỉ phép','items'=>LeavePersonnel::with(['unitRelation'])->where('active',true)->withCount(['requests as approved_leave_days'=>function($q){$q->where('status','APPROVED')->whereYear('from_date',now()->year);}])->orderBy('name')->get()]);}
    public function units(){return view('leave-management::feature',['section'=>'units','title'=>'Đơn vị quản lý phép','items'=>\Modules\Unit\Models\Unit::active()->withCount('instructors')->orderBy('name')->get()]);}
    public function requests(){
        $user=request()->user();
        $personnel=LeaveAccess::personnel(LeavePersonnel::where('active',true),$user)->with(['unitRelation','leaveClass'])->orderBy('name')->get();
        $classes=LeaveClass::with(['unit.parent','personnel'=>fn($q)=>$q->where('active',true)->orderBy('name')])->where('active',true)->when(LeaveAccess::isScoped($user),fn($q)=>$q->whereIn('unit_id',LeaveAccess::unitIds($user)))->orderBy('name')->get();
        $localities=LeaveLocality::with('parent')->orderBy('level')->orderBy('name')->get();
         $linkedMilitaryPersonnel=LeaveAccess::personnelForUser($user);
        $isMilitaryAccount=!($user->isSuperAdmin()||$user->can('leave-management.approvals.approve')||$user->can('leave-management.approve'))&&($user->hasRole(\App\Support\RoleCatalog::LEAVE_MILITARY)||$linkedMilitaryPersonnel);
        $canProposeForUnit=$user->isSuperAdmin()||(!$linkedMilitaryPersonnel&&($user->can('leave-management.approvals.approve')||$user->can('leave-management.approve')));
        $isMilitaryAccount=!$user->isSuperAdmin()&&($linkedMilitaryPersonnel||$user->hasRole(\App\Support\RoleCatalog::LEAVE_MILITARY));
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
        $user=request()->user();
        abort_unless(PermissionCheck::isLeaveAgency($user) || LeaveAccess::canApprove($user),403,'Chỉ tài khoản Cơ quan cán bộ hoặc Quân lực được in giấy nghỉ phép.');
        return view('leave-management::print-request',['request'=>$leaveRequest->load(['personnel','commander'])]);
    }
    public function approvals(){
        $user=request()->user();
         $agency=LeaveAccess::agencyForUser($user); $items=LeaveRequest::with(['personnel','leaveClass'])->where(function($q)use($user,$agency){$q->where(fn($x)=>$x->where('status','PENDING_COMMANDER')->where('commander_user_id',$user->id))->orWhere(fn($x)=>$x->where('status','PENDING_AGENCY')->when(!$user->isSuperAdmin(),fn($y)=>$y->where('managing_agency',$agency ?: '__NONE__')));})->when(!$user->isSuperAdmin()&&!PermissionCheck::can($user,'leave-management.approvals.approve')&&!PermissionCheck::can($user,'leave-management.approve'),fn($q)=>$q->whereRaw('1=0'))->latest()->get();
        return view('leave-management::feature',['section'=>'approvals','title'=>'Duyệt nghỉ phép','items'=>$items]);
    }
    public function regulations(){return view('leave-management::feature',['section'=>'regulations','title'=>'Quy định phép','items'=>LeaveRegulation::latest()->get(),'objects'=>LeaveObjectType::where('active',true)->orderBy('sort_order')->get()]);}
    public function regulationStore(Request $r){$d=$r->validate(['leave_type'=>'required|string|max:50','object_type'=>'nullable|string|max:50','min_years'=>'nullable|integer|min:0','max_years'=>'nullable|integer|min:0','base_days'=>'required|integer|min:0','label'=>'nullable|string|max:255','description'=>'nullable|string']);LeaveRegulation::create($d);return back()->with('success','Đã thêm quy định phép.');}
    public function regulationUpdate(Request $r, LeaveRegulation $regulation){$regulation->update($r->validate(['leave_type'=>'required|string|max:50','object_type'=>'nullable|string|max:50','min_years'=>'nullable|integer|min:0','max_years'=>'nullable|integer|min:0','base_days'=>'required|integer|min:0','label'=>'nullable|string|max:255','description'=>'nullable|string']));return back()->with('success','Đã cập nhật quy định.');}
    public function regulationDelete(LeaveRegulation $regulation){$regulation->delete();return back()->with('success','Đã xóa quy định.');}
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
    public function records(){return view('leave-management::feature',['section'=>'records','title'=>'Hồ sơ phép','items'=>LeaveRecord::with(['request.personnel','decidedBy'])->latest()->get()]);}
    public function auditLogs(){return view('leave-management::feature',['section'=>'audit','title'=>'Nhật ký quản lý phép','items'=>LeaveAuditLog::with('user')->latest()->limit(200)->get()]);}
    public function alerts(Request $request){$alerts=LeaveAlert::with('request.personnel')->where('user_id',$request->user()->id)->latest()->paginate(30);return view('leave-management::feature',['section'=>'alerts','title'=>'Thông báo quản lý phép','items'=>$alerts]);}
    public function alertRead(Request $request, LeaveAlert $alert){abort_unless((int)$alert->user_id===(int)$request->user()->id,403);$alert->update(['read_at'=>now()]);return back();}
    public function archiveRecord(LeaveRecord $record){$record->update(['archived_at'=>now()]);return back()->with('success','Đã lưu trữ bản ghi phép.');}
    public function reports(Request $request){$year=(int)($request->input('year')?:now()->year);$today=now()->startOfDay();$approved=LeaveRequest::with(['personnel.unitRelation'])->where('status','APPROVED')->whereYear('from_date',$year)->latest()->get()->map(function($item)use($today){$start=$item->from_date?->copy()->startOfDay();$end=$item->to_date?->copy()->startOfDay();$item->days_used=($start&&!$today->lt($start))?min((int)$item->total_days,$end&&!$today->gte($end)?$start->diffInDays($today)+1:(int)$item->total_days):0;$item->days_remaining=$end&&!$today->gte($end)?$today->diffInDays($end):0;return $item;});$taken=$approved->filter(fn($item)=>$item->from_date&&!$today->lt($item->from_date->copy()->startOfDay()))->values();$usedPersonnel=$taken->pluck('personnel_id')->filter()->unique();$notYet=LeavePersonnel::with('unitRelation')->where('active',true)->when($usedPersonnel->isNotEmpty(),fn($q)=>$q->whereNotIn('id',$usedPersonnel->all()))->orderBy('name')->get();$yearSummary=$approved->groupBy('personnel_id')->map(fn($rows)=>['personnel'=>$rows->first()->personnel,'days'=>$rows->sum('total_days'),'quota'=>$rows->max('base_days')]);return view('leave-management::feature',['section'=>'reports','title'=>'Báo cáo phép','year'=>$year,'taken'=>$taken,'notYet'=>$notYet,'yearSummary'=>$yearSummary,'comparison'=>$approved,'pending'=>LeaveRequest::whereIn('status',['PENDING','PENDING_COMMANDER','PENDING_AGENCY'])->count(),'approved'=>LeaveRequest::where('status','APPROVED')->count(),'rejected'=>LeaveRequest::where('status','REJECTED')->count(),'days'=>$approved->sum('total_days')]);}
    public function reportWord(Request $request){return $this->reportWordFixed($request);}
    public function reportCsv(Request $request){$year=(int)($request->input('year')?:now()->year);$rows=LeaveRequest::with('personnel')->whereYear('from_date',$year)->latest()->get();$callback=function()use($rows){$out=fopen('php://output','w');fputcsv($out,['STT','Nhan su','Loai','Tu ngay','Den ngay','So ngay','Trang thai']);foreach($rows as $i=>$item)fputcsv($out,[$i+1,$item->personnel?->name,$item->leave_type,$item->from_date?->format('d/m/Y'),$item->to_date?->format('d/m/Y'),$item->total_days,$item->status]);fclose($out);};return response()->streamDownload($callback,'bao-cao-nghi-phep-'.$year.'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);}
    public function classes(){ return view('leave-management::feature',['section'=>'classes','title'=>'Lớp / đại đội','items'=>LeaveClass::with('unit.parent')->where('active',true)->when(LeaveAccess::isScoped(request()->user()),fn($q)=>$q->whereIn('unit_id',LeaveAccess::unitIds(request()->user())))->orderBy('name')->get(),'units'=>\Modules\Unit\Models\Unit::active()->when(LeaveAccess::isScoped(request()->user()),fn($q)=>$q->whereIn('id',LeaveAccess::unitIds(request()->user())))->orderBy('name')->get()]);}
    public function classStore(Request $request){$data=$request->validate(['unit_id'=>'required|exists:units,id','name'=>'required|string|max:255']);$unit=\Modules\Unit\Models\Unit::with('parent')->findOrFail($data['unit_id']);$isCompany=str_contains(mb_strtolower((string)$unit->name,'UTF-8'),'đại đội')&&$unit->parent&&str_contains(mb_strtolower((string)$unit->parent->name,'UTF-8'),'tiểu đoàn');abort_unless($isCompany,422,'Chỉ được tạo lớp thuộc Đại đội; Đại đội phải thuộc Tiểu đoàn.');abort_unless(LeaveAccess::canAccessUnit((int)$data['unit_id'],$request->user()),403);LeaveClass::create($data);return back()->with('success','Đã thêm lớp.');}
    public function classDelete(LeaveClass $class){if($class->personnel()->exists()||LeaveRequest::where('class_id',$class->id)->exists())return back()->withErrors(['class'=>'Không thể xóa lớp đã có dữ liệu.']);$class->delete();return back()->with('success','Đã xóa lớp.');}
    public function reportWordFixed(Request $request){
        $year=(int)($request->input('year')?:now()->year);
        $unitId=$request->input('unit_id');$selectedUnit=$unitId?\Modules\Unit\Models\Unit::find($unitId):null;
        $template=public_path('samples/leave-management/MẪU ĐĂNG KÍ PHÉP.docx');
        if(false && is_file($template)){
            $request->merge(['don_vi'=>(string)($selectedUnit?->name?:$request->input('don_vi',''))]);
            $rows=LeaveRequest::with('personnel')->whereYear('from_date',$year)->latest()->get();$processor=new \PhpOffice\PhpWord\TemplateProcessor($template);$ref=new \ReflectionClass($processor);$prop=$ref->getProperty('tempDocumentMainPart');$prop->setAccessible(true);$xml=$prop->getValue($processor);$xml=str_replace(['{NHAP_NGU}}','{GHI_CHU}}'],['{{NHAP_NGU}}','{{GHI_CHU}}'],$xml);$prop->setValue($processor,$xml);$processor->setValue('DON_VI',(string)$request->input('don_vi',''));$processor->setValue('NOI_NHAN',(string)($request->input('noi_nhan')?:'Chưa nhập nơi nhận'));$ref=new \ReflectionClass($processor);$prop=$ref->getProperty('tempDocumentMainPart');$prop->setAccessible(true);$xml=$prop->getValue($processor);$pattern='~<w:tr\\b[^>]*>.*?\\{\\{STT\\}\\}.*?</w:tr>~s';preg_match($pattern,$xml,$match);$rowXml=$match[0]??'';$clones=[];for($i=1;$i<=max(1,$rows->count());$i++)$clones[]=preg_replace_callback('~\\{\\{([A-Z_]+)\\}\\}~',fn($m)=>'{{'.$m[1].'#'.$i.'}}',$rowXml);if($rowXml)$xml=str_replace($rowXml,implode('',$clones),$xml);$prop->setValue($processor,$xml);foreach($rows as $index=>$item){$n=$index+1;$p=$item->personnel;$values=['STT'=>$n,'HO_TEN'=>$item->personnel_name?:$p?->name,'NHAP_NGU'=>$item->enlistment_date?->format('d/m/Y'),'CAP_BAC'=>$item->rank?:$p?->rank,'CHUC_VU_DON_VI'=>trim(($item->position?:$p?->position?:'').' / '.($item->unit_name?:$p?->unit?:'')),'THOI_GIAN_NGHI'=>($item->from_date?->format('d/m/Y').' - '.$item->to_date?->format('d/m/Y')),'NGUOI_THAY_THE'=>$item->replacement_personnel_name,'GHI_CHU'=>$item->note?:$item->reason];foreach($values as $key=>$value)$processor->setValue($key.'#'.$n,(string)($value?:'—'));}$path=storage_path('app/report-phep-'.now()->format('YmdHis').'.docx');$processor->saveAs($path);return response()->download($path,'bao-cao-nghi-phep-'.$year.'.docx')->deleteFileAfterSend(true);
        }
        $word=new \PhpOffice\PhpWord\PhpWord();$word->setDefaultFontName('Arial');$section=$word->addSection(['marginLeft'=>720,'marginRight'=>720,'marginTop'=>500,'marginBottom'=>500]);
        $center=['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER];$bold=['bold'=>true];$underBold=['bold'=>true,'underline'=>'single'];$italic=['italic'=>true];$small=['size'=>10];$headerStyle=['bold'=>true,'size'=>9];$cellStyle=['size'=>8];$border=['borderSize'=>6,'borderColor'=>'000000'];
        $top=$section->addTable(['borderSize'=>0,'borderColor'=>'FFFFFF','borderInsideH'=>0,'borderInsideV'=>0,'cellMargin'=>40]);$top->addRow();$left=$top->addCell(4300);$left->addText('TRƯỜNG CAO ĐẲNG HẬU CẦN 2',$bold,$center);$left->addText($selectedUnit?->name?:$request->input('don_vi',''),$underBold,$center);$right=$top->addCell(6800);$right->addText('CỘNG HOÀ XÃ HỘI CHỦ NGHĨA VIỆT NAM',$bold,$center);$right->addText('Độc lập – Tự do – Hạnh phúc',$underBold,$center);$right->addText('Thành phố Hồ Chí Minh, ngày '.now()->format('d').' tháng '.now()->format('m').' năm '.now()->format('Y'),$italic,$center);
        $section->addText('DANH SÁCH',$bold,$center);$section->addText('ĐĂNG KÍ NGHỈ PHÉP (TRANH THỦ)',$bold,$center);
        $table=$section->addTable($border+['alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER,'cellMargin'=>45]);$header=$table->addRow();
        foreach(['TT','Họ và tên','Nhập ngũ','C.bậc','C.vụ / Đơn vị','Nơi nghỉ (Xã, Tỉnh)','Thời gian nghỉ','Người thay thế','Ghi chú'] as $head)$header->addCell(1200)->addText($head,$headerStyle,$center);
        $rows=LeaveRequest::with('personnel')->where('status','APPROVED')->when($unitId,fn($q,$id)=>$q->where(fn($x)=>$x->where('unit_id',$id)->orWhere('unit_name',$selectedUnit?->name)->orWhereHas('personnel',fn($p)=>$p->where('unit_id',$id)->orWhere('unit',$selectedUnit?->name))))->whereYear('from_date',$year)->latest()->get();$types=['ANNUAL'=>'Phép hàng năm','SICK'=>'Nghỉ ốm','PERSONAL'=>'Nghỉ việc riêng'];
        foreach($rows as $index=>$item){$p=$item->personnel;$row=$table->addRow();foreach([$index+1,$item->personnel_name?:$p?->name,$item->enlistment_date?->format('d/m/Y'),$item->rank?:$p?->rank,trim(($item->position?:$p?->position?:'').' '.($item->unit_name?:$p?->unit?:'')),$item->locality_path?:'—',($item->from_date?->format('d/m/Y').' - '.$item->to_date?->format('d/m/Y')),$item->replacement_personnel_name?:'—',$item->note?:$item->reason?:'—'] as $value)$row->addCell(1200)->addText((string)($value??'—'),$cellStyle,$center);}
        if($rows->isEmpty())$table->addRow()->addCell(10800)->addText('Không có dữ liệu trong năm '.$year.'.',$cellStyle,$center);
        $footer=$section->addTable(['borderSize'=>0,'borderColor'=>'FFFFFF','borderInsideH'=>0,'borderInsideV'=>0,'cellMargin'=>40]);$footer->addRow();$receive=$footer->addCell(7000);$receive->addText('Nơi nhận:');$receive->addText('- '.($request->input('noi_nhan')?:'Chưa nhập nơi nhận'));$sign=$footer->addCell(4000);$sign->addText('CHỈ HUY ĐƠN VỊ',$bold,$center);$sign->addText('(Ký, ghi rõ họ tên)',$small,$center);
        $path=storage_path('app/report-phep-'.now()->format('YmdHis').'.docx');(new \PhpOffice\PhpWord\Writer\Word2007($word))->save($path);return response()->download($path,'bao-cao-nghi-phep-'.$year.'.docx')->deleteFileAfterSend(true);
    }
}
