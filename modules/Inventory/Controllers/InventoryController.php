<?php
namespace Modules\Inventory\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\{InventoryAsset,InventoryAuditLog,InventoryCategory,InventoryMaterial,InventoryMovement,InventoryProposal,InventoryRoomUser};

class InventoryController extends ModuleBaseController
{
    protected bool $useGenericModulePermissions = false;
    private const IMPORT_HEADERS = ['Mã ngành','Tên ngành','Mã loại','Tên loại','Mã vật tư','Tên vật tư','Đơn vị tính','Số lượng','Số lượng tối thiểu','Đơn giá','Năm sản xuất','Năm sử dụng','Phân cấp','Tình trạng','Ngày mua','Ngày hết hạn bảo hành','Vị trí','Ghi chú','Mô tả'];
    private const IMPORT_SAMPLE_ROW = ['N01','Ngành ví dụ','L01','Loại ví dụ','VT-001','Ví dụ thiết bị','cái',1,0,0,2026,2026,'Cấp 1','NORMAL','','','','',''];

    private function assignedInventoryRoomIds($user = null): ?array
    {
        $user = $user ?: auth()->user();
        if (! $user || $user->isSuperAdmin()) {
            return null;
        }

        $roomIds = InventoryRoomUser::where('user_id', $user->id)->pluck('classroom_id')->map(fn($id) => (int) $id)->values()->all();

        return $roomIds ?: null;
    }

    private function scopedMaterialIds(?array $roomIds): ?array
    {
        if ($roomIds === null) {
            return null;
        }

        return InventoryAsset::whereIn('classroom_id', $roomIds)->whereNotNull('material_id')->distinct()->pluck('material_id')->map(fn($id) => (int) $id)->values()->all();
    }

    private function scopedCategoryIds(?array $materialIds): ?array
    {
        if ($materialIds === null) {
            return null;
        }

        $typeIds = InventoryMaterial::whereIn('id', $materialIds)->whereNotNull('category_id')->distinct()->pluck('category_id')->map(fn($id) => (int) $id);
        $industryIds = InventoryCategory::whereIn('id', $typeIds)->whereNotNull('parent_id')->distinct()->pluck('parent_id')->map(fn($id) => (int) $id);

        return $typeIds->merge($industryIds)->unique()->values()->all();
    }

    private function ensureInventoryMaterialAllowed(InventoryMaterial $material): void
    {
        $materialIds = $this->scopedMaterialIds($this->assignedInventoryRoomIds());
        abort_if($materialIds !== null && ! in_array((int) $material->id, $materialIds, true), 403, 'Tài khoản này không được gán vật tư này.');
    }

