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
use Modules\StandardHours\Models\Position;
class LeaveWorkflowController extends ModuleBaseController {
    public function __construct(){
        $this->middleware('permission:leave-management.personnel.index|leave-management.personnel.show|leave-management.show')->only(['personnel','directory']);
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
    public function personnel(){ $users=\App\Models\User::where('status',1)->orderBy('name')->get(); $positions=Position::active()->orderBy('name')->get(); if($positions->isEmpty())$positions=LeavePosition::orderBy('sort_order')->orderBy('name')->get(); return view('leave-management::feature',['section'=>'personnel','title'=>'Quân nhân / nhân sự','items'=>LeavePersonnel::with(['user','unitRelation','commander'])->where('active',true)->orderBy('name')->get(),'users'=>$users,'units'=>\Modules\Unit\Models\Unit::active()->orderBy('name')->get(),'objects'=>LeaveObjectType::where('active',true)->orderBy('sort_order')->get(),'positions'=>$positions]);}
    public function syncPersonnelPositions(Request $request){$count=0;$personnel=LeavePersonnel::with('user.position')->whereNotNull('user_id')->where('active',true)->get();foreach($personnel as $person){$account=$person->user;$positionId=$account?->position_id;$positionName=$account?->position?->name;if((int)$person->position_id!==(int)$positionId||$person->position!==$positionName){$person->update(['position_id'=>$positionId,'position'=>$positionName]);$count++;}}return back()->with('success',"Đã đồng bộ chức vụ cho {$count} hồ sơ quân nhân từ Dashboard.");}
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
        $user=request()->user();
        abort_unless(PermissionCheck::isLeaveAgency($user) || LeaveAccess::canApprove($user) || LeaveAccess::canHeadSign($user),403,'Chỉ tài khoản Cơ quan cán bộ, Quân lực hoặc thủ trưởng được in giấy nghỉ phép.');
        $leaveRequest->forceFill(['printed_at'=>now()])->save();
        LeaveAuditLog::create(['user_id'=>$user->id,'action'=>'PRINT','entity_type'=>'request','entity_id'=>$leaveRequest->id,'details'=>['printed_at'=>now()->toDateTimeString()]]);
        return view('leave-management::print-request',['request'=>$leaveRequest->load(['personnel','commander'])]);
    }
    public function approvals(){
        $user=request()->user();
         $agency=LeaveAccess::agencyForUser($user); $isUnitManager=LeaveAccess::isCommanderAccount($user); $isHeadSigner=LeaveAccess::canHeadSign($user); $items=LeaveRequest::with(['personnel','leaveClass'])->where(function($q)use($user,$agency,$isHeadSigner){$q->where(fn($x)=>$x->where('status','PENDING_COMMANDER')->where('commander_user_id',$user->id))->orWhere(fn($x)=>$x->where('status','PENDING_AGENCY')->when(!$user->isSuperAdmin(),fn($y)=>$y->where('managing_agency',$agency ?: '__NONE__')));if($isHeadSigner)$q->orWhere('status','PENDING_HEAD');})->when(!$user->isSuperAdmin()&&!$isUnitManager&&!$isHeadSigner&&!PermissionCheck::can($user,'leave-management.approvals.approve')&&!PermissionCheck::can($user,'leave-management.approve'),fn($q)=>$q->whereRaw('1=0'))->latest()->get();
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
    public function records(Request $request){
        $user=$request->user();
        $year=(int)($request->input('year')?:now()->year);
        $unitId=$request->input('unit_id')?(int)$request->input('unit_id'):null;
        $selectedUnit=$unitId?\Modules\Unit\Models\Unit::find($unitId):null;
        $keyword=trim((string)$request->input('q',''));
        $scopedUnitIds=LeaveAccess::isScoped($user)?LeaveAccess::unitIds($user):[];
        if($unitId&&$scopedUnitIds)abort_unless(in_array($unitId,$scopedUnitIds,true),403);
        $scopeFilter=function($q)use($scopedUnitIds){if($scopedUnitIds)$q->where(function($x)use($scopedUnitIds){$x->whereIn('unit_id',$scopedUnitIds)->orWhereHas('personnel',fn($p)=>$p->whereIn('unit_id',$scopedUnitIds));});};
        $unitFilter=function($q)use($unitId,$selectedUnit){if($unitId)$q->where(function($x)use($unitId,$selectedUnit){$x->where('unit_id',$unitId)->orWhere('unit_name',$selectedUnit?->name)->orWhereHas('personnel',fn($p)=>$p->where('unit_id',$unitId)->orWhere('unit',$selectedUnit?->name));});};
        $nameFilter=function($q)use($keyword){if($keyword!=='')$q->where(function($x)use($keyword){$x->where('personnel_name','like','%'.$keyword.'%')->orWhere('personnel_code','like','%'.$keyword.'%')->orWhereHas('personnel',fn($p)=>$p->where('name','like','%'.$keyword.'%')->orWhere('staff_code','like','%'.$keyword.'%'));});};
        $units=\Modules\Unit\Models\Unit::active()->when(LeaveAccess::isScoped($user),fn($q)=>$q->whereIn('id',LeaveAccess::unitIds($user)))->orderBy('name')->get();
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
    public function alertRead(Request $request, LeaveAlert $alert){abort_unless((int)$alert->user_id===(int)$request->user()->id,403);$alert->update(['read_at'=>now()]);return back();}
    public function archiveRecord(LeaveRecord $record){$record->update(['archived_at'=>now()]);return back()->with('success','Đã lưu trữ bản ghi phép.');}
    public function reports(Request $request){
        $year=(int)($request->input('year')?:now()->year);$today=now()->startOfDay();$agency=(string)$request->input('agency','');$unitId=$request->input('unit_id')?(int)$request->input('unit_id'):null;$selectedUnit=$unitId?\Modules\Unit\Models\Unit::find($unitId):null;$keyword=trim((string)$request->input('q',''));
        $agencyFilter=function($q)use($agency){if(in_array($agency,[LeaveAccess::QUAN_LUC,LeaveAccess::CO_QUAN_CAN_BO],true))$q->where(function($x)use($agency){$x->where('managing_agency',$agency)->orWhereHas('personnel',fn($p)=>$p->where('managing_agency',$agency));});};
        $unitFilter=function($q)use($unitId,$selectedUnit){if($unitId)$q->where(function($x)use($unitId,$selectedUnit){$x->where('unit_id',$unitId)->orWhere('unit_name',$selectedUnit?->name)->orWhereHas('personnel',fn($p)=>$p->where('unit_id',$unitId)->orWhere('unit',$selectedUnit?->name));});};
        $personUnitFilter=function($q)use($unitId,$selectedUnit){if($unitId)$q->where(function($x)use($unitId,$selectedUnit){$x->where('unit_id',$unitId)->orWhere('unit',$selectedUnit?->name);});};
        $nameFilter=function($q)use($keyword){if($keyword!=='')$q->where(function($x)use($keyword){$x->where('personnel_name','like','%'.$keyword.'%')->orWhere('personnel_code','like','%'.$keyword.'%')->orWhereHas('personnel',fn($p)=>$p->where('name','like','%'.$keyword.'%')->orWhere('staff_code','like','%'.$keyword.'%'));});};
        $personNameFilter=function($q)use($keyword){if($keyword!=='')$q->where(function($x)use($keyword){$x->where('name','like','%'.$keyword.'%')->orWhere('staff_code','like','%'.$keyword.'%');});};
        $approved=LeaveRequest::with(['personnel.unitRelation'])->where('status','APPROVED')->whereYear('from_date',$year)->when($agency,$agencyFilter)->when($unitId,$unitFilter)->when($keyword,$nameFilter)->latest()->get()->map(function($item)use($today){$start=$item->from_date?->copy()->startOfDay();$end=$item->to_date?->copy()->startOfDay();$item->days_used=($start&&!$today->lt($start))?min((int)$item->total_days,$end&&!$today->gte($end)?$start->diffInDays($today)+1:(int)$item->total_days):0;$item->days_remaining=$end&&!$today->gte($end)?$today->diffInDays($end):0;return $item;});
        $registered=LeaveRequest::with(['personnel.unitRelation'])->whereYear('from_date',$year)->whereNotIn('status',['REJECTED'])->when($agency,$agencyFilter)->when($unitId,$unitFilter)->when($keyword,$nameFilter)->latest()->get();
        $taken=$approved->filter(fn($item)=>$item->from_date&&!$today->lt($item->from_date->copy()->startOfDay()))->values();$usedPersonnel=$taken->pluck('personnel_id')->filter()->unique();
        $notYet=LeavePersonnel::with(['unitRelation','requests'])->where('active',true)->when(in_array($agency,[LeaveAccess::QUAN_LUC,LeaveAccess::CO_QUAN_CAN_BO],true),fn($q)=>$q->where('managing_agency',$agency))->when($unitId,$personUnitFilter)->when($keyword,$personNameFilter)->when($usedPersonnel->isNotEmpty(),fn($q)=>$q->whereNotIn('id',$usedPersonnel->all()))->orderBy('name')->get();
        $yearSummary=$approved->groupBy('personnel_id')->map(fn($rows)=>['personnel'=>$rows->first()->personnel,'days'=>$rows->sum('total_days'),'quota'=>$rows->max('base_days')]);
        $countBase=fn()=>LeaveRequest::query()->whereYear('from_date',$year)->when($agency,$agencyFilter)->when($unitId,$unitFilter)->when($keyword,$nameFilter);
        return view('leave-management::feature',['section'=>'reports','title'=>'Báo cáo phép','year'=>$year,'taken'=>$taken,'notYet'=>$notYet,'yearSummary'=>$yearSummary,'comparison'=>$approved,'registered'=>$registered,'pending'=>$countBase()->whereIn('status',['PENDING','PENDING_COMMANDER','PENDING_AGENCY','PENDING_HEAD','RETURNED'])->count(),'approved'=>$countBase()->where('status','APPROVED')->count(),'rejected'=>$countBase()->where('status','REJECTED')->count(),'days'=>$approved->sum('total_days')]);
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
        $agency=(string)$request->input('agency',LeaveAccess::QUAN_LUC);
        $keyword=trim((string)$request->input('q',''));
        if(!in_array($reportType,['used','unused','tracking','registered'],true))$reportType='used';
        if(!in_array($agency,[LeaveAccess::CO_QUAN_CAN_BO,LeaveAccess::QUAN_LUC],true))$agency=LeaveAccess::QUAN_LUC;

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

        $word=new \PhpOffice\PhpWord\PhpWord();$word->setDefaultFontName('Times New Roman');$word->setDefaultFontSize(12);
        $section=$word->addSection(['orientation'=>'landscape','pageSizeW'=>15840,'pageSizeH'=>12240,'marginLeft'=>1701,'marginRight'=>851,'marginTop'=>567,'marginBottom'=>567]);
        $center=['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER];$left=['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::LEFT];$bold=['bold'=>true];$italic=['italic'=>true];$title=['bold'=>true,'size'=>13];$cellFont=['size'=>12];$headerFont=['bold'=>true,'size'=>12];$border=['borderSize'=>6,'borderColor'=>'000000','cellMargin'=>60];$cell=['valign'=>'center'];$noBorder=['borderSize'=>0,'borderColor'=>'FFFFFF','borderInsideH'=>0,'borderInsideV'=>0,'cellMargin'=>40];
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
        foreach($grouped as $unitName=>$unitRows){$groupRow=$table->addRow();foreach($widths as $i=>$w)$groupRow->addCell($w,$cell)->addText($i===1?$unitName:'',$bold,$i===1?$left:$center);foreach($unitRows->values() as $i=>$item){$row=$table->addRow();$values=$reportType==='unused'?[$i+1,$item['name'],$item['rank'],$item['enlistment'],$item['hometown'],$item['permanent'],$item['note']]:($reportType==='tracking'?[$i+1,$item['name'],$item['rank'],$item['enlistment'],$item['total'],$item['used'],$item['remaining'],$item['place'],$item['reason']]:[$i+1,$item['name'],$item['rank'],$item['enlistment'],$item['from'],$item['to'],$item['place'],$item['reason']??$item['note']]);foreach($values as $j=>$value)$row->addCell($widths[$j],$cell)->addText((string)($value!==''?$value:' '),$cellFont,$j===1?$left:$center);}}
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
}
