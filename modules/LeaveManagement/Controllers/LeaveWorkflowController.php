<?php
namespace Modules\LeaveManagement\Controllers;
use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
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
    public function personnel(){return view('leave-management::feature',['section'=>'personnel','title'=>'Quân nhân / nhân sự','items'=>LeavePersonnel::with(['user','unitRelation'])->where('active',true)->orderBy('name')->get(),'users'=>\App\Models\User::where('status',1)->orderBy('name')->get(),'units'=>\Modules\Unit\Models\Unit::active()->orderBy('name')->get(),'objects'=>LeaveObjectType::where('active',true)->orderBy('sort_order')->get()]);}
    public function personnelUpdate(Request $request, LeavePersonnel $personnel){$personnel->update($request->validate(['staff_code'=>'nullable|string|max:80','name'=>'required|string|max:255','position'=>'nullable|string|max:255','object_type'=>'nullable|string|max:50','rank'=>'nullable|string|max:80','unit'=>'nullable|string|max:255','email'=>'nullable|email|max:255','enlistment_date'=>'nullable|date','hometown'=>'nullable|string|max:255','permanent_residence'=>'nullable|string|max:255']));return back()->with('success','Đã cập nhật nhân sự.');}
    public function personnelDelete(LeavePersonnel $personnel){if($personnel->requests()->exists())return back()->withErrors(['personnel'=>'Không thể xóa nhân sự đã có đơn phép.']);$personnel->delete();return back()->with('success','Đã xóa nhân sự.');}
    public function directory(){return view('leave-management::feature',['section'=>'directory','title'=>'Danh sách nghỉ phép','items'=>LeavePersonnel::with(['unitRelation'])->where('active',true)->withCount(['requests as approved_leave_days'=>function($q){$q->where('status','APPROVED')->whereYear('from_date',now()->year);}])->orderBy('name')->get()]);}
    public function units(){return view('leave-management::feature',['section'=>'units','title'=>'Đơn vị quản lý phép','items'=>\Modules\Unit\Models\Unit::active()->withCount('instructors')->orderBy('name')->get()]);}
    public function requests(){
        $user=request()->user();
        $personnel=LeaveAccess::personnel(LeavePersonnel::where('active',true),$user)->with(['unitRelation','leaveClass'])->orderBy('name')->get();
        $classes=LeaveClass::with(['unit','personnel'=>fn($q)=>$q->where('active',true)->orderBy('name')])->where('active',true)->when(LeaveAccess::isScoped($user),fn($q)=>$q->whereIn('unit_id',LeaveAccess::unitIds($user)))->orderBy('name')->get();
        $localities=LeaveLocality::orderBy('name')->get();
        $extraStandards=LeaveRegulation::where('leave_type','EXTRA')->where('active',true)->orderBy('sort_order')->orderBy('id')->get()->map(function($rule){$rule->days=$rule->base_days;$rule->label=$rule->description?:$rule->label;return $rule;});
        return view('leave-management::feature',['section'=>'requests','title'=>'Đề xuất nghỉ phép','items'=>LeaveAccess::requests(LeaveRequest::with('personnel'),$user)->latest()->get(),'personnel'=>$personnel,'replacementPersonnel'=>$personnel,'classes'=>$classes,'localities'=>$localities,'extraStandards'=>$extraStandards,'regulations'=>LeaveRegulation::where('active',true)->get()]);
    }
    public function requestDetail(LeaveRequest $leaveRequest){return view('leave-management::feature',['section'=>'request-detail','title'=>'Chi tiết đơn phép #'.$leaveRequest->id,'request'=>$leaveRequest->load('personnel'),'audit'=>LeaveAuditLog::where('entity_type','request')->where('entity_id',$leaveRequest->id)->with('user')->latest()->get()]);}
    public function approvals(){
        $user=request()->user();
        $items=LeaveRequest::with(['personnel','leaveClass'])->where(function($q)use($user){$q->where(fn($x)=>$x->where('status','PENDING_COMMANDER')->where('commander_user_id',$user->id))->orWhere('status','PENDING_AGENCY');})->when(!$user->isSuperAdmin()&&!$user->can('leave-management.approvals.approve')&&!$user->can('leave-management.approve'),fn($q)=>$q->whereRaw('1=0'))->latest()->get();
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
    public function batches(){return view('leave-management::feature',['section'=>'batches','title'=>'Đợt nghỉ','items'=>LeaveBatch::with('request.personnel')->latest()->get(),'requests'=>LeaveRequest::where('status','APPROVED')->latest()->get()]);}
    public function batchStore(Request $r){$d=$r->validate(['request_id'=>'required|exists:leave_requests,id','label'=>'required|string|max:255','start_date'=>'required|date','end_date'=>'required|date|after_or_equal:start_date','total_days'=>'required|integer|min:1','note'=>'nullable|string']);LeaveBatch::create($d+['created_by'=>$r->user()->id]);return back()->with('success','Đã tạo đợt nghỉ.');}
    public function batchUpdate(Request $r, LeaveBatch $batch){$batch->update($r->validate(['label'=>'required|string|max:255','start_date'=>'required|date','end_date'=>'required|date|after_or_equal:start_date','total_days'=>'required|integer|min:1','note'=>'nullable|string']));return back()->with('success','Đã cập nhật đợt nghỉ.');}
    public function batchDelete(LeaveBatch $batch){$batch->delete();return back()->with('success','Đã xóa đợt nghỉ.');}
    public function records(){return view('leave-management::feature',['section'=>'records','title'=>'Lưu trữ phép','items'=>LeaveRecord::with('request.personnel')->latest()->get()]);}
    public function auditLogs(){return view('leave-management::feature',['section'=>'audit','title'=>'Nhật ký quản lý phép','items'=>LeaveAuditLog::with('user')->latest()->limit(200)->get()]);}
    public function alerts(Request $request){$alerts=LeaveAlert::with('request.personnel')->where('user_id',$request->user()->id)->latest()->paginate(30);return view('leave-management::feature',['section'=>'alerts','title'=>'Thông báo quản lý phép','items'=>$alerts]);}
    public function alertRead(Request $request, LeaveAlert $alert){abort_unless((int)$alert->user_id===(int)$request->user()->id,403);$alert->update(['read_at'=>now()]);return back();}
    public function archiveRecord(LeaveRecord $record){$record->update(['archived_at'=>now()]);return back()->with('success','Đã lưu trữ bản ghi phép.');}
    public function reports(Request $request){$year=(int)($request->input('year')?:now()->year);$taken=LeaveRequest::with(['personnel.unitRelation'])->where('status','APPROVED')->whereYear('from_date',$year)->latest()->get();$takenPersonnel=$taken->pluck('personnel_id')->filter()->unique();$notYet=LeavePersonnel::with('unitRelation')->where('active',true)->when($takenPersonnel->isNotEmpty(),fn($q)=>$q->whereNotIn('id',$takenPersonnel->all()))->orderBy('name')->get();$yearSummary=$taken->groupBy('personnel_id')->map(fn($rows)=>['personnel'=>$rows->first()->personnel,'days'=>$rows->sum('total_days'),'quota'=>$rows->max('base_days')]);return view('leave-management::feature',['section'=>'reports','title'=>'Báo cáo phép','year'=>$year,'taken'=>$taken,'notYet'=>$notYet,'yearSummary'=>$yearSummary,'pending'=>LeaveRequest::where('status','PENDING')->count(),'approved'=>LeaveRequest::where('status','APPROVED')->count(),'rejected'=>LeaveRequest::where('status','REJECTED')->count(),'days'=>LeaveRequest::where('status','APPROVED')->sum('total_days')]);}
    public function reportWord(Request $request){$year=(int)($request->input('year')?:now()->year);$word=new \PhpOffice\PhpWord\PhpWord();$word->setDefaultFontName('Arial');$section=$word->addSection();$section->addTitle('BÁO CÁO NGHỈ PHÉP '.$year,1);$section->addText('Ngày lập: '.now()->format('d/m/Y H:i'));$table=$section->addTable(['borderSize'=>6,'borderColor'=>'999999']);foreach(['STT','Nhân sự','Loại','Từ ngày','Đến ngày','Số ngày','Trạng thái'] as $head)$table->addCell(1600)->addText($head);foreach(LeaveRequest::with('personnel')->whereYear('from_date',$year)->latest()->get() as $index=>$item){$row=$table->addRow();foreach([$index+1,$item->personnel?->name,$item->leave_type,$item->from_date?->format('d/m/Y'),$item->to_date?->format('d/m/Y'),$item->total_days,$item->status] as $value)$row->addCell(1600)->addText((string)$value);} $path=storage_path('app/report-phep-'.now()->format('YmdHis').'.docx');(new \PhpOffice\PhpWord\Writer\Word2007($word))->save($path);return response()->download($path,'bao-cao-nghi-phep-'.$year.'.docx')->deleteFileAfterSend(true);}
    public function reportCsv(Request $request){$year=(int)($request->input('year')?:now()->year);$rows=LeaveRequest::with('personnel')->whereYear('from_date',$year)->latest()->get();$callback=function()use($rows){$out=fopen('php://output','w');fputcsv($out,['STT','Nhan su','Loai','Tu ngay','Den ngay','So ngay','Trang thai']);foreach($rows as $i=>$item)fputcsv($out,[$i+1,$item->personnel?->name,$item->leave_type,$item->from_date?->format('d/m/Y'),$item->to_date?->format('d/m/Y'),$item->total_days,$item->status]);fclose($out);};return response()->streamDownload($callback,'bao-cao-nghi-phep-'.$year.'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);}
    public function classes(){return view('leave-management::feature',['section'=>'classes','title'=>'Lớp / đại đội','items'=>LeaveClass::with('unit')->where('active',true)->when(LeaveAccess::isScoped(request()->user()),fn($q)=>$q->whereIn('unit_id',LeaveAccess::unitIds(request()->user())))->orderBy('name')->get(),'units'=>\Modules\Unit\Models\Unit::active()->when(LeaveAccess::isScoped(request()->user()),fn($q)=>$q->whereIn('id',LeaveAccess::unitIds(request()->user())))->orderBy('name')->get()]);}
    public function classStore(Request $request){$data=$request->validate(['unit_id'=>'required|exists:units,id','name'=>'required|string|max:255']);abort_unless(LeaveAccess::canAccessUnit((int)$data['unit_id'],$request->user()),403);LeaveClass::create($data);return back()->with('success','Đã thêm lớp.');}
    public function classDelete(LeaveClass $class){if($class->personnel()->exists()||LeaveRequest::where('class_id',$class->id)->exists())return back()->withErrors(['class'=>'Không thể xóa lớp đã có dữ liệu.']);$class->delete();return back()->with('success','Đã xóa lớp.');}
}