    public function portal(){ return view('portals.inventory'); }
    public function materials(Request $request)
    {
        $roomIds = $this->assignedInventoryRoomIds($request->user());
        $materialIds = $this->scopedMaterialIds($roomIds);
        $categoryIds = $this->scopedCategoryIds($materialIds);
        $industryId = $request->input('industry_id');
        $typeId = $request->input('category_id');
        if ($industryId && $typeId && ! InventoryCategory::whereKey($typeId)->where('parent_id', $industryId)->exists()) {
            $typeId = null;
        }

        $materials = InventoryMaterial::with('category')->withCount(['assets','warehouseItems','proposalItems','transfers','movements'])
            ->when($materialIds !== null, fn($q) => $q->whereIn('id', $materialIds))
            ->when($industryId, fn($q, $id) => $q->whereHas('category', fn($c) => $c->where('parent_id', $id)))
            ->when($typeId, fn($q, $id) => $q->where('category_id', $id))
            ->when($request->filled('search'), fn($q) => $q->where(fn($x) => $x->where('code', 'like', '%'.$request->search.'%')->orWhere('name', 'like', '%'.$request->search.'%')))
            ->latest()
            ->get();

        $assetGrades = InventoryAsset::query()
            ->select('material_id', 'grade', DB::raw('SUM(quantity) as quantity'))
            ->whereIn('material_id', $materials->pluck('id')->filter()->values())
            ->when($roomIds !== null, fn($q) => $q->whereIn('classroom_id', $roomIds))
            ->groupBy('material_id', 'grade')
            ->get()
            ->groupBy('material_id');

        $materialRows = $materials->flatMap(function (InventoryMaterial $material) use ($assetGrades) {
            $grades = $assetGrades->get($material->id, collect());
            if ($grades->isEmpty()) {
                return collect([(object) [
                    'material' => $material,
                    'quantity' => $material->quantity,
                    'classification' => $material->classification,
                ]]);
            }

            return $grades->sortBy('grade')->values()->map(fn($grade) => (object) [
                'material' => $material,
                'quantity' => (float) $grade->quantity,
                'classification' => $grade->grade ? 'Phân cấp '.$grade->grade : ($material->classification ?: null),
            ]);
        })->values();

        $industries = InventoryCategory::whereNull('parent_id')->when($categoryIds !== null, fn($q) => $q->whereIn('id', $categoryIds))->orderBy('code')->get();
        $allCategories = InventoryCategory::whereNotNull('parent_id')->when($categoryIds !== null, fn($q) => $q->whereIn('id', $categoryIds))->orderBy('code')->get();
        $categories = $allCategories->when($industryId, fn($items, $id) => $items->where('parent_id', $id))->values();

        return view('inventory::feature', ['section'=>'materials','title'=>'Danh sách vật tư','materials'=>$materials,'materialRows'=>$materialRows,'categories'=>$categories,'allCategories'=>$allCategories,'industries'=>$industries,'industryId'=>$industryId,'typeId'=>$typeId]);
    }
    public function materialShow(InventoryMaterial $material)
    {
        $this->ensureInventoryMaterialAllowed($material);
        $material->load(['category.parent','building','classroom','assets.classroom','warehouseItems','movements.user']);
        $material->loadCount(['assets','warehouseItems','proposalItems','transfers','movements']);

        return view('inventory::feature', ['section'=>'material-detail','title'=>'Chi tiết '.$material->name,'material'=>$material]);
    }
    public function importTemplate(){
        $spreadsheet=new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            self::IMPORT_HEADERS,
            self::IMPORT_SAMPLE_ROW,
        ]);
        $path=tempnam(storage_path('app'),'inventory-template-').'.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
        return response()->download($path,'mau-import-vat-tu.xlsx')->deleteFileAfterSend(true);
    }
    public function importTemplateWord(){
        $word=new \PhpOffice\PhpWord\PhpWord();
        $section=$word->addSection(['orientation'=>'landscape']);
        $section->addText('Mẫu import vật tư');
        $table=$section->addTable(['borderSize'=>6,'borderColor'=>'999999','cellMargin'=>80]);
        foreach([self::IMPORT_HEADERS,self::IMPORT_SAMPLE_ROW] as $row){
            $table->addRow();
            foreach($row as $cell)$table->addCell(1600)->addText((string)$cell);
        }
        $path=tempnam(storage_path('app'),'inventory-template-').'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($word,'Word2007')->save($path);
        return response()->download($path,'mau-import-vat-tu.docx')->deleteFileAfterSend(true);
    }
    public function index(Request $request){$roomIds=$this->assignedInventoryRoomIds($request->user());$materialIds=$this->scopedMaterialIds($roomIds);$categoryIds=$this->scopedCategoryIds($materialIds);$buildingIds=$roomIds===null?null:\Modules\Classroom\Models\Classroom::whereIn('id',$roomIds)->whereNotNull('building_id')->distinct()->pluck('building_id')->map(fn($id)=>(int)$id)->values()->all();$materials=InventoryMaterial::with(['category','building','classroom'])->withCount(['assets','warehouseItems','proposalItems','transfers','movements'])->when($materialIds!==null,fn($q)=>$q->whereIn('id',$materialIds))->when($request->search,fn($q,$s)=>$q->where(fn($x)=>$x->where('code','like',"%$s%")->orWhere('name','like',"%$s%")))->latest()->paginate(20)->withQueryString();$industries=InventoryCategory::whereNull('parent_id')->where('active',true)->when($categoryIds!==null,fn($q)=>$q->whereIn('id',$categoryIds))->orderBy('code')->get();$categories=InventoryCategory::whereNotNull('parent_id')->where('active',true)->when($categoryIds!==null,fn($q)=>$q->whereIn('id',$categoryIds))->orderBy('code')->get();$buildings=\Modules\Building\Models\Building::where('status',true)->when($buildingIds!==null,fn($q)=>$q->whereIn('id',$buildingIds))->orderBy('name')->get();$classrooms=\Modules\Classroom\Models\Classroom::active()->when($roomIds!==null,fn($q)=>$q->whereIn('id',$roomIds))->orderBy('name')->get();$stats=['materials'=>InventoryMaterial::when($materialIds!==null,fn($q)=>$q->whereIn('id',$materialIds))->count(),'quantity'=>InventoryMaterial::when($materialIds!==null,fn($q)=>$q->whereIn('id',$materialIds))->sum('quantity'),'pending'=>InventoryProposal::where('status','PENDING')->when($roomIds!==null,fn($q)=>$q->whereHas('items',fn($i)=>$i->whereIn('from_classroom_id',$roomIds)->orWhereIn('target_room_id',$roomIds)->orWhereHas('asset',fn($a)=>$a->whereIn('classroom_id',$roomIds))))->count()];return view('inventory::index',compact('materials','categories','industries','buildings','classrooms','stats'));}
    public function store(Request $request){$d=$request->validate(['industry_id'=>'nullable|exists:inventory_categories,id','category_id'=>'nullable|exists:inventory_categories,id','building_id'=>'nullable|exists:buildings,id','classroom_id'=>'nullable|exists:classrooms,id','code'=>'required|string|max:80|unique:inventory_materials,code','name'=>'required|string|max:255','unit'=>'required|string|max:30','quantity'=>'required|integer|min:0','min_quantity'=>'nullable|integer|min:0','price'=>'nullable|numeric|min:0','status'=>'nullable|string|max:30','manufacture_year'=>'nullable|integer|min:1900|max:2200','usage_year'=>'nullable|integer|min:1900|max:2200','classification'=>'nullable|string|max:255','asset_status'=>'nullable|string|max:30','purchase_date'=>'nullable|date','expiry_date'=>'nullable|date','location'=>'nullable|string|max:255','note'=>'nullable|string','description'=>'nullable|string']);$roomIds=$this->assignedInventoryRoomIds($request->user());abort_if($roomIds!==null&&!empty($d['classroom_id'])&&!in_array((int)$d['classroom_id'],$roomIds,true),403,'Tài khoản này không được gán phòng vật tư này.');if(!empty($d['industry_id'])&&!empty($d['category_id'])&&!InventoryCategory::whereKey($d['category_id'])->where('parent_id',$d['industry_id'])->exists())return back()->withErrors(['category_id'=>'Loại vật tư không thuộc ngành vật tư đã chọn.'])->withInput();unset($d['industry_id']);DB::transaction(function()use($d,$request){$m=InventoryMaterial::create($d);if((int)$d['quantity']>0)InventoryMovement::create(['material_id'=>$m->id,'type'=>'IN','quantity'=>$d['quantity'],'note'=>'Opening stock','created_by'=>$request->user()->id]);InventoryAuditLog::create(['user_id'=>$request->user()->id,'action'=>'CREATE','entity_type'=>'material','entity_id'=>$m->id,'details'=>$d]);});return back()->with('success','Đã thêm vật tư.');}
    public function import(Request $request){$d=$request->validate(['category_id'=>'nullable|exists:inventory_categories,id','update_type'=>'nullable|in:IN,OUT','reason'=>'nullable|string|max:255','file'=>'required|file|mimes:xlsx,xls,csv,txt,docx|max:20480']);$d['update_type']=$d['update_type']??'IN';$d['reason']=$d['reason']??'Import vật tư';[$headers,$rows]=$this->readImportRows($request->file('file'));$headers=array_map(fn($v)=>$this->normalizeImportHeader($v),$headers);$missing=array_diff(['code','name'],array_filter($headers));abort_if($missing,422,'File import thiếu cột bắt buộc: '.implode(', ',$missing).'.');abort_if(empty($d['category_id'])&&!array_intersect(['category_code','category_name'],$headers),422,'File import cần có cột Mã loại hoặc Tên loại để xác định loại vật tư.');$count=0;DB::transaction(function()use($rows,$headers,$request,$d,&$count){foreach($rows as $index=>$row){$line=$index+2;$v=[];foreach($headers as $key=>$header){if($header!=='')$v[$header]=trim((string)($row[$key]??''));}if(($v['code']??'')===''&&($v['name']??'')==='')continue;abort_if(($v['code']??'')===''||($v['name']??'')==='',422,"Dòng {$line} thiếu mã hoặc tên vật tư.");$cat=!empty($d['category_id'])?InventoryCategory::find($d['category_id']):$this->resolveImportCategory($v);abort_unless($cat,422,"Dòng {$line} chưa xác định được loại vật tư. Hãy nhập Mã loại hoặc Tên loại đúng với danh mục hiện có.");$payload=['category_id'=>$cat->id,'name'=>$v['name'],'unit'=>$v['unit']??'cái','quantity'=>(float)($v['quantity']??0),'min_quantity'=>(float)($v['min_quantity']??0),'price'=>(float)($v['price']??0),'status'=>$v['status']??'ACTIVE','manufacture_year'=>($v['manufacture_year']??null)?(int)$v['manufacture_year']:null,'usage_year'=>($v['usage_year']??null)?(int)$v['usage_year']:null,'classification'=>$v['classification']??null,'asset_status'=>$v['asset_status']??'NORMAL','purchase_date'=>$v['purchase_date']??null,'expiry_date'=>$v['expiry_date']??null,'location'=>$v['location']??null,'note'=>$v['note']??null,'description'=>$v['description']??null];$m=InventoryMaterial::updateOrCreate(['code'=>$v['code']],$payload);if((float)$payload['quantity']>0)InventoryMovement::create(['material_id'=>$m->id,'type'=>$d['update_type'],'quantity'=>$payload['quantity'],'note'=>$d['reason'],'created_by'=>$request->user()->id]);InventoryAuditLog::create(['user_id'=>$request->user()->id,'action'=>'IMPORT','entity_type'=>'material','entity_id'=>$m->id,'details'=>$payload+['code'=>$v['code'],'industry_code'=>$v['industry_code']??null,'category_code'=>$v['category_code']??null,'update_type'=>$d['update_type'],'reason'=>$d['reason']]]);$count++;}});return back()->with('success',"Đã import {$count} dòng vật tư.");}
    private function normalizeImportHeader($header): string
    {
        $key = mb_strtolower(trim((string) $header));
        $map = [
            'mã vật tư' => 'code', 'ma vat tu' => 'code', 'code' => 'code',
            'tên vật tư' => 'name', 'ten vat tu' => 'name', 'name' => 'name',
            'đơn vị tính' => 'unit', 'don vi tinh' => 'unit', 'unit' => 'unit',
            'số lượng' => 'quantity', 'so luong' => 'quantity', 'quantity' => 'quantity',
            'số lượng tối thiểu' => 'min_quantity', 'so luong toi thieu' => 'min_quantity', 'min_quantity' => 'min_quantity',
            'đơn giá' => 'price', 'don gia' => 'price', 'price' => 'price',
            'mã ngành' => 'industry_code', 'ma nganh' => 'industry_code', 'industry_code' => 'industry_code',
            'tên ngành' => 'industry_name', 'ten nganh' => 'industry_name', 'industry_name' => 'industry_name',
            'mã loại' => 'category_code', 'ma loai' => 'category_code', 'category_code' => 'category_code',
            'tên loại' => 'category_name', 'ten loai' => 'category_name', 'category_name' => 'category_name',
            'năm sản xuất' => 'manufacture_year', 'nam san xuat' => 'manufacture_year', 'manufacture_year' => 'manufacture_year',
            'năm sử dụng' => 'usage_year', 'nam su dung' => 'usage_year', 'usage_year' => 'usage_year',
            'phân cấp' => 'classification', 'phan cap' => 'classification', 'classification' => 'classification',
            'tình trạng' => 'asset_status', 'tinh trang' => 'asset_status', 'asset_status' => 'asset_status',
            'trạng thái' => 'status', 'trang thai' => 'status', 'status' => 'status',
            'ngày mua' => 'purchase_date', 'ngay mua' => 'purchase_date', 'purchase_date' => 'purchase_date',
            'ngày hết hạn bảo hành' => 'expiry_date', 'ngay het han bao hanh' => 'expiry_date', 'expiry_date' => 'expiry_date',
            'vị trí' => 'location', 'vi tri' => 'location', 'location' => 'location',
            'ghi chú' => 'note', 'ghi chu' => 'note', 'note' => 'note',
            'mô tả' => 'description', 'mo ta' => 'description', 'description' => 'description',
        ];

        return $map[$key] ?? $key;
    }
    private function resolveImportCategory(array $values): ?InventoryCategory
    {
        $industry = null;
        if (($values['industry_code'] ?? '') !== '') {
            $industry = InventoryCategory::whereNull('parent_id')->where('code', $values['industry_code'])->first();
        }
        if (! $industry && ($values['industry_name'] ?? '') !== '') {
            $industry = InventoryCategory::whereNull('parent_id')->where('name', $values['industry_name'])->first();
        }

        $query = InventoryCategory::query()->whereNotNull('parent_id');
        if ($industry) {
            $query->where('parent_id', $industry->id);
        }
        if (($values['category_code'] ?? '') !== '') {
            return (clone $query)->where('code', $values['category_code'])->first();
        }
        if (($values['category_name'] ?? '') !== '') {
            return (clone $query)->where('name', $values['category_name'])->first();
        }

        return null;
    }
    private function readImportRows($file): array
    {
        $extension=strtolower($file->getClientOriginalExtension());
        if($extension==='docx')return $this->readDocxImportRows($file->getRealPath());
        $rows=\PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath())->getActiveSheet()->toArray(null,true,true,false);
        return [array_shift($rows)?:[], $rows];
    }
    private function readDocxImportRows(string $path): array
    {
        abort_unless(class_exists(\ZipArchive::class),500,'Máy chủ chưa bật ZipArchive nên chưa đọc được file Word .docx.');
        $zip=new \ZipArchive();
        abort_unless($zip->open($path)===true,422,'Không mở được file Word .docx.');
        $xml=$zip->getFromName('word/document.xml');
        $zip->close();
        abort_unless(is_string($xml)&&$xml!=='',422,'File Word .docx không có nội dung hợp lệ.');
        $dom=new \DOMDocument();
        $dom->loadXML($xml,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
        $xpath=new \DOMXPath($dom);
        $xpath->registerNamespace('w','http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $rows=[];
        foreach($xpath->query('//w:tbl[1]/w:tr') as $tr){
            $row=[];
            foreach($xpath->query('./w:tc',$tr) as $tc){
                $parts=[];
                foreach($xpath->query('.//w:t',$tc) as $text)$parts[]=$text->textContent;
                $row[]=trim(implode(' ',$parts));
            }
            if(implode('',array_map('trim',$row))!=='')$rows[]=$row;
        }
        abort_unless(count($rows)>1,422,'File Word cần có bảng import: dòng đầu là tiêu đề cột, các dòng sau là dữ liệu.');
        return [array_shift($rows), $rows];
    }
    public function movement(Request $request,InventoryMaterial $material){$this->ensureInventoryMaterialAllowed($material);$d=$request->validate(['type'=>'required|in:IN,OUT,ADJUST','quantity'=>'required|numeric|min:.01','reference'=>'nullable|string|max:255','note'=>'nullable|string']);DB::transaction(function()use($d,$material,$request){$change=$d['type']==='OUT'?-abs($d['quantity']):($d['type']==='IN'?abs($d['quantity']):$d['quantity']-(float)$material->quantity);abort_if($d['type']==='OUT'&&(float)$material->quantity<abs($change),422,'Số lượng xuất vượt tồn kho.');$material->increment('quantity',$change);$movement=InventoryMovement::create($d+['material_id'=>$material->id,'created_by'=>$request->user()->id]);InventoryAuditLog::create(['user_id'=>$request->user()->id,'action'=>'MOVEMENT','entity_type'=>'material','entity_id'=>$material->id,'details'=>$d+['movement_id'=>$movement->id,'balance_change'=>$change]]);});return back()->with('success','Đã cập nhật kho.');}
    public function update(Request $request,InventoryMaterial $material){$this->ensureInventoryMaterialAllowed($material);$d=$request->validate(['category_id'=>'nullable|exists:inventory_categories,id','building_id'=>'nullable|exists:buildings,id','classroom_id'=>'nullable|exists:classrooms,id','code'=>'required|string|max:80|unique:inventory_materials,code,'.$material->id,'name'=>'required|string|max:255','unit'=>'required|string|max:30','quantity'=>'required|integer|min:0','min_quantity'=>'nullable|integer|min:0','price'=>'nullable|numeric|min:0','status'=>'nullable|string|max:30','manufacture_year'=>'nullable|integer|min:1900|max:2200','usage_year'=>'nullable|integer|min:1900|max:2200','classification'=>'nullable|string|max:255','asset_status'=>'nullable|string|max:30','purchase_date'=>'nullable|date','expiry_date'=>'nullable|date','location'=>'nullable|string|max:255','note'=>'nullable|string','description'=>'nullable|string']);$roomIds=$this->assignedInventoryRoomIds($request->user());abort_if($roomIds!==null&&!empty($d['classroom_id'])&&!in_array((int)$d['classroom_id'],$roomIds,true),403,'Tài khoản này không được gán phòng vật tư này.');$before=(int)$material->quantity;$material->update($d);$material->refresh();$difference=(int)$material->quantity-$before;if($difference!==0)InventoryMovement::create(['material_id'=>$material->id,'type'=>$difference>0?'IN':'OUT','quantity'=>abs($difference),'note'=>'Cập nhật số lượng vật tư','created_by'=>$request->user()->id]);InventoryAuditLog::create(['user_id'=>$request->user()->id,'action'=>'UPDATE','entity_type'=>'material','entity_id'=>$material->id,'details'=>$d]);return back()->with('success','Đã cập nhật vật tư.');}
    public function destroy(Request $request,InventoryMaterial $material){$this->ensureInventoryMaterialAllowed($material);$blocked=[];if($material->warehouseItems()->exists())$blocked[]='vật tư trong kho';if($material->proposalItems()->exists())$blocked[]='phiếu đề xuất';if($material->transfers()->exists())$blocked[]='phiếu điều động';if($blocked)return back()->withErrors(['material'=>'Không thể xóa vật tư này vì đang có '.implode(', ',$blocked).'. Hãy xử lý hoặc xóa dữ liệu liên quan trước.']);DB::transaction(function()use($material,$request){$id=$material->id;$details=['code'=>$material->code,'name'=>$material->name,'room_assets_deleted'=>$material->assets()->count()];$material->assets()->delete();$material->delete();InventoryAuditLog::create(['user_id'=>$request->user()->id,'action'=>'DELETE','entity_type'=>'material','entity_id'=>$id,'details'=>$details]);});return back()->with('success','Đã xóa vật tư và các vật tư trong phòng liên quan.');}
}
