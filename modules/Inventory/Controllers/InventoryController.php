<?php
namespace Modules\Inventory\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\{InventoryAsset,InventoryAuditLog,InventoryCategory,InventoryMaterial,InventoryMovement,InventoryProposal,InventoryRoomUser};

class InventoryController extends ModuleBaseController
{
    protected bool $useGenericModulePermissions = false;
    private const INDUSTRY_IMPORT_HEADERS = ['Mã ngành','Tên ngành'];
    private const INDUSTRY_IMPORT_SAMPLE_ROW = ['N01','Ngành ví dụ'];
    private const CATEGORY_IMPORT_HEADERS = ['Mã loại vật tư','Tên loại vật tư'];
    private const CATEGORY_IMPORT_SAMPLE_ROW = ['L01','Loại ví dụ'];
    private const MATERIAL_IMPORT_HEADERS = ['Mã loại vật tư','Tên vật tư','Đơn vị tính'];
    private const MATERIAL_IMPORT_SAMPLE_ROW = ['L01','Ví dụ thiết bị','cái'];

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
    public function importTemplate(Request $request){
        [$title, $headers, $sample] = $this->importTemplateConfig($request->input('type', 'material'), $request->boolean('scoped'));
        $spreadsheet=new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            $headers,
            $sample,
        ]);
        $path=tempnam(storage_path('app'),'inventory-template-').'.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
        return response()->download($path,$title.'.xlsx')->deleteFileAfterSend(true);
    }
    public function importTemplateWord(Request $request){
        [$title, $headers, $sample] = $this->importTemplateConfig($request->input('type', 'material'), $request->boolean('scoped'));
        $word=new \PhpOffice\PhpWord\PhpWord();
        $section=$word->addSection(['orientation'=>'landscape']);
        $section->addText('Mẫu '.str_replace('-', ' ', $title));
        $table=$section->addTable(['borderSize'=>6,'borderColor'=>'999999','cellMargin'=>80]);
        foreach([$headers,$sample] as $row){
            $table->addRow();
            foreach($row as $cell)$table->addCell(1600)->addText((string)$cell);
        }
        $path=tempnam(storage_path('app'),'inventory-template-').'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($word,'Word2007')->save($path);
        return response()->download($path,$title.'.docx')->deleteFileAfterSend(true);
    }
    private function importTemplateConfig(string $type, bool $scoped = false): array
    {
        return match ($type) {
            'industry' => ['mau-import-nganh-vat-tu', self::INDUSTRY_IMPORT_HEADERS, self::INDUSTRY_IMPORT_SAMPLE_ROW],
            'category' => ['mau-import-loai-vat-tu', self::CATEGORY_IMPORT_HEADERS, self::CATEGORY_IMPORT_SAMPLE_ROW],
            default => $scoped
                ? ['mau-import-vat-tu-theo-loai', ['Tên vật tư','Đơn vị tính'], ['Ví dụ thiết bị','cái']]
                : ['mau-import-vat-tu', self::MATERIAL_IMPORT_HEADERS, self::MATERIAL_IMPORT_SAMPLE_ROW],
        };
    }
    public function index(Request $request){$roomIds=$this->assignedInventoryRoomIds($request->user());$materialIds=$this->scopedMaterialIds($roomIds);$categoryIds=$this->scopedCategoryIds($materialIds);$buildingIds=$roomIds===null?null:\Modules\Classroom\Models\Classroom::whereIn('id',$roomIds)->whereNotNull('building_id')->distinct()->pluck('building_id')->map(fn($id)=>(int)$id)->values()->all();$materials=InventoryMaterial::with(['category','building','classroom'])->withCount(['assets','warehouseItems','proposalItems','transfers','movements'])->when($materialIds!==null,fn($q)=>$q->whereIn('id',$materialIds))->when($request->search,fn($q,$s)=>$q->where(fn($x)=>$x->where('code','like',"%$s%")->orWhere('name','like',"%$s%")))->latest()->paginate(20)->withQueryString();$industries=InventoryCategory::whereNull('parent_id')->where('active',true)->when($categoryIds!==null,fn($q)=>$q->whereIn('id',$categoryIds))->orderBy('code')->get();$categories=InventoryCategory::whereNotNull('parent_id')->where('active',true)->when($categoryIds!==null,fn($q)=>$q->whereIn('id',$categoryIds))->orderBy('code')->get();$buildings=\Modules\Building\Models\Building::where('status',true)->when($buildingIds!==null,fn($q)=>$q->whereIn('id',$buildingIds))->orderBy('name')->get();$classrooms=\Modules\Classroom\Models\Classroom::active()->when($roomIds!==null,fn($q)=>$q->whereIn('id',$roomIds))->orderBy('name')->get();$stats=['materials'=>InventoryMaterial::when($materialIds!==null,fn($q)=>$q->whereIn('id',$materialIds))->count(),'quantity'=>InventoryMaterial::when($materialIds!==null,fn($q)=>$q->whereIn('id',$materialIds))->sum('quantity'),'pending'=>InventoryProposal::where('status','PENDING')->when($roomIds!==null,fn($q)=>$q->whereHas('items',fn($i)=>$i->whereIn('from_classroom_id',$roomIds)->orWhereIn('target_room_id',$roomIds)->orWhereHas('asset',fn($a)=>$a->whereIn('classroom_id',$roomIds))))->count()];return view('inventory::index',compact('materials','categories','industries','buildings','classrooms','stats'));}
    public function store(Request $request)
    {
        $d = $request->validate([
            'industry_id' => 'nullable|exists:inventory_categories,id',
            'category_id' => 'required|exists:inventory_categories,id',
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:30',
        ]);
        if (! empty($d['industry_id']) && ! InventoryCategory::whereKey($d['category_id'])->where('parent_id', $d['industry_id'])->exists()) {
            return back()->withErrors(['category_id' => 'Loại vật tư không thuộc ngành vật tư đã chọn.'])->withInput();
        }
        unset($d['industry_id']);
        $d['code'] = $this->nextMaterialCode((int) $d['category_id']);
        $m = InventoryMaterial::create($d);
        InventoryAuditLog::create(['user_id' => $request->user()->id, 'action' => 'CREATE', 'entity_type' => 'material', 'entity_id' => $m->id, 'details' => $d]);

        return back()->with('success', 'Đã thêm vật tư.');
    }
    public function import(Request $request)
    {
        $d = $request->validate([
            'import_type' => 'required|in:industry,category,material',
            'industry_id' => 'nullable|exists:inventory_categories,id',
            'category_id' => 'nullable|exists:inventory_categories,id',
            'file' => 'required|file|mimes:xlsx,xls,csv,txt,docx|max:20480',
        ]);
        [$headers, $rows] = $this->readImportRows($request->file('file'));
        $headers = array_map(fn ($v) => $this->normalizeImportHeader($v), $headers);

        $count = match ($d['import_type']) {
            'industry' => $this->importIndustries($rows, $headers, $request),
            'category' => $this->importCategories($rows, $headers, $request, $d['industry_id'] ?? null),
            'material' => $this->importMaterials($rows, $headers, $request, $d['category_id'] ?? null),
        };
        $labels = ['industry' => 'ngành vật tư', 'category' => 'loại vật tư', 'material' => 'vật tư'];

        return back()->with('success', "Đã import {$count} dòng {$labels[$d['import_type']]}.");
    }
    private function importIndustries(array $rows, array $headers, Request $request): int
    {
        $missing = array_diff(['industry_code', 'industry_name'], array_filter($headers));
        abort_if($missing, 422, 'File import ngành thiếu cột bắt buộc: '.implode(', ', $missing).'.');
        $count = 0;
        DB::transaction(function () use ($rows, $headers, $request, &$count) {
            foreach ($rows as $index => $row) {
                $line = $index + 2;
                $v = $this->importRowValues($headers, $row);
                if (($v['industry_code'] ?? '') === '' && ($v['industry_name'] ?? '') === '') continue;
                $industry = $this->resolveOrCreateImportIndustry($v, $line);
                InventoryAuditLog::create(['user_id' => $request->user()->id, 'action' => 'IMPORT', 'entity_type' => 'category', 'entity_id' => $industry->id, 'details' => ['type' => 'industry', 'code' => $industry->code, 'name' => $industry->name]]);
                $count++;
            }
        });
        return $count;
    }
    private function importCategories(array $rows, array $headers, Request $request, ?int $selectedIndustryId): int
    {
        $missing = array_diff($selectedIndustryId ? ['category_code', 'category_name'] : ['industry_code', 'category_code', 'category_name'], array_filter($headers));
        abort_if($missing, 422, 'File import loại vật tư thiếu cột bắt buộc: '.implode(', ', $missing).'.');
        $selectedIndustry = $selectedIndustryId ? InventoryCategory::whereNull('parent_id')->find($selectedIndustryId) : null;
        abort_if($selectedIndustryId && ! $selectedIndustry, 422, 'Ngành vật tư import không hợp lệ.');
        $count = 0;
        DB::transaction(function () use ($rows, $headers, $request, $selectedIndustry, &$count) {
            foreach ($rows as $index => $row) {
                $line = $index + 2;
                $v = $this->importRowValues($headers, $row);
                if (($v['category_code'] ?? '') === '' && ($v['category_name'] ?? '') === '') continue;
                if ($selectedIndustry) {
                    $v['industry_code'] = $selectedIndustry->code;
                    $v['industry_name'] = $selectedIndustry->name;
                }
                $category = $this->resolveOrCreateImportCategory($v, $line);
                InventoryAuditLog::create(['user_id' => $request->user()->id, 'action' => 'IMPORT', 'entity_type' => 'category', 'entity_id' => $category->id, 'details' => ['type' => 'category', 'code' => $category->code, 'name' => $category->name, 'industry_code' => $v['industry_code'] ?? null]]);
                $count++;
            }
        });
        return $count;
    }
    private function importMaterials(array $rows, array $headers, Request $request, ?int $selectedCategoryId): int
    {
        $missing = array_diff(['name', 'unit'], array_filter($headers));
        abort_if($missing, 422, 'File import vật tư thiếu cột bắt buộc: '.implode(', ', $missing).'.');
        abort_if(empty($selectedCategoryId) && ! in_array('category_code', $headers, true), 422, 'File import vật tư cần có cột Mã loại vật tư.');
        $count = 0;
        DB::transaction(function () use ($rows, $headers, $request, $selectedCategoryId, &$count) {
            foreach ($rows as $index => $row) {
                $line = $index + 2;
                $v = $this->importRowValues($headers, $row);
                if (($v['code'] ?? '') === '' && ($v['name'] ?? '') === '') continue;
                abort_if(($v['name'] ?? '') === '', 422, "Dòng {$line} thiếu tên vật tư.");
                $category = $selectedCategoryId
                    ? InventoryCategory::whereNotNull('parent_id')->find($selectedCategoryId)
                    : InventoryCategory::whereNotNull('parent_id')->where('code', $v['category_code'] ?? '')->first();
                abort_unless($category, 422, "Dòng {$line} không tìm thấy Mã loại vật tư.");
                $code = trim((string) ($v['code'] ?? ''));
                $payload = ['category_id' => $category->id, 'name' => $v['name'], 'unit' => $v['unit'] ?: 'cái'];
                $material = $code !== ''
                    ? InventoryMaterial::updateOrCreate(['code' => $code], $payload)
                    : InventoryMaterial::create($payload + ['code' => $this->nextMaterialCode((int) $category->id)]);
                InventoryAuditLog::create(['user_id' => $request->user()->id, 'action' => 'IMPORT', 'entity_type' => 'material', 'entity_id' => $material->id, 'details' => $payload + ['code' => $material->code, 'category_code' => $category->code]]);
                $count++;
            }
        });
        return $count;
    }
    private function nextMaterialCode(int $categoryId): string
    {
        $category = InventoryCategory::findOrFail($categoryId);
        $prefix = (string) $category->code;
        $next = InventoryMaterial::where('category_id', $category->id)
            ->where('code', 'like', $prefix.'%')
            ->pluck('code')
            ->map(fn ($code) => (int) substr((string) $code, strlen($prefix)))
            ->max() + 1;

        do {
            $code = $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
            $next++;
        } while (InventoryMaterial::where('code', $code)->exists());

        return $code;
    }
    private function importRowValues(array $headers, array $row): array
    {
        $values = [];
        foreach ($headers as $key => $header) {
            if ($header !== '') $values[$header] = trim((string) ($row[$key] ?? ''));
        }
        return $values;
    }
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
            'mã loại' => 'category_code', 'ma loai' => 'category_code', 'mã loại vật tư' => 'category_code', 'ma loai vat tu' => 'category_code', 'category_code' => 'category_code',
            'tên loại' => 'category_name', 'ten loai' => 'category_name', 'tên loại vật tư' => 'category_name', 'ten loai vat tu' => 'category_name', 'category_name' => 'category_name',
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
    private function resolveOrCreateImportCategory(array $values, int $line): ?InventoryCategory
    {
        $industry = $this->resolveOrCreateImportIndustry($values, $line);

        $query = InventoryCategory::query()->where('parent_id', $industry->id);
        if (($values['category_code'] ?? '') !== '') {
            $category = (clone $query)->where('code', $values['category_code'])->first();
            if ($category) return $category;
        }
        if (($values['category_name'] ?? '') !== '') {
            $category = (clone $query)->where('name', $values['category_name'])->first();
            if ($category) return $category;
        }

        $categoryCode = trim((string) ($values['category_code'] ?? ''));
        $categoryName = trim((string) ($values['category_name'] ?? ''));
        abort_if($categoryName === '', 422, "Dòng {$line} cần có Tên loại để tạo loại vật tư.");

        if ($categoryCode === '') {
            $next = InventoryCategory::where('parent_id', $industry->id)
                ->get()
                ->map(fn ($item) => (int) substr((string) $item->code, strlen((string) $industry->code)))
                ->max() + 1;
            $categoryCode = $industry->code.str_pad((string) $next, 2, '0', STR_PAD_LEFT);
        }

        return InventoryCategory::create([
            'parent_id' => $industry->id,
            'code' => $categoryCode,
            'name' => $categoryName,
            'active' => true,
        ]);
    }
    private function resolveOrCreateImportIndustry(array $values, int $line): InventoryCategory
    {
        $industry = null;
        if (($values['industry_code'] ?? '') !== '') {
            $industry = InventoryCategory::whereNull('parent_id')->where('code', $values['industry_code'])->first();
        }
        if (! $industry && ($values['industry_name'] ?? '') !== '') {
            $industry = InventoryCategory::whereNull('parent_id')->where('name', $values['industry_name'])->first();
        }
        if ($industry) return $industry;

        $industryCode = trim((string) ($values['industry_code'] ?? ''));
        $industryName = trim((string) ($values['industry_name'] ?? ''));
        abort_if($industryCode === '' || $industryName === '', 422, "Dòng {$line} cần có Mã ngành và Tên ngành để tạo ngành vật tư.");

        return InventoryCategory::create([
            'code' => $industryCode,
            'name' => $industryName,
            'active' => true,
        ]);
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
    public function update(Request $request,InventoryMaterial $material)
    {
        $this->ensureInventoryMaterialAllowed($material);
        $d = $request->validate([
            'industry_id' => 'nullable|exists:inventory_categories,id',
            'category_id' => 'required|exists:inventory_categories,id',
            'code' => 'required|string|max:80|unique:inventory_materials,code,'.$material->id,
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:30',
        ]);
        if (! empty($d['industry_id']) && ! InventoryCategory::whereKey($d['category_id'])->where('parent_id', $d['industry_id'])->exists()) {
            return back()->withErrors(['category_id' => 'Loại vật tư không thuộc ngành vật tư đã chọn.'])->withInput();
        }
        unset($d['industry_id']);
        $material->update($d);
        InventoryAuditLog::create(['user_id' => $request->user()->id, 'action' => 'UPDATE', 'entity_type' => 'material', 'entity_id' => $material->id, 'details' => $d]);

        return back()->with('success', 'Đã cập nhật vật tư.');
    }
    public function destroy(Request $request,InventoryMaterial $material){$this->ensureInventoryMaterialAllowed($material);$blocked=[];if($material->warehouseItems()->exists())$blocked[]='vật tư trong kho';if($material->proposalItems()->exists())$blocked[]='phiếu đề xuất';if($material->transfers()->exists())$blocked[]='phiếu điều động';if($blocked)return back()->withErrors(['material'=>'Không thể xóa vật tư này vì đang có '.implode(', ',$blocked).'. Hãy xử lý hoặc xóa dữ liệu liên quan trước.']);DB::transaction(function()use($material,$request){$id=$material->id;$details=['code'=>$material->code,'name'=>$material->name,'room_assets_deleted'=>$material->assets()->count()];$material->assets()->delete();$material->delete();InventoryAuditLog::create(['user_id'=>$request->user()->id,'action'=>'DELETE','entity_type'=>'material','entity_id'=>$id,'details'=>$details]);});return back()->with('success','Đã xóa vật tư và các vật tư trong phòng liên quan.');}
}
