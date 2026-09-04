<?php

namespace Modules\Inventory\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Building\Models\Building;
use Modules\Classroom\Models\Classroom;
use Modules\Inventory\Models\{InventoryAsset,InventoryAuditLog,InventoryBrokenLog,InventoryCategory,InventoryMaterial,InventoryMovement,InventoryProposal,InventoryRepair,InventoryReportTemplate,InventoryRoomImage,InventoryRoomUser,InventoryTransfer,InventoryUserCategory,InventoryWarehouse,InventoryWarehouseItem};

class InventoryWorkflowController extends ModuleBaseController
{
    protected bool $useGenericModulePermissions = false;
    private const INVENTORY_STATUS_FIXED_WIDTHS = [900, 3000, 430, 480, 720];
    private const INVENTORY_STATUS_TABLE_WIDTH = 15400;

    public function category(Request $r){$isTypes=$r->routeIs('inventory.types');$categories=InventoryCategory::with('parent')->withCount('materials')->when($isTypes,fn($q)=>$q->whereNotNull('parent_id'),fn($q)=>$q->whereNull('parent_id'))->orderBy('code')->get();return view('inventory::feature',['section'=>'category','title'=>$isTypes?'Danh mục loại vật tư':'Danh mục ngành vật tư','categories'=>$categories,'parents'=>InventoryCategory::whereNull('parent_id')->orderBy('code')->get(),'isTypes'=>$isTypes,'users'=>\App\Models\User::where('status',1)->orderBy('name')->get()]);}
    public function categoryShow(InventoryCategory $category){$category->load(['parent','children'=>fn($q)=>$q->withCount('materials')->orderBy('code'),'materials.category']);return view('inventory::feature',['section'=>'category-detail','title'=>'Chi tiết '.$category->name,'category'=>$category,'isRoot'=>$category->parent_id===null]);}
    public function categoryStore(Request $r){$d=$r->validate(['parent_id'=>'nullable|exists:inventory_categories,id','code'=>'nullable|string|max:50|unique:inventory_categories,code','name'=>'required|string|max:255','description'=>'nullable|string']);if(!empty($d['parent_id'])){$parent=InventoryCategory::findOrFail($d['parent_id']);$prefix=$parent->code;$next=InventoryCategory::where('parent_id',$parent->id)->get()->map(fn($x)=>(int) substr($x->code,strlen($prefix)))->max()+1;$d['code']=$prefix.str_pad((string)$next,2,'0',STR_PAD_LEFT);}else{abort_if(empty($d['code']),422,'Ngành gốc phải có mã ngành.');}InventoryCategory::create($d);return back()->with('success','Đã thêm ngành/loại vật tư.');}
    public function categoryUpdate(Request $r,InventoryCategory $category){$rules=['name'=>'required|string|max:255','description'=>'nullable|string'];if($category->parent_id===null)$rules['code']='required|string|max:50|unique:inventory_categories,code,'.$category->id;$category->update($r->validate($rules));return back()->with('success','Đã cập nhật danh mục.');}
    public function categoryDelete(InventoryCategory $category){if($category->materials()->exists()||$category->children()->exists())return back()->withErrors(['category'=>'Không thể xóa danh mục đang có dữ liệu con.']);$category->delete();return back()->with('success','Đã xóa danh mục.');}

    public function assets(Request $r){$assets=InventoryAsset::with(['material','classroom'])->when($r->search,fn($q,$s)=>$q->where(fn($x)=>$x->where('asset_code','like',"%$s%")->orWhere('name','like',"%$s%")))->latest()->paginate(20)->withQueryString();$allAssets=InventoryAsset::with(['material','classroom'])->latest()->get();$auditLogs=InventoryAuditLog::with('user')->latest()->limit(100)->get()->each->resolveDetails();return view('inventory::feature',['section'=>'assets','title'=>'Cập nhật vật tư','assets'=>$assets,'allAssets'=>$allAssets,'auditLogs'=>$auditLogs,'materials'=>InventoryMaterial::with('category')->orderBy('name')->get(),'classrooms'=>Classroom::active()->orderBy('name')->get(),'categories'=>InventoryCategory::whereNotNull('parent_id')->orderBy('code')->get(),'industries'=>InventoryCategory::whereNull('parent_id')->orderBy('code')->get(),'buildings'=>Building::orderBy('name')->get(),'units'=>\Modules\Unit\Models\Unit::active()->orderBy('name')->get()]);}
    public function assetBulkStoreDelta(Request $r)
    {
        $data = $r->validate([
            'update_type' => 'required|in:IN,OUT',
            'reason' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:inventory_materials,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($data, $r) {
            foreach ($data['items'] as $item) {
                $material = InventoryMaterial::lockForUpdate()->findOrFail($item['material_id']);
                $before = (int) $material->quantity;
                $delta = (int) $item['quantity'];
                $after = $data['update_type'] === 'IN' ? $before + $delta : max(0, $before - $delta);
                $material->update(['quantity' => $after]);
                InventoryMovement::create([
                    'material_id' => $material->id,
                    'type' => $data['update_type'],
                    'quantity' => $delta,
                    'note' => $data['reason'],
                    'created_by' => $r->user()->id,
                ]);
                InventoryAuditLog::create([
                    'user_id' => $r->user()->id,
                    'action' => $data['update_type'] === 'IN' ? 'INCREASE' : 'DECREASE',
                    'entity_type' => 'material',
                    'entity_id' => $material->id,
                    'details' => $data + ['material_id' => $material->id, 'asset_code' => $material->code, 'name' => $material->name, 'install_address' => $material->location, 'before' => $before, 'after' => $after, 'change' => $data['update_type'] === 'IN' ? $delta : -$delta],
                ]);
            }
        });

        return back()->with('success', 'Đã cập nhật số lượng vật tư theo các dòng đã chọn.');
    }

    public function assetChangeDelta(Request $r)
    {
        $data = $r->validate([
            'asset_id' => 'nullable|exists:inventory_assets,id',
            'material_id' => 'nullable|exists:inventory_materials,id',
            'change_type' => 'required|in:IN,OUT',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'decision_date' => 'nullable|date',
            'decision_number' => 'nullable|string|max:100',
            'signer' => 'nullable|string|max:255',
            'performer' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        $asset = !empty($data['asset_id']) ? InventoryAsset::findOrFail($data['asset_id']) : null;
        $material = !empty($data['material_id']) ? InventoryMaterial::findOrFail($data['material_id']) : null;
        abort_if(!$asset && !$material, 422, 'Chưa chọn vật tư cần cập nhật.');
        if ($material) {
            $data['asset_code'] = $material->code;
            $data['name'] = $material->name;
            $data['install_address'] = $material->location;
        } elseif ($asset) {
            $data['asset_code'] = $asset->asset_code;
            $data['name'] = $asset->name;
            $data['install_address'] = $asset->install_address;
        }

        DB::transaction(function () use ($data, $asset, $material, $r) {
            $record = $asset ?: $material;
            $before = (int) $record->quantity;
            $delta = (int) $data['quantity'];
            $change = $data['change_type'] === 'IN' ? $delta : -$delta;
            $after = max(0, $before + $change);
            $record->update(['quantity' => $after]);
            if ($material) {
                InventoryMovement::create(['material_id' => $material->id, 'type' => $data['change_type'], 'quantity' => $delta, 'note' => $data['reason'], 'created_by' => $r->user()->id]);
            }
            InventoryAuditLog::create(['user_id' => $r->user()->id, 'action' => $data['change_type'] === 'IN' ? 'INCREASE' : 'DECREASE', 'entity_type' => $material ? 'material' : 'asset', 'entity_id' => $record->id, 'details' => $data + ['before' => $before, 'after' => $after, 'change' => $change]]);
        });

        return back()->with('success', 'Đã cập nhật số lượng vật tư.');
    }

    public function materialAdjust(Request $r)
    {
        $data = $r->validate([
            'material_id' => 'required|exists:inventory_materials,id',
            'quantity' => 'required|integer|min:0',
            'reason' => 'required|string|max:255',
            'decision_date' => 'nullable|date',
            'decision_number' => 'nullable|string|max:100',
            'signer' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);
        $material = InventoryMaterial::findOrFail($data['material_id']);
        $before = (int) $material->quantity;
        $material->update(['quantity' => (int) $data['quantity']]);
        InventoryAuditLog::create([
            'user_id' => $r->user()->id,
            'action' => 'ADJUST',
            'entity_type' => 'material',
            'entity_id' => $material->id,
            'details' => $data + ['asset_code' => $material->code, 'name' => $material->name, 'before' => $before, 'after' => (int) $data['quantity']],
        ]);
        return back()->with('success', 'Đã điều chỉnh vật tư.');
    }

    public function assetBulkStore(Request $r){$d=$r->validate(['category_id'=>'nullable|exists:inventory_categories,id','building_id'=>'nullable|exists:buildings,id','classroom_id'=>'nullable|exists:classrooms,id','holding_unit_id'=>'nullable|exists:units,id','update_type'=>'required|in:IN,OUT','reason'=>'required|string|max:255','items'=>'required|array|min:1','items.*.category_id'=>'nullable|exists:inventory_categories,id','items.*.name'=>'required|string|max:255','items.*.asset_code'=>'nullable|string|max:100','items.*.quantity'=>'required|numeric|min:.01','items.*.unit'=>'nullable|string|max:30','items.*.grade'=>'nullable|integer|min:1|max:5','items.*.manufacture_year'=>'nullable|integer|min:1900|max:2200','items.*.usage_year'=>'nullable|integer|min:1900|max:2200','items.*.purchase_date'=>'nullable|date','items.*.install_address'=>'nullable|string|max:255','items.*.note'=>'nullable|string']);DB::transaction(function()use($d){foreach($d['items'] as $item){$item['asset_code']=$item['asset_code']?:'VT-'.now()->format('YmdHis').'-'.random_int(100,999);$item['unit']=$item['unit']?:'cái';$item['status']='NORMAL';$item['classroom_id']=$d['classroom_id']??null;$item['holding_unit_id']=$d['holding_unit_id']??null;$item['category']=$item['category_id']??($d['category_id']??null);$item['note']=$item['note']??$d['reason'];InventoryAsset::create(collect($item)->only(['asset_code','name','quantity','unit','grade','manufacture_year','usage_year','purchase_date','install_address','note','status','classroom_id','holding_unit_id','category'])->all());}});return back()->with('success','Đã lưu '.count($d['items']).' dòng vật tư.');}
    public function assetChange(Request $r){$d=$r->validate(['asset_id'=>'nullable|exists:inventory_assets,id','material_id'=>'nullable|exists:inventory_materials,id','change_type'=>'required|in:IN,OUT','quantity'=>'required|integer|min:1','asset_code'=>'nullable|string|max:100','name'=>'nullable|string|max:255','category'=>'nullable|string|max:255','classroom_id'=>'nullable|exists:classrooms,id','holding_unit_id'=>'nullable|exists:units,id','install_address'=>'nullable|string|max:255','grade'=>'nullable|integer|min:1|max:5','manufacture_year'=>'nullable|integer|min:1900|max:2200','usage_year'=>'nullable|integer|min:1900|max:2200','purchase_date'=>'nullable|date','reason'=>'required|string|max:255','decision_date'=>'nullable|date','decision_number'=>'nullable|string|max:100','signer'=>'nullable|string|max:255','performer'=>'nullable|string|max:255','building_name'=>'nullable|string|max:255','note'=>'nullable|string']);$asset=($d['asset_id']??null)?InventoryAsset::findOrFail($d['asset_id']):null;$material=($d['material_id']??null)?InventoryMaterial::findOrFail($d['material_id']):null;abort_if(!$asset&&!$material,422,'Chưa chọn vật tư cần cập nhật.');$current=(int)($asset?->quantity??$material?->quantity??0);$newQuantity=(int)$d['quantity'];$change=$newQuantity-$current;abort_if($newQuantity<0,422,'Số lượng sau cập nhật không hợp lệ.');DB::transaction(function()use($d,$asset,$material,$change,$newQuantity,$current){if($asset){$fields=collect($d)->only(['asset_code','name','category','classroom_id','holding_unit_id','install_address','grade','manufacture_year','usage_year','purchase_date','note'])->filter(fn($v)=>$v!==null&&$v!=='')->all();$asset->update($fields+['quantity'=>$newQuantity]);}if($material)$material->update(['quantity'=>$newQuantity]);if($material)InventoryMovement::create(['material_id'=>$material->id,'type'=>$d['change_type'],'quantity'=>abs($change),'note'=>$d['reason'],'created_by'=>auth()->id()]);InventoryAuditLog::create(['user_id'=>auth()->id(),'action'=>$d['change_type']==='IN'?'INCREASE':'DECREASE','entity_type'=>$material?'material':'asset','entity_id'=>$material?->id??$asset?->id,'details'=>$d+['before'=>$current,'after'=>$newQuantity,'change'=>$change]]);});return back()->with('success','Đã cập nhật số lượng vật tư.');}
    public function assetAdjust(Request $r){$d=$r->validate(['asset_id'=>'required|exists:inventory_assets,id','quantity'=>'required|numeric|min:0','grade'=>'nullable|integer|min:1|max:5','status'=>'required|in:NORMAL,BROKEN,REPAIRING,LIQUIDATED','reason'=>'required|string|max:255','decision_date'=>'nullable|date','decision_number'=>'nullable|string|max:100','signer'=>'nullable|string|max:255','note'=>'nullable|string']);$asset=InventoryAsset::findOrFail($d['asset_id']);$asset->update(collect($d)->only(['quantity','grade','status','note'])->all());if($asset->material_id)$asset->material?->update(['quantity'=>(int)$d['quantity']]);InventoryAuditLog::create(['user_id'=>auth()->id(),'action'=>'ADJUST','entity_type'=>'asset','entity_id'=>$asset->id,'details'=>$d]);return back()->with('success','Đã điều chỉnh vật tư.');}
    public function assetStore(Request $r){$d=$r->validate(['material_id'=>'nullable|exists:inventory_materials,id','industry_id'=>'required|exists:inventory_categories,id','category_id'=>'required|exists:inventory_categories,id','classroom_id'=>'nullable|exists:classrooms,id','holding_unit_id'=>'nullable|exists:units,id','asset_code'=>'required|string|max:100|unique:inventory_assets,asset_code','name'=>'required|string|max:255','category'=>'nullable|string|max:255','quantity'=>'required|numeric|min:.01','broken_quantity'=>'nullable|numeric|min:0','unit'=>'nullable|string|max:30','grade'=>'nullable|integer|min:1|max:5','manufacture_year'=>'nullable|integer|min:1900|max:2200','usage_year'=>'nullable|integer|min:1900|max:2200','install_address'=>'nullable|string|max:255','status'=>'required|in:NORMAL,BROKEN,REPAIRING,LIQUIDATED','purchase_date'=>'nullable|date','expiry_date'=>'nullable|date','broken_at'=>'nullable|date','repair_started_at'=>'nullable|date','repair_completed_at'=>'nullable|date','repair_performer'=>'nullable|string|max:255','note'=>'nullable|string','description'=>'nullable|string']);$type=InventoryCategory::whereKey($d['category_id'])->where('parent_id',$d['industry_id'])->where('active',true)->firstOrFail();$d['category']=$type->name;unset($d['industry_id'],$d['category_id']);InventoryAsset::create($d);return back()->with('success','Đã thêm tài sản.');}
    public function assetUpdate(Request $r,InventoryAsset $asset){$d=$r->validate(['material_id'=>'nullable|exists:inventory_materials,id','industry_id'=>'required|exists:inventory_categories,id','category_id'=>'required|exists:inventory_categories,id','classroom_id'=>'nullable|exists:classrooms,id','holding_unit_id'=>'nullable|exists:units,id','asset_code'=>'required|string|max:100|unique:inventory_assets,asset_code,'.$asset->id,'name'=>'required|string|max:255','category'=>'nullable|string|max:255','quantity'=>'required|numeric|min:.01','broken_quantity'=>'nullable|numeric|min:0','unit'=>'nullable|string|max:30','grade'=>'nullable|integer|min:1|max:5','manufacture_year'=>'nullable|integer|min:1900|max:2200','usage_year'=>'nullable|integer|min:1900|max:2200','install_address'=>'nullable|string|max:255','status'=>'required|in:NORMAL,BROKEN,REPAIRING,LIQUIDATED','purchase_date'=>'nullable|date','expiry_date'=>'nullable|date','broken_at'=>'nullable|date','repair_started_at'=>'nullable|date','repair_completed_at'=>'nullable|date','repair_performer'=>'nullable|string|max:255','note'=>'nullable|string','description'=>'nullable|string']);$type=InventoryCategory::whereKey($d['category_id'])->where('parent_id',$d['industry_id'])->where('active',true)->firstOrFail();$d['category']=$type->name;unset($d['industry_id'],$d['category_id']);$asset->update($d);return back()->with('success','Đã cập nhật tài sản.');}
    public function assetDelete(InventoryAsset $asset){if($asset->repairs()->exists()||$asset->proposals()->exists())return back()->withErrors(['asset'=>'Không thể xóa tài sản đã có lịch sử.']);$asset->delete();return back()->with('success','Đã xóa tài sản.');}

    public function warehouse(){return view('inventory::feature',['section'=>'warehouse','title'=>'Kho vật tư','warehouses'=>InventoryWarehouse::with(['manager','industry','items.material.category.parent'])->latest()->get(),'users'=>\App\Models\User::where('status',1)->orderBy('name')->get(),'industries'=>InventoryCategory::whereNull('parent_id')->where('active',true)->orderBy('code')->get(),'materials'=>InventoryMaterial::with('category.parent')->orderBy('name')->get()]);}
    public function warehouseStore(Request $r){InventoryWarehouse::create($r->validate(['code'=>'required|string|max:80|unique:inventory_warehouses,code','name'=>'required|string|max:255','industry_id'=>'required|exists:inventory_categories,id','location'=>'nullable|string|max:255','manager_id'=>'nullable|exists:users,id','description'=>'nullable|string']));return back()->with('success','Đã thêm kho theo ngành vật tư.');}
    public function warehouseUpdate(Request $r,InventoryWarehouse $warehouse){$d=$r->validate(['code'=>'required|string|max:80|unique:inventory_warehouses,code,'.$warehouse->id,'name'=>'required|string|max:255','location'=>'nullable|string|max:255','manager_id'=>'nullable|exists:users,id','description'=>'nullable|string','active'=>'nullable|boolean']);$warehouse->update($d);return back()->with('success','Đã sửa kho.');}
    public function warehouseDestroy(InventoryWarehouse $warehouse){$warehouse->delete();return back()->with('success','Đã xóa kho và các mặt hàng tồn trong kho.');}
    public function warehouseItemStore(Request $r){$d=$r->validate(['warehouse_id'=>'required|exists:inventory_warehouses,id','material_id'=>'nullable|exists:inventory_materials,id','code'=>'required|string|max:100','name'=>'required|string|max:255','unit'=>'nullable|string|max:30','quantity'=>'required|numeric|min:0','minimum_quantity'=>'nullable|numeric|min:0','note'=>'nullable|string']);$warehouse=InventoryWarehouse::with('industry')->findOrFail($d['warehouse_id']);if(!empty($d['material_id'])&&$warehouse->industry_id){$material=InventoryMaterial::with('category')->findOrFail($d['material_id']);abort_unless((int)$material->category?->parent_id===(int)$warehouse->industry_id,422,'Vật tư phải thuộc đúng ngành vật tư của kho.');}InventoryWarehouseItem::updateOrCreate(['warehouse_id'=>$d['warehouse_id'],'code'=>$d['code']],$d);return back()->with('success','Đã cập nhật vật tư trong kho.');}
    public function warehouseItemUpdate(Request $r,InventoryWarehouse $warehouse,InventoryWarehouseItem $item){abort_unless((int)$item->warehouse_id===(int)$warehouse->id,404);$d=$r->validate(['material_id'=>'nullable|exists:inventory_materials,id','code'=>['required','string','max:100',Rule::unique('inventory_warehouse_items','code')->where(fn($q)=>$q->where('warehouse_id',$warehouse->id))->ignore($item->id)],'name'=>'required|string|max:255','unit'=>'nullable|string|max:30','quantity'=>'required|numeric|min:0','minimum_quantity'=>'nullable|numeric|min:0','note'=>'nullable|string']);$item->update($d);return back()->with('success','Đã sửa mặt hàng trong kho.');}
    public function warehouseItemDestroy(InventoryWarehouse $warehouse,InventoryWarehouseItem $item){abort_unless((int)$item->warehouse_id===(int)$warehouse->id,404);$item->delete();return back()->with('success','Đã xóa mặt hàng khỏi kho.');}

    public function proposals(){$liquidated=InventoryProposal::where('type','LIQUIDATION')->whereIn('status',['APPROVED','COMPLETED'])->with('items')->get()->sum(fn($p)=>$p->items->sum('quantity'));$recalled=InventoryProposal::where('type','RECALL')->whereIn('status',['APPROVED','COMPLETED'])->with('items')->get()->sum(fn($p)=>$p->items->sum('quantity'));$repaired=InventoryRepair::where('status','COMPLETED')->with('asset')->get()->sum(fn($r)=>(int)($r->asset?->quantity??0));return view('inventory::feature',['section'=>'proposals','title'=>'Đề xuất / thanh lý','proposals'=>InventoryProposal::with(['unit','items'])->latest()->get(),'liquidatedQuantity'=>$liquidated,'recalledQuantity'=>$recalled,'repairedQuantity'=>$repaired,'units'=>\Modules\Unit\Models\Unit::active()->orderBy('name')->get(),'assets'=>InventoryAsset::with('classroom')->orderBy('name')->get(),'materials'=>InventoryMaterial::with(['category.parent'])->orderBy('name')->get(),'categories'=>InventoryCategory::where('active',true)->whereNull('parent_id')->orderBy('code')->get(),'types'=>InventoryCategory::with('parent')->where('active',true)->whereNotNull('parent_id')->orderBy('code')->get(),'classrooms'=>Classroom::active()->with('building')->orderBy('name')->get()]);}
    public function proposalApproval(){return view('inventory::feature',['section'=>'proposal-approval','title'=>'Duyệt đề xuất','proposals'=>InventoryProposal::with(['unit','items'])->whereIn('status',['PENDING','APPROVED'])->latest()->get(),'transferProposals'=>InventoryTransfer::with(['asset','fromClassroom','toClassroom','warehouse'])->whereIn('status',['PENDING','APPROVED'])->latest()->get()]);}
    public function proposalDetail(InventoryProposal $proposal){return view('inventory::feature',['section'=>'proposal-detail','title'=>'Chi tiết đề xuất','proposal'=>$proposal->load(['unit','items'])]);}
    public function proposalPrint(Request $r,InventoryProposal $proposal){$d=$r->validate(['print_mode'=>'required|in:unsigned,image,direct','signature_data'=>'nullable|string|max:7000000']);$signaturePath=$proposal->signature_path;$method=null;if($d['print_mode']!=='unsigned'){abort_unless(preg_match('/^data:image\/png;base64,(.+)$/s',(string)$d['signature_data'],$m),422,'Chữ ký phải là ảnh PNG hợp lệ.');$binary=base64_decode($m[1],true);abort_unless($binary!==false&&strlen($binary)>100,422,'Ảnh chữ ký không hợp lệ.');$signaturePath='inventory/signatures/'.now()->format('Y/m').'/proposal-'.$proposal->id.'-'.bin2hex(random_bytes(5)).'.png';Storage::disk('public')->put($signaturePath,$binary);$method=$d['print_mode']==='image'?'upload':'draw';}$proposal->update(['print_mode'=>$d['print_mode'],'signature_method'=>$method,'signature_path'=>$signaturePath,'printed_at'=>now()]);return view('inventory::partials.proposal-print',['proposal'=>$proposal->fresh()->load(['unit','items']),'signatureUrl'=>$signaturePath?Storage::disk('public')->url($signaturePath):null]);}
    public function proposalStore(Request $r){$d=$r->validate(['type'=>'required|in:REPAIR,RECALL,LIQUIDATION','title'=>'required|string|max:255','description'=>'nullable|string','unit_id'=>'nullable|exists:units,id','asset_id'=>'nullable|exists:inventory_assets,id','material_id'=>'nullable|exists:inventory_materials,id','loai_id'=>'required|exists:inventory_categories,id','classroom_id'=>'nullable|exists:classrooms,id','quantity'=>'nullable|numeric|min:.01','item_note'=>'nullable|string','location_note'=>'nullable|string|max:255','nganh_code'=>'required|string|max:80']);$industry=InventoryCategory::whereNull('parent_id')->where('code',$d['nganh_code'])->where('active',true)->first();$type=InventoryCategory::whereKey($d['loai_id'])->where('parent_id',$industry?->id)->where('active',true)->first();abort_unless($industry&&$type,422,'Loại vật tư không thuộc ngành đã chọn.');$materialId=$d['material_id']??null;$material=$materialId?InventoryMaterial::with('category')->findOrFail($materialId):null;abort_unless(!$material||((int)$material->category_id===(int)$type->id),422,'Vật tư không thuộc loại vật tư đã chọn.');$p=DB::transaction(function()use($d,$r,$materialId){$p=InventoryProposal::create(collect($d)->except(['asset_id','material_id','loai_id','classroom_id','quantity','item_note','location_note'])->merge(['created_by'=>$r->user()->id,'proposed_by_user_id'=>$r->user()->id,'proposed_by_username'=>$r->user()->email,'proposed_by_display_name'=>$r->user()->name])->all());$assetId=$d['asset_id']??null;if($materialId||$assetId){$m=$materialId?InventoryMaterial::find($materialId):null;$a=$assetId?InventoryAsset::find($assetId):null;$p->items()->create(['asset_id'=>$a?->id,'material_id'=>$m?->id,'material_code'=>$m?->code,'material_name'=>$m?->name??$a?->name,'name'=>$m?->name??$a?->name??$d['title'],'quantity'=>$d['quantity']??1,'unit'=>$m?->unit??$a?->unit,'from_classroom_id'=>$d['classroom_id']??$a?->classroom_id,'location_note'=>$d['location_note']??null,'note'=>$d['item_note']??null,'original_grade'=>$a?->grade,'original_code'=>$a?->asset_code]);}InventoryAuditLog::create(['user_id'=>$r->user()->id,'action'=>'CREATE','entity_type'=>'proposal','entity_id'=>$p->id,'details'=>$d]);return $p;});return back()->with('success','Đã tạo đề xuất.');}
    public function proposalUpdate(Request $r,InventoryProposal $proposal){$d=$r->validate(['type'=>'required|in:REPAIR,RECALL,LIQUIDATION,PURCHASE','title'=>'required|string|max:255','description'=>'nullable|string','unit_id'=>'nullable|exists:units,id','status'=>'required|in:PENDING,APPROVED,REJECTED,COMPLETED','decision_number'=>'nullable|string|max:100','decision_note'=>'nullable|string|max:2000']);$proposal->update($d);InventoryAuditLog::create(['user_id'=>$r->user()->id,'action'=>'UPDATE','entity_type'=>'proposal','entity_id'=>$proposal->id,'details'=>$d]);return back()->with('success','Đã cập nhật đề xuất vật tư.');}
    public function proposalDelete(InventoryProposal $proposal){DB::transaction(function()use($proposal):void{$id=$proposal->id;$details=['proposal_title'=>$proposal->title,'proposal_type'=>$proposal->type,'reason'=>'Xóa đề xuất vật tư'];$proposal->items()->delete();$proposal->delete();InventoryAuditLog::withoutEvents(fn()=>InventoryAuditLog::create(['user_id'=>auth()->id(),'action'=>'DELETE','entity_type'=>'proposal','entity_id'=>$id,'details'=>$details]));});return back()->with('success','Đã xóa đề xuất vật tư.');}
    public function proposalDecide(Request $r,InventoryProposal $proposal){$d=$r->validate(['status'=>'required|in:APPROVED,REJECTED,COMPLETED','decision_note'=>'required_if:status,REJECTED|string|min:3','decision_number'=>'nullable|string|max:100','decision_issuing_level'=>'nullable|string|max:100','decision_signer'=>'nullable|string|max:255']);$wasPending=$proposal->status==='PENDING';DB::transaction(function()use($d,$proposal,$r,$wasPending){$proposal->load('items');$proposal->update($d+['decided_by'=>$r->user()->id,'decided_at'=>now(),'completed_at'=>$d['status']==='COMPLETED'?now():null]);if($wasPending&&in_array($d['status'],['APPROVED','COMPLETED'],true))foreach($proposal->items as $item){$quantity=(int)($item->quantity?:1);if($proposal->type==='LIQUIDATION'){if($item->material){$item->material->update(['quantity'=>max(0,(int)$item->material->quantity-$quantity)]);}if($item->asset){$item->asset->update(['quantity'=>max(0,(int)$item->asset->quantity-$quantity),'status'=>'LIQUIDATED']);}}if($proposal->type==='RECALL'&&$item->asset)$item->asset->update(['classroom_id'=>null]);if($proposal->type==='REPAIR'&&$item->asset)$item->asset->update(['status'=>'REPAIRING']);}InventoryAuditLog::create(['user_id'=>$r->user()->id,'action'=>$d['status'],'entity_type'=>'proposal','entity_id'=>$proposal->id,'details'=>$d+['quantity_processed'=>$wasPending&&$proposal->type==='LIQUIDATION'?($proposal->items->sum('quantity')):0]]);});return back()->with('success','Đã cập nhật quyết định.');}

    public function repairs(){return view('inventory::feature',['section'=>'repairs','title'=>'Phân công sửa chữa','repairs'=>InventoryRepair::with(['asset','assignee'])->latest()->get(),'assets'=>InventoryAsset::whereIn('status',['BROKEN','REPAIRING'])->orderBy('name')->get(),'users'=>\App\Models\User::where('status',1)->orderBy('name')->get()]);}
    public function repairStore(Request $r){$d=$r->validate(['asset_id'=>'required|exists:inventory_assets,id','content'=>'required|string','assigned_to'=>'nullable|exists:users,id','cost'=>'nullable|numeric|min:0','performer'=>'nullable|string|max:255','started_at'=>'nullable|date','result_note'=>'nullable|string']);$a=InventoryAsset::findOrFail($d['asset_id']);$existing=InventoryRepair::where('asset_id',$a->id)->whereIn('status',['OPEN','ASSIGNED'])->latest()->first();if($existing && $r->filled('performer')){$existing->update(['status'=>'ASSIGNED','performer'=>$d['performer'],'started_at'=>$d['started_at']??now(),'cost'=>$d['cost']??$existing->cost,'result_note'=>$d['result_note']??null]);$a->update(['status'=>'REPAIRING','repair_started_at'=>$d['started_at']??now(),'repair_performer'=>$d['performer']]);return back()->with('success','Đã phân công người sửa.');}$repair=InventoryRepair::create($d+['status'=>($d['assigned_to']??null)?'ASSIGNED':'OPEN','source_type'=>'REPAIR_REQUEST','requested_by'=>$r->user()->id,'opened_at'=>now(),'started_at'=>now()]);$a->update(['status'=>'REPAIRING','repair_started_at'=>now(),'repair_performer'=>$d['performer']??null]);InventoryBrokenLog::create(['event_type'=>'BROKEN','source_type'=>'REPAIR_REQUEST','source_id'=>$repair->id,'asset_id'=>$a->id,'asset_code'=>$a->asset_code,'asset_name'=>$a->name,'quantity'=>$a->quantity,'original_grade'=>$a->grade,'grade_after'=>5,'status_after'=>'REPAIRING','reason'=>$d['content'],'performer'=>$d['performer']??null,'event_at'=>now(),'actor_user_id'=>$r->user()->id]);return back()->with('success','Đã ghi nhận sửa chữa.');}
    public function repairComplete(Request $r,InventoryRepair $repair){$d=$r->validate(['status'=>'required|in:COMPLETED,CANCELLED','result'=>'nullable|string','result_note'=>'nullable|string','performer'=>'nullable|string|max:255']);$repair->update($d+['completed_at'=>now(),'result_note'=>$d['result_note']??($d['result']??null)]);if($d['status']==='COMPLETED'){$a=$repair->asset;$a?->update(['status'=>'NORMAL','repair_completed_at'=>now(),'repair_performer'=>$d['performer']??$repair->performer]);if($a)InventoryBrokenLog::create(['event_type'=>'COMPLETED','source_type'=>'REPAIR_REQUEST','source_id'=>$repair->id,'asset_id'=>$a->id,'asset_code'=>$a->asset_code,'asset_name'=>$a->name,'quantity'=>$a->quantity,'original_grade'=>$a->grade,'grade_after'=>2,'status_after'=>'NORMAL','result_note'=>$d['result_note']??($d['result']??null),'performer'=>$d['performer']??$repair->performer,'event_at'=>now(),'actor_user_id'=>$r->user()->id]);}return back()->with('success','Đã cập nhật sửa chữa.');}

    public function transfers(){return view('inventory::feature',['section'=>'transfers','title'=>'Điều động và thu hồi','transfers'=>InventoryTransfer::with(['asset','material','fromClassroom','toClassroom','warehouse'])->latest()->get(),'assets'=>InventoryAsset::with('material')->orderBy('name')->get(),'materials'=>InventoryMaterial::with('category')->orderBy('name')->get(),'categories'=>InventoryCategory::where('active',true)->whereNotNull('parent_id')->orderBy('code')->get(),'industries'=>InventoryCategory::where('active',true)->whereNull('parent_id')->orderBy('code')->get(),'classrooms'=>Classroom::active()->with('managingUnit')->orderBy('name')->get(),'warehouses'=>InventoryWarehouse::where('active',true)->orderBy('name')->get(),'units'=>\Modules\Unit\Models\Unit::active()->orderBy('name')->get()]);}
    public function transferStore(Request $r)
    {
        $d = $r->validate([
            'asset_id' => 'nullable|exists:inventory_assets,id',
            'asset_ids' => 'nullable|array|min:1',
            'asset_ids.*' => 'exists:inventory_assets,id',
            'quantities' => 'nullable|array',
            'quantities.*' => 'nullable|integer|min:1',
            'material_id' => 'nullable|exists:inventory_materials,id',
            'from_classroom_id' => 'required|exists:classrooms,id',
            'to_classroom_id' => 'nullable|exists:classrooms,id',
            'warehouse_id' => 'nullable|exists:inventory_warehouses,id',
            'type' => 'required|in:TRANSFER,RECALL',
            'reason' => 'nullable|string', 'performed_at' => 'nullable|date',
            'performing_unit' => 'required|string|max:255', 'using_unit' => 'nullable|string|max:255',
            'decision_date' => 'nullable|date', 'decision_number' => 'nullable|string|max:100',
            'signer' => 'nullable|string|max:255', 'requesting_unit' => 'required|string|max:255',
            'supplemental_reason' => 'nullable|string', 'general_note' => 'nullable|string',
        ]);
        if (blank($d['reason'] ?? null)) $d['reason'] = $d['type'] === 'RECALL' ? 'Thu hồi' : 'Điều động';
        $assetIds = $d['asset_ids'] ?? (empty($d['asset_id']) ? [] : [$d['asset_id']]);
        abort_unless($assetIds || ($d['material_id'] ?? null), 422, 'Chưa chọn vật tư cần điều động hoặc thu hồi.');
        if ($assetIds) {
            $assets = InventoryAsset::whereIn('id', $assetIds)->where('classroom_id', $d['from_classroom_id'])->get()->keyBy('id');
            abort_unless($assets->count() === count($assetIds), 422, 'Phòng nguồn không có vật tư đã chọn.');
            foreach ($assetIds as $assetId) {
                $quantity = (int) ($d['quantities'][$assetId] ?? 0);
                abort_unless($quantity > 0 && $quantity <= (int) $assets[$assetId]->quantity, 422, 'Số lượng điều động phải lớn hơn 0 và không vượt quá số lượng hiện có.');
            }
        }
        if ($d['type'] === 'TRANSFER' && empty($d['to_classroom_id'])) return back()->withErrors(['to_classroom_id' => 'Điều động phải chọn phòng đích.'])->withInput();
        if ($d['type'] === 'TRANSFER') { $room = Classroom::with('managingUnit')->findOrFail($d['to_classroom_id']); abort_unless($room->managingUnit, 422, 'Phòng đích chưa có đơn vị quản lý.'); $d['using_unit'] = $room->managingUnit->name; }
        if ($d['type'] === 'RECALL') {
            $warehouse = InventoryWarehouse::where('active', true)->find($d['warehouse_id'] ?? null);
            abort_unless($warehouse, 422, 'Vui lòng chọn kho đích để thu hồi vật tư.');
            $d['warehouse_id'] = $warehouse->id;
        }
        if ($d['type'] === 'RECALL') $d['to_classroom_id'] = null;
        $base = collect($d)->except(['asset_id', 'asset_ids', 'quantities'])->all();
        if ($assetIds) foreach ($assetIds as $assetId) {
            $quantity = (int) $d['quantities'][$assetId];
            $payload = $base + ['asset_id' => $assetId, 'material_id' => null, 'requested_by' => $r->user()->id, 'status' => 'PENDING'];
            if (Schema::hasColumn('inventory_transfers', 'quantity')) $payload['quantity'] = $quantity;
            else $payload['general_note'] = trim(($payload['general_note'] ?? '') . ' [Số lượng chuyển: ' . $quantity . ']');
            InventoryTransfer::create($payload);
        }
        else {
            $payload = $base + ['requested_by' => $r->user()->id, 'status' => 'PENDING'];
            if (Schema::hasColumn('inventory_transfers', 'quantity')) $payload['quantity'] = 1;
            InventoryTransfer::create($payload);
        }
        return back()->with('success', $d['type'] === 'RECALL' ? 'Đã tạo phiếu thu hồi về kho vật tư.' : 'Đã tạo phiếu điều động theo số lượng đã chọn.');
    }
    public function transferDetail(InventoryTransfer $transfer){return view('inventory::feature',['section'=>'transfer-detail','title'=>'Chi tiết phiếu điều động / thu hồi','transfer'=>$transfer->load(['asset.material','fromClassroom.managingUnit','toClassroom.managingUnit','warehouse','decidedBy']),'units'=>\Modules\Unit\Models\Unit::active()->orderBy('name')->get()]);}
    public function transferUpdate(Request $r, InventoryTransfer $transfer){$d=$r->validate(['performing_unit'=>'required|string|max:255','requesting_unit'=>'required|string|max:255','decision_date'=>'nullable|date','decision_number'=>'nullable|string|max:100','signer'=>'nullable|string|max:255','using_unit'=>'nullable|string|max:255','general_note'=>'nullable|string']);$d['printed_at']=null;$d['general_note']=trim(preg_replace('/\s*\[ĐÃ IN QUYẾT ĐỊNH\]/u','',$d['general_note']??''));if(!Schema::hasColumn('inventory_transfers','printed_at'))unset($d['printed_at']);$transfer->update($d);if($r->boolean('print'))return $this->transferWord($transfer->fresh());return back()->with('success','Đã cập nhật thông tin phiếu.');}
    public function transferDelete(InventoryTransfer $transfer){$transfer->delete();return back()->with('success','Đã xóa phiếu điều động / thu hồi.');}
    public function transferWord(InventoryTransfer $transfer){
        $transfer->load(['asset.material','fromClassroom.managingUnit','toClassroom.managingUnit','warehouse']);
        if (Schema::hasColumn('inventory_transfers', 'printed_at')) $transfer->update(['printed_at'=>now()]);
        else $transfer->update(['general_note'=>trim(($transfer->general_note?:'').' [ĐÃ IN QUYẾT ĐỊNH]')]);
        $sourceUnit=$transfer->fromClassroom?->managingUnit?->name ?: 'Đơn vị giao';
        $callingUnit=$transfer->performing_unit ?: 'Đơn vị gọi giao';
        $receiverUnit=$transfer->type==='RECALL'?($transfer->warehouse?->name ?: 'Kho vật tư'):($transfer->toClassroom?->managingUnit?->name ?: 'Đơn vị nhận');
        return view('inventory::partials.transfer-print',['transfer'=>$transfer,'sourceUnit'=>$sourceUnit,'callingUnit'=>$callingUnit,'receiverUnit'=>$receiverUnit]);
    }
    public function transferDecide(Request $r, InventoryTransfer $transfer)
    {
        abort_unless($transfer->is_printed, 403, 'Phải in quyết định trước khi duyệt phiếu.');
        $d = $r->validate(['status' => 'required|in:APPROVED,REJECTED,COMPLETED', 'decision_note' => 'required_if:status,REJECTED|string|min:3', 'decision_number' => 'nullable|string|max:100']);
        $wasCompleted = $transfer->status === 'COMPLETED';
        DB::transaction(function () use ($d, $transfer, $r, $wasCompleted) {
            $transfer->load(['asset', 'material']);
            $transfer->update($d + ['decided_by' => $r->user()->id, 'decided_at' => now()]);
            if ($d['status'] !== 'COMPLETED' || $wasCompleted) return;
            $quantity = (int) ($transfer->quantity ?: 0);
            if ($quantity < 1 && preg_match('/Số lượng chuyển:\s*(\d+)/u', (string) $transfer->general_note, $match)) $quantity = (int) $match[1];
            $quantity = max(1, $quantity);
            $destination = $transfer->type === 'RECALL' ? null : $transfer->to_classroom_id;
            $warehouseItemData = null;
            if ($transfer->asset) {
                $asset = $transfer->asset;
                $current = (int) $asset->quantity;
                abort_unless($quantity <= $current, 422, 'Số lượng duyệt vượt quá số lượng hiện có.');
                $warehouseItemData = [
                    'code' => $asset->material?->code ?: $asset->asset_code,
                    'name' => $asset->material?->name ?: $asset->name,
                    'unit' => $asset->material?->unit ?: $asset->unit,
                    'material_id' => $asset->material_id,
                ];
                if ($quantity === $current) {
                    $asset->update($transfer->type === 'RECALL'
                        ? ['quantity' => 0, 'classroom_id' => null]
                        : ['classroom_id' => $destination]);
                } else {
                    $asset->decrement('quantity', $quantity);
                    if ($destination !== null) {
                        $newAsset = $asset->replicate();
                        // Mã vật tư là mã định danh của vật tư, không đổi khi điều động.
                        $newAsset->asset_code = $asset->asset_code;
                        $newAsset->quantity = $quantity;
                        $newAsset->classroom_id = $destination;
                        $newAsset->save();
                    }
                }
            }
            if ($transfer->material && !$transfer->asset) {
                $transfer->material->update($transfer->type === 'RECALL'
                    ? ['quantity' => max(0, (int) $transfer->material->quantity - $quantity), 'classroom_id' => null]
                    : ['classroom_id' => $destination]);
                $warehouseItemData = [
                    'code' => $transfer->material->code,
                    'name' => $transfer->material->name,
                    'unit' => $transfer->material->unit,
                    'material_id' => $transfer->material->id,
                ];
            }
            if ($transfer->type === 'RECALL' && $warehouseItemData && $transfer->warehouse_id) {
                $warehouseItem = InventoryWarehouseItem::firstOrCreate(
                    ['warehouse_id' => $transfer->warehouse_id, 'code' => $warehouseItemData['code']],
                    $warehouseItemData + ['quantity' => 0, 'minimum_quantity' => 0]
                );
                $warehouseItem->update(['name' => $warehouseItemData['name'], 'unit' => $warehouseItemData['unit'], 'material_id' => $warehouseItemData['material_id']]);
                $warehouseItem->increment('quantity', $quantity);
            }
            InventoryAuditLog::create(['user_id' => $r->user()->id, 'action' => $transfer->type === 'RECALL' ? 'RECALL' : 'TRANSFER', 'entity_type' => $transfer->material ? 'material' : 'asset', 'entity_id' => $transfer->material?->id ?? $transfer->asset?->id, 'details' => $d + ['reason' => $transfer->reason ?: ($transfer->type === 'RECALL' ? 'Thu hồi' : 'Điều động'), 'from_classroom_id' => $transfer->from_classroom_id, 'to_classroom_id' => $destination, 'warehouse_id' => $transfer->warehouse_id, 'warehouse_name' => $transfer->warehouse?->name, 'quantity' => $quantity]]);
        });
        return back()->with('success', $transfer->type === 'RECALL' ? 'Đã thu hồi đúng số lượng về kho.' : 'Đã điều động đúng số lượng sang phòng đích.');
    }

    public function search(Request $r){$term=$r->string('q')->toString();return view('inventory::feature',['section'=>'search','title'=>'Tìm kiếm vật tư','materials'=>InventoryMaterial::with(['building','classroom'])->when($term,fn($q)=>$q->where(fn($x)=>$x->where('code','like',"%$term%")->orWhere('name','like',"%$term%")))->get(),'assets'=>InventoryAsset::with('classroom')->when($term,fn($q)=>$q->where(fn($x)=>$x->where('asset_code','like',"%$term%")->orWhere('name','like',"%$term%")))->get()]);}
    public function logs(){return view('inventory::feature',['section'=>'logs','title'=>'Nhật ký vật tư','logs'=>InventoryMovement::with(['material','user'])->latest()->paginate(30),'transferLogs'=>InventoryTransfer::with(['asset','material','fromClassroom','toClassroom','decidedBy'])->latest()->limit(50)->get(),'repairLogs'=>InventoryRepair::with('asset')->latest()->limit(50)->get(),'proposalLogs'=>InventoryProposal::with(['items','unit','decidedBy'])->latest()->limit(50)->get(),'auditLogs'=>InventoryAuditLog::with('user')->latest()->limit(100)->get()->each->resolveDetails(),'brokenLogs'=>InventoryBrokenLog::with(['asset','actor'])->latest()->limit(100)->get()]);}
    public function auditLogUpdate(Request $r,InventoryAuditLog $log){$d=$r->validate(['created_at'=>'nullable|date','action'=>'required|string|max:80','asset_code'=>'nullable|string|max:120','name'=>'nullable|string|max:255','quantity'=>'nullable|numeric|min:0','reason'=>'nullable|string|max:2000']);$details=(array)$log->details;$details['asset_code']=$d['asset_code']??data_get($details,'asset_code',data_get($details,'code'));$details['code']=$details['asset_code'];$details['name']=$d['name']??data_get($details,'name');$details['quantity']=$d['quantity']??data_get($details,'quantity',0);$details['reason']=$d['reason']??data_get($details,'reason');$log->update(['action'=>$d['action'],'details'=>$details,'created_at'=>$d['created_at']??$log->created_at]);return back()->with('success','Đã cập nhật nhật ký vật tư.');}
    public function auditLogDelete(InventoryAuditLog $log){$log->delete();return back()->with('success','Đã xóa nhật ký vật tư.');}
    public function movementUpdate(Request $r,InventoryMovement $movement){$movement->update($r->validate(['type'=>'required|string|max:50','quantity'=>'required|integer|min:0','reference'=>'nullable|string|max:255','note'=>'nullable|string|max:2000']));return back()->with('success','Đã cập nhật nhật ký nhập / xuất vật tư.');}
    public function movementDelete(InventoryMovement $movement){$movement->delete();return back()->with('success','Đã xóa nhật ký nhập / xuất vật tư.');}
    public function brokenLogUpdate(Request $r,InventoryBrokenLog $brokenLog){$brokenLog->update($r->validate(['event_at'=>'nullable|date','event_type'=>'required|string|max:80','asset_code'=>'nullable|string|max:120','asset_name'=>'required|string|max:255','quantity'=>'required|numeric|min:0','status_after'=>'nullable|string|max:80','reason'=>'nullable|string|max:2000','performer'=>'nullable|string|max:255']));return back()->with('success','Đã cập nhật nhật ký hỏng / sửa chữa.');}
    public function brokenLogDelete(InventoryBrokenLog $brokenLog){$brokenLog->delete();return back()->with('success','Đã xóa nhật ký hỏng / sửa chữa.');}
    public function repairUpdate(Request $r,InventoryRepair $repair){$repair->update($r->validate(['status'=>'required|in:OPEN,ASSIGNED,COMPLETED,CANCELLED','content'=>'nullable|string|max:2000','performer'=>'nullable|string|max:255','started_at'=>'nullable|date','cost'=>'nullable|numeric|min:0','result_note'=>'nullable|string|max:2000']));return back()->with('success','Đã cập nhật phiếu sửa chữa.');}
    public function repairDelete(InventoryRepair $repair){$repair->delete();return back()->with('success','Đã xóa phiếu sửa chữa.');}
    public function assignCategory(Request $r){$d=$r->validate(['user_id'=>'required|exists:users,id','category_id'=>'required|exists:inventory_categories,id']);InventoryUserCategory::firstOrCreate($d);InventoryAuditLog::create(['user_id'=>$r->user()->id,'action'=>'ASSIGN','entity_type'=>'user_category','entity_id'=>$d['category_id'],'details'=>$d]);return back()->with('success','Đã gán ngành vật tư.');}
    public function myCatalog(Request $r){$categories=InventoryCategory::withCount('materials')->whereHas('userAssignments',fn($q)=>$q->where('user_id',$r->user()->id))->orderBy('name')->get();return view('inventory::feature',['section'=>'my-catalog','title'=>'Ngành vật tư của tôi','categories'=>$categories]);}
    public function liquidation(){$liquidated=InventoryProposal::where('type','LIQUIDATION')->whereIn('status',['APPROVED','COMPLETED'])->with('items')->get()->sum(fn($p)=>$p->items->sum('quantity'));$recalled=InventoryProposal::where('type','RECALL')->whereIn('status',['APPROVED','COMPLETED'])->with('items')->get()->sum(fn($p)=>$p->items->sum('quantity'));$repaired=InventoryRepair::where('status','COMPLETED')->with('asset')->get()->sum(fn($r)=>(int)($r->asset?->quantity??0));return view('inventory::feature',['section'=>'liquidation','title'=>'Đề xuất / thanh lý','proposals'=>InventoryProposal::with(['unit','items'])->where('type','LIQUIDATION')->latest()->get(),'liquidatedQuantity'=>$liquidated,'recalledQuantity'=>$recalled,'repairedQuantity'=>$repaired,'units'=>\Modules\Unit\Models\Unit::active()->orderBy('name')->get(),'assets'=>InventoryAsset::with('classroom')->where('status','<>','LIQUIDATED')->orderBy('name')->get(),'materials'=>InventoryMaterial::with('category')->orderBy('name')->get(),'categories'=>InventoryCategory::where('active',true)->whereNull('parent_id')->orderBy('code')->get(),'types'=>InventoryCategory::where('active',true)->whereNotNull('parent_id')->orderBy('code')->get(),'classrooms'=>Classroom::active()->with('building')->orderBy('name')->get()]);}

    public function buildings(Request $r){$buildings=Building::withCount('classrooms')->when($r->filled('search'),fn($q)=>$q->where(fn($x)=>$x->where('code','like','%'.$r->search.'%')->orWhere('name','like','%'.$r->search.'%')))->when($r->filled('status'),fn($q)=>$q->where('status',$r->status))->orderBy('name')->get();return view('inventory::feature',['section'=>'inventory-buildings','title'=>'Danh mục tòa nhà','buildings'=>$buildings]);}
    public function classrooms(Request $r){$classrooms=Classroom::with(['building','managingUnit'])->withCount('inventoryAssets')->when($r->filled('search'),fn($q)=>$q->where(fn($x)=>$x->where('code','like','%'.$r->search.'%')->orWhere('name','like','%'.$r->search.'%')))->when($r->filled('building_id'),fn($q)=>$q->where('building_id',$r->building_id))->when($r->filled('floor'),fn($q)=>$q->where('floor',$r->floor))->when($r->filled('status'),fn($q)=>$q->where('status',$r->status))->orderBy('building_id')->orderBy('floor')->orderBy('name')->get();return view('inventory::feature',['section'=>'inventory-classrooms','title'=>'Danh mục phòng','classrooms'=>$classrooms,'buildings'=>Building::where('status',true)->orderBy('name')->get()]);}
    public function building(Request $r,Building $building){$classrooms=Classroom::where('building_id',$building->id)->orderBy('floor')->orderBy('name')->get();$classrooms->each(fn($room)=>$room->inventory_count=InventoryAsset::where('classroom_id',$room->id)->sum('quantity'));$selectedRoom=null;$roomData=[];if($r->filled('room')){$selectedRoom=$classrooms->firstWhere('id',(int)$r->input('room'));if($selectedRoom){$selectedRoom->load(['building','managingUnit']);$roomData=['assets'=>InventoryAsset::where('classroom_id',$selectedRoom->id)->with(['material.category.parent','holdingUnit'])->orderBy('name')->get(),'materials'=>InventoryMaterial::with('category.parent')->orderBy('name')->get(),'roomImages'=>InventoryRoomImage::where('classroom_id',$selectedRoom->id)->latest()->get(),'roomUsers'=>InventoryRoomUser::where('classroom_id',$selectedRoom->id)->with('user')->latest()->get(),'users'=>\App\Models\User::where('status',1)->orderBy('name')->get(),'breakReports'=>\Modules\Inventory\Models\InventoryRoomBreakReport::where('classroom_id',$selectedRoom->id)->with('reporter')->latest()->get(),'roomRepairs'=>\Modules\Inventory\Models\InventoryRoomRepair::where('classroom_id',$selectedRoom->id)->latest()->get(),'roomInventories'=>\Modules\Inventory\Models\InventoryRoomInventory::where('classroom_id',$selectedRoom->id)->latest()->get(),'roomReplacements'=>\Modules\Inventory\Models\InventoryRoomReplacement::where('classroom_id',$selectedRoom->id)->latest()->get(),'industries'=>InventoryCategory::whereNull('parent_id')->where('active',true)->orderBy('code')->get(),'categories'=>InventoryCategory::whereNotNull('parent_id')->where('active',true)->orderBy('code')->get(),'units'=>\Modules\Unit\Models\Unit::active()->orderBy('name')->get()];}}return view('inventory::feature',['section'=>'building','title'=>'Tòa nhà: '.$building->name,'building'=>$building,'classrooms'=>$classrooms,'selectedRoom'=>$selectedRoom]+$roomData);}
    public function room(Classroom $classroom){return view('inventory::feature',['section'=>'room','title'=>'Phòng: '.$classroom->name,'classroom'=>$classroom->load(['building','managingUnit']),'assets'=>InventoryAsset::where('classroom_id',$classroom->id)->with(['material.category.parent','holdingUnit'])->orderBy('name')->get(),'materials'=>InventoryMaterial::with('category.parent')->orderBy('name')->get(),'roomImages'=>InventoryRoomImage::where('classroom_id',$classroom->id)->latest()->get(),'roomUsers'=>InventoryRoomUser::where('classroom_id',$classroom->id)->with('user')->latest()->get(),'users'=>\App\Models\User::where('status',1)->orderBy('name')->get(),'breakReports'=>\Modules\Inventory\Models\InventoryRoomBreakReport::where('classroom_id',$classroom->id)->with('reporter')->latest()->get(),'roomRepairs'=>\Modules\Inventory\Models\InventoryRoomRepair::where('classroom_id',$classroom->id)->latest()->get(),'roomInventories'=>\Modules\Inventory\Models\InventoryRoomInventory::where('classroom_id',$classroom->id)->latest()->get(),'roomReplacements'=>\Modules\Inventory\Models\InventoryRoomReplacement::where('classroom_id',$classroom->id)->latest()->get(),'industries'=>InventoryCategory::whereNull('parent_id')->where('active',true)->orderBy('code')->get(),'categories'=>InventoryCategory::whereNotNull('parent_id')->where('active',true)->orderBy('code')->get(),'units'=>\Modules\Unit\Models\Unit::active()->orderBy('name')->get()]);}
    public function roomAssetStore(Request $r,Classroom $classroom){$d=$r->validate(['material_id'=>'required|exists:inventory_materials,id','quantity'=>'required|numeric|min:.01','holding_unit_id'=>'nullable|exists:units,id']);$material=InventoryMaterial::with('category')->findOrFail($d['material_id']);$d['holding_unit_id']=$d['holding_unit_id']?:$classroom->managing_unit_id;abort_unless($d['holding_unit_id'],'Chưa chọn đơn vị quản lý / giữ vật tư.');$asset=InventoryAsset::updateOrCreate(['material_id'=>$material->id,'classroom_id'=>$classroom->id],['asset_code'=>$material->code,'name'=>$material->name,'quantity'=>$d['quantity'],'unit'=>$material->unit,'holding_unit_id'=>$d['holding_unit_id'],'grade'=>1,'status'=>'NORMAL']);InventoryAuditLog::create(['user_id'=>auth()->id(),'action'=>'CREATE','entity_type'=>'asset','entity_id'=>$asset->id,'details'=>['reason'=>'Thêm vật tư vào phòng','quantity'=>$d['quantity'],'classroom_id'=>$classroom->id,'holding_unit_id'=>$d['holding_unit_id'],'asset_code'=>$material->code,'name'=>$material->name]]);return back()->with('success','Đã thêm vật tư có sẵn vào phòng.');}
    public function roomAssetImport(Request $r,Classroom $classroom){$d=$r->validate(['file'=>'required|file|mimes:xlsx,xls,csv,txt|max:20480','holding_unit_id'=>'nullable|exists:units,id']);$d['holding_unit_id']=$d['holding_unit_id']?:$classroom->managing_unit_id;abort_unless($d['holding_unit_id'],'Chưa chọn đơn vị quản lý / giữ vật tư.');$rows=\PhpOffice\PhpSpreadsheet\IOFactory::load($r->file('file')->getRealPath())->getActiveSheet()->toArray(null,true,true,true);$headers=array_map(fn($v)=>strtolower(trim((string)$v)),array_shift($rows));$count=0;foreach($rows as $row){$values=[];foreach($headers as $key=>$header)$values[$header]=trim((string)($row[$key]??''));if(empty($values['code']))continue;$material=InventoryMaterial::where('code',$values['code'])->first();if(!$material)continue;$asset=InventoryAsset::updateOrCreate(['material_id'=>$material->id,'classroom_id'=>$classroom->id],['asset_code'=>$material->code,'name'=>$material->name,'quantity'=>(float)($values['quantity']?:1),'unit'=>$material->unit,'holding_unit_id'=>$d['holding_unit_id'],'grade'=>1,'status'=>'NORMAL']);InventoryAuditLog::create(['user_id'=>auth()->id(),'action'=>'CREATE','entity_type'=>'asset','entity_id'=>$asset->id,'details'=>['reason'=>'Import vật tư vào phòng','quantity'=>(float)($values['quantity']?:1),'classroom_id'=>$classroom->id,'holding_unit_id'=>$d['holding_unit_id'],'asset_code'=>$material->code,'name'=>$material->name]]);$count++;}return back()->with('success',"Đã import {$count} vật tư có sẵn vào phòng.");}
    public function roomAssetUpdate(Request $r,Classroom $classroom,InventoryAsset $asset){abort_unless((int)$asset->classroom_id===(int)$classroom->id,404);$d=$r->validate(['quantity'=>'required|numeric|min:.01','holding_unit_id'=>'nullable|exists:units,id','grade'=>'required|integer|min:1|max:5','status'=>'required|in:NORMAL,BROKEN,REPAIRING,LIQUIDATED']);$before=(int)$asset->quantity;$d['holding_unit_id']=$d['holding_unit_id']?:$classroom->managing_unit_id;abort_unless($d['holding_unit_id'],'Chưa chọn đơn vị quản lý / giữ vật tư.');$asset->update($d);InventoryAuditLog::create(['user_id'=>auth()->id(),'action'=>'UPDATE','entity_type'=>'asset','entity_id'=>$asset->id,'details'=>$d+['reason'=>$r->input('reason')?:'Sửa thông tin vật tư trong phòng','before'=>$before,'after'=>(int)$asset->quantity,'quantity'=>abs((int)$asset->quantity-$before),'classroom_id'=>$classroom->id]]);return back()->with('success','Đã sửa vật tư trong phòng.');}
    public function roomAssetDelete(Request $r,Classroom $classroom,InventoryAsset $asset){abort_unless((int)$asset->classroom_id===(int)$classroom->id,404);abort_if($asset->repairs()->exists()||$asset->proposals()->exists(),422,'Không thể xóa vật tư đã có lịch sử.');$details=['reason'=>$r->input('reason')?:'Xóa vật tư khỏi phòng','quantity'=>(int)$asset->quantity,'classroom_id'=>$classroom->id,'asset_code'=>$asset->asset_code,'name'=>$asset->name];$id=$asset->id;$asset->delete();InventoryAuditLog::create(['user_id'=>auth()->id(),'action'=>'DELETE','entity_type'=>'asset','entity_id'=>$id,'details'=>$details]);return back()->with('success','Đã xóa vật tư khỏi phòng.');}
    public function roomImageStore(Request $r,Classroom $classroom){$d=$r->validate(['image'=>'required|image|max:10240','caption'=>'nullable|string|max:255']);InventoryRoomImage::create(['classroom_id'=>$classroom->id,'path'=>$r->file('image')->store('inventory-room-images'),'caption'=>$d['caption']??null,'uploaded_by'=>$r->user()->id]);return back()->with('success','Đã thêm ảnh phòng.');}
    public function roomImageDelete(InventoryRoomImage $image){abort_unless(\App\Support\PermissionCheck::can(auth()->user(),'inventory.locations.delete'),403);Storage::delete($image->path);$image->delete();return back()->with('success','Đã xóa ảnh phòng.');}
    public function roomUserStore(Request $r,Classroom $classroom){$d=$r->validate(['user_id'=>'required|exists:users,id','role'=>'nullable|string|max:100']);InventoryRoomUser::updateOrCreate(['classroom_id'=>$classroom->id,'user_id'=>$d['user_id']],['role'=>$d['role']??null]);return back()->with('success','Đã gán người phụ trách phòng.');}
    public function roomUserDelete(InventoryRoomUser $roomUser){$roomUser->delete();return back()->with('success','Đã bỏ người phụ trách phòng.');}

    public function reports(Request $r){$months=max(1,min(60,(int)$r->input('months',3)));$from=$r->input('from');$to=$r->input('to');$materials=InventoryMaterial::with(['category','building','classroom'])->when($r->filled('category_id'),fn($q)=>$q->where('category_id',$r->category_id))->when($r->filled('building_id'),fn($q)=>$q->where('building_id',$r->building_id))->when($r->filled('classroom_id'),fn($q)=>$q->where('classroom_id',$r->classroom_id))->when($r->filled('search'),fn($q,$s)=>$q->where(fn($x)=>$x->where('code','like',"%$s%")->orWhere('name','like',"%$s%")))->orderBy('name')->get();$assets=InventoryAsset::with(['classroom.building','holdingUnit'])->orderBy('name')->get();$auditLogs=InventoryAuditLog::with('user')->when($from,fn($q)=>$q->whereDate('created_at','>=',$from))->when($to,fn($q)=>$q->whereDate('created_at','<=',$to))->latest()->limit(300)->get()->each->resolveDetails();$repairs=InventoryRepair::with(['asset','assignee'])->when($from,fn($q)=>$q->whereDate('opened_at','>=',$from))->when($to,fn($q)=>$q->whereDate('opened_at','<=',$to))->latest()->get();$brokenLogs=InventoryBrokenLog::with(['asset','actor'])->when($from,fn($q)=>$q->whereDate('event_at','>=',$from))->when($to,fn($q)=>$q->whereDate('event_at','<=',$to))->latest()->get();$transfers=InventoryTransfer::with(['asset','fromClassroom','toClassroom'])->latest()->limit(100)->get();$stats=['records'=>InventoryMaterial::count(),'quantity'=>InventoryMaterial::sum('quantity'),'groups'=>InventoryCategory::where('active',true)->count(),'buildings'=>InventoryMaterial::whereNotNull('building_id')->distinct()->count('building_id'),'normal'=>InventoryAsset::where('status','NORMAL')->count(),'broken'=>InventoryAsset::whereIn('status',['BROKEN','REPAIRING'])->count()];$expiringAssets=InventoryMaterial::with(['building','classroom'])->whereNotNull('expiry_date')->whereBetween('expiry_date',[now()->toDateString(),now()->addMonths($months)->toDateString()])->orderBy('expiry_date')->get();return view('inventory::feature',['section'=>'reports','title'=>'Báo cáo vật tư','materials'=>$materials,'assets'=>$assets,'auditLogs'=>$auditLogs,'repairs'=>$repairs,'brokenLogs'=>$brokenLogs,'transfers'=>$transfers,'expiringAssets'=>$expiringAssets,'stats'=>$stats,'categories'=>InventoryCategory::where('active',true)->orderBy('name')->get(),'buildings'=>Building::orderBy('name')->get(),'classrooms'=>Classroom::active()->orderBy('name')->get(),'reportTemplates'=>InventoryReportTemplate::where('active',true)->whereNotNull('report_type')->whereNotNull('file_path')->orderBy('report_type')->orderBy('name')->get(),'defaultTemplates'=>$this->defaultReportTemplates(),'months'=>$months,'from'=>$from,'to'=>$to,'repairCount'=>$repairs->whereIn('status',['OPEN','ASSIGNED'])->count()]);}
    public function reportWord(Request $r){return app(InventoryReportTemplateController::class)->download($r);}
    public function reportCsv(){$rows=InventoryMaterial::orderBy('name')->get();$callback=function()use($rows){$out=fopen('php://output','w');fputcsv($out,['No','Code','Name','Unit','Quantity','Location']);foreach($rows as $i=>$m)fputcsv($out,[$i+1,$m->code,$m->name,$m->unit,$m->quantity,$m->location]);fclose($out);};return response()->streamDownload($callback,'inventory-report.csv',['Content-Type'=>'text/csv; charset=UTF-8']);}
    public function templates(){$templates=InventoryReportTemplate::latest()->get();$deletedTypes=$templates->whereNotNull('report_type')->where('active',false)->whereNull('file_path')->pluck('report_type')->all();$customTemplates=$templates->whereNotNull('report_type')->filter(fn($template)=>$template->active&&$template->file_path)->groupBy('report_type')->map(fn($items)=>$items->first());$defaultTemplates=collect($this->defaultReportTemplates())->reject(fn($template,$type)=>in_array($type,$deletedTypes,true))->all();foreach($defaultTemplates as $type=>$template){if($custom=$customTemplates->get($type)){$defaultTemplates[$type]['name']=$custom->name?:$template['name'];$defaultTemplates[$type]['report']=$custom->description?:($template['report']??$template['name']);}}return view('inventory::feature',['section'=>'templates','title'=>'Mẫu báo cáo Word','templates'=>$templates,'uploadTemplates'=>$templates->whereNull('report_type'),'customTemplates'=>$customTemplates,'defaultTemplates'=>$defaultTemplates]);}
    public function templateStore(Request $r){$d=$r->validate(['code'=>'required|string|max:80|unique:inventory_report_templates,code','report_type'=>'nullable|string|max:80','name'=>'required|string|max:255','description'=>'nullable|string','file'=>'required|file|mimes:docx|max:10240','active'=>'nullable|boolean']);$type=$d['report_type']??$this->inferVariableTemplateType($d['code'],$d['name'],$d['description']??'');$d['report_type']=$type;$d['file_path']=$this->storeVariableTemplateFile($r->file('file')->getRealPath(),$type,$d['code'],$r->file('file')->getClientOriginalName());$d['active']=$r->boolean('active',true);unset($d['file']);InventoryReportTemplate::updateOrCreate(['report_type'=>$type],$d);return back()->with('success','Đã lưu mẫu Word báo cáo vật tư. Khi xuất đúng loại báo cáo này sẽ dùng mẫu vừa import.');}
    public function templateUpdate(Request $r,InventoryReportTemplate $template){$d=$r->validate(['code'=>['required','string','max:80',Rule::unique('inventory_report_templates','code')->ignore($template->id)],'report_type'=>'nullable|string|max:80','name'=>'required|string|max:255','description'=>'nullable|string','file'=>'nullable|file|mimes:docx|max:10240','active'=>'nullable|boolean']);$oldPath=$template->file_path;$type=$d['report_type']??$template->report_type??$this->inferVariableTemplateType($d['code'],$d['name'],$d['description']??'');$d['report_type']=$type;if($r->hasFile('file'))$d['file_path']=$this->storeVariableTemplateFile($r->file('file')->getRealPath(),$type,$d['code'],$r->file('file')->getClientOriginalName());$d['active']=$r->boolean('active');unset($d['file']);$template->update($d);if(isset($d['file_path'])&&$oldPath)Storage::disk('local')->delete($oldPath);return back()->with('success','Đã cập nhật mẫu báo cáo vật tư.');}
    public function templateReplaceDefault(Request $r,string $type){$defaults=$this->defaultReportTemplates();abort_unless(isset($defaults[$type]),404,'Không tìm thấy loại mẫu báo cáo.');$d=$r->validate(['name'=>'required|string|max:255','report_type'=>'required|string|max:80','file'=>'nullable|file|mimes:docx|max:10240','active'=>'nullable|boolean']);$selectedType=$d['report_type'];abort_unless(isset($defaults[$selectedType]),404,'Không tìm thấy loại báo cáo đã chọn.');$default=$defaults[$selectedType];$code='DEFAULT_'.strtoupper(str_replace('-','_',$selectedType));$old=InventoryReportTemplate::where('report_type',$selectedType)->first();$oldPath=$old?->file_path;$description=($default['report']??$default['name']).(isset($default['scope'])?' - '.$default['scope']:'');$payload=['code'=>$old?->code?:$code,'report_type'=>$selectedType,'name'=>$d['name'],'description'=>$description,'active'=>$r->boolean('active',true)];if($r->hasFile('file'))$payload['file_path']=$this->storeVariableTemplateFile($r->file('file')->getRealPath(),$selectedType,$payload['code'],$r->file('file')->getClientOriginalName());elseif($old?->file_path)$payload['file_path']=$old->file_path;InventoryReportTemplate::updateOrCreate(['report_type'=>$selectedType],$payload);if(isset($payload['file_path'])&&$r->hasFile('file')&&$oldPath&&$oldPath!==$payload['file_path'])Storage::disk('local')->delete($oldPath);return back()->with('success','Đã cập nhật mẫu '.$description.'. Lần xuất báo cáo tương ứng sẽ dùng cấu hình vừa lưu.');}
    public function templateDeleteDefault(string $type){$defaults=$this->defaultReportTemplates();abort_unless(isset($defaults[$type]),404,'Không tìm thấy loại mẫu báo cáo.');$templates=InventoryReportTemplate::where('report_type',$type)->get();foreach($templates as $template){if($template->file_path)Storage::disk('local')->delete($template->file_path);$template->delete();}$this->deleteGeneratedVariableTemplates($type);InventoryReportTemplate::create(['code'=>'DELETED_'.strtoupper(str_replace('-','_',$type)),'report_type'=>$type,'name'=>$defaults[$type]['name'],'description'=>'Đã xóa mẫu, không dùng mẫu mặc định.','file_path'=>null,'active'=>false]);return back()->with('success','Đã xóa hoàn toàn mẫu báo cáo này. Hệ thống sẽ không tự quay về file mặc định.');}
    public function templateDownload(InventoryReportTemplate $template){$source=$template->absolutePath();abort_unless($source&&is_file($source),404,'Không tìm thấy file mẫu.');return response()->download($source,basename((string)$template->file_path));}
    public function defaultTemplateDownload(string $type)
    {
        $templates = $this->defaultReportTemplates();
        abort_unless(isset($templates[$type]), 404, 'Không tìm thấy mẫu mặc định.');

        $configured = InventoryReportTemplate::where('report_type', $type)->latest()->first();
        abort_if($configured && !$configured->active && !$configured->file_path, 404, 'Mẫu báo cáo này đã bị xóa.');
        if ($configured && $configured->active && $configured->file_path) {
            $source = $configured->absolutePath();
            abort_unless($source && is_file($source), 404, 'File mẫu đang dùng không tồn tại.');
            return response()->download($source, basename((string) $configured->file_path));
        }

        $template = $templates[$type];
        $filename = $template['variable_file'] ?? ('mau-bien-'.$type.'.docx');
        $path = storage_path('app/'.$filename);
        if (in_array($type, ['position', 'total-position', 'unit', 'using-position', 'using-total'], true)) {
            $this->createInventoryStatusVariableTemplate($path);
        } else {
            $this->writeVariableTemplateZip(resource_path('inventory-report-templates/'.$template['file']),$path,$type);
        }

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
    private function deleteGeneratedVariableTemplates(string $type): void
    {
        $patterns = [
            'position' => ['mau-bien-theo-vi-tri*.docx', 'Mau_bien_Thong_ke_thuc_luc_hien_co_theo_vi_tri*.docx'],
            'total-position' => ['mau-bien-tong-hop-toan-bo*.docx', 'Mau_bien_Thong_ke_thuc_luc_hien_co_tong_hop*.docx'],
            'unit' => ['Mau_bien_Don_vi*.docx'],
            'using-position' => ['mau-bien-vat-tu-dang-su-dung-theo-vi-tri*.docx', 'Mau_bien_Vat_tu_dang_su_dung_theo_vi_tri*.docx'],
            'using-total' => ['mau-bien-vat-tu-dang-su-dung-tong-hop*.docx', 'Mau_bien_Toan_he_thong*.docx'],
        ][$type] ?? [];

        $directory = Storage::disk('local')->path('inventory-templates');
        foreach ($patterns as $pattern) {
            foreach (glob($directory.DIRECTORY_SEPARATOR.$pattern) ?: [] as $path) {
                if (is_file($path)) @unlink($path);
            }
        }
    }
    public function templateDelete(InventoryReportTemplate $template){$path=$template->file_path;$template->delete();if($path)Storage::disk('local')->delete($path);return back()->with('success','Đã xóa mẫu báo cáo vật tư.');}
    private function storeVariableTemplateFile(string $source,string $type,string $code,?string $originalName=null): string
    {
        Storage::disk('local')->makeDirectory('inventory-templates');
        $filename=$originalName?:((trim($code)?:'mau-bao-cao-vat-tu').'.docx');
        $filename=preg_replace('/[\\\\\/:*?"<>|]+/','-',trim($filename));
        if(strtolower(pathinfo($filename,PATHINFO_EXTENSION))!=='docx')$filename=pathinfo($filename,PATHINFO_FILENAME).'.docx';
        $relative='inventory-templates/'.$filename;
        if(Storage::disk('local')->exists($relative)){
            $relative='inventory-templates/'.pathinfo($filename,PATHINFO_FILENAME).'-'.now()->format('YmdHis').'.docx';
        }
        $target=Storage::disk('local')->path($relative);
        $this->wordFileHasVariables($source)?copy($source,$target):$this->writeVariableTemplateZip($source,$target,$type);
        return $relative;
    }
    private function wordFileHasVariables(string $path): bool
    {
        $zip=new \ZipArchive();
        if($zip->open($path)!==true)return false;
        $xml=$zip->getFromName('word/document.xml')?:'';
        $zip->close();
        return str_contains($xml,'${')||str_contains($xml,'{{');
    }
    private function createInventoryStatusVariableTemplate(string $path): void
    {
        $word = new \PhpOffice\PhpWord\PhpWord();
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize(10);
        $unitCount = max(1, InventoryAsset::with(['classroom.managingUnit', 'holdingUnit'])->get()
            ->map(fn ($asset) => $asset->classroom?->managingUnit ?: $asset->holdingUnit)
            ->filter()
            ->reject(fn ($unit) => mb_strtoupper(trim((string) ($unit->abbreviation ?: $unit->code ?: $unit->name)), 'UTF-8') === 'KHO')
            ->unique('id')
            ->count()) + 1;
        $unitColumnWidth = $this->inventoryStatusUnitColumnWidth($unitCount);
        $pageWidth = max(23811, array_sum(self::INVENTORY_STATUS_FIXED_WIDTHS) + ($unitColumnWidth * $unitCount) + 1800);
        $section = $word->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => $pageWidth,
            'pageSizeH' => 16838,
            'marginTop' => 650,
            'marginBottom' => 650,
            'marginLeft' => 650,
            'marginRight' => 650,
        ]);

        $normal = ['name' => 'Times New Roman', 'size' => 10];
        $bold = ['name' => 'Times New Roman', 'size' => 10, 'bold' => true];
        $title = ['name' => 'Times New Roman', 'size' => 13, 'bold' => true];
        $small = ['name' => 'Times New Roman', 'size' => 5];
        $smallBold = ['name' => 'Times New Roman', 'size' => 5, 'bold' => true];
        $center = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];
        $right = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT];
        $left = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT];

        $head = $section->addTable(['borderSize' => 0, 'cellMargin' => 0, 'width' => 100 * 50, 'unit' => 'pct']);
        $head->addRow();
        $head->addCell(8000)->addText('TỔNG CỤC HẬU CẦN', $bold, $center);
        $head->addCell(8000)->addText('CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM', $bold, $center);
        $head->addRow();
        $head->addCell(8000)->addText('TRƯỜNG CAO ĐẲNG HẬU CẦN 2', $bold, $center);
        $head->addCell(8000)->addText('Độc lập - Tự do - Hạnh phúc', ['bold' => true, 'underline' => 'single'] + $normal, $center);
        $head->addRow();
        $head->addCell(8000)->addText('Số: ${so_van_ban}/BC-CDHC', $normal, $center);
        $head->addCell(8000)->addText('Thành phố Hồ Chí Minh, ngày ${ngay} tháng ${thang} năm ${nam_hien_tai}', ['italic' => true] + $normal, $center);

        $section->addTextBreak(1);
        $section->addText('BÁO CÁO THỐNG KÊ THỰC LỰC VẬT TƯ, TRANG BỊ KỸ THUẬT HIỆN CÓ', $title, $center);
        $section->addText('Số liệu đến ngày: ${ngay_bao_cao}', ['italic' => true, 'bold' => true, 'size' => 11, 'name' => 'Times New Roman'], $center);
        $section->addTextBreak(1);

        $tableStyle = ['borderSize' => 6, 'borderColor' => '222222', 'cellMargin' => 45, 'width' => 100 * 50, 'unit' => 'pct'];
        $table = $section->addTable($tableStyle);
        $table->addRow(520);
        $unitHeaderCell = ['textDirection' => \PhpOffice\PhpWord\Style\Cell::TEXT_DIR_BTLR];
        $this->addTemplateCell($table, 'MÃ SỐ', 900, $smallBold, $center, ['vMerge' => 'restart']);
        $this->addTemplateCell($table, 'TÊN VẬT TƯ TRANG BỊ', 3000, $smallBold, $center, ['vMerge' => 'restart']);
        $this->addTemplateCell($table, 'ĐVT', 430, $smallBold, $center, ['vMerge' => 'restart']);
        $this->addTemplateCell($table, "Phâ\nn", 480, $smallBold, $center, ['vMerge' => 'restart']);
        $this->addTemplateCell($table, 'Thực lực ngày ${ngay_bao_cao}', 720, $smallBold, $center, ['vMerge' => 'restart']);
        $this->addTemplateCell($table, 'THỰC LỰC CÁC ĐƠN VỊ', $unitColumnWidth * $unitCount, $smallBold, $center, ['gridSpan' => $unitCount]);

        $table->addRow(420);
        $this->addTemplateCell($table, '', 1050, $small, $center, ['vMerge' => 'continue']);
        $this->addTemplateCell($table, '', 3500, $small, $center, ['vMerge' => 'continue']);
        $this->addTemplateCell($table, '', 520, $small, $center, ['vMerge' => 'continue']);
        $this->addTemplateCell($table, '', 650, $small, $center, ['vMerge' => 'continue']);
        $this->addTemplateCell($table, '', 900, $small, $center, ['vMerge' => 'continue']);
        for ($i = 1; $i <= $unitCount; $i++) {
            $this->addTemplateCell($table, $i === $unitCount ? 'KHO' : '${don_vi_'.$i.'}', $unitColumnWidth, $smallBold, $center, $unitHeaderCell);
        }

        $unitValues = [];
        for ($i = 1; $i <= $unitCount; $i++) {
            $unitValues[] = $i === $unitCount ? '${kho_so_luong}' : '${don_vi_'.$i.'_so_luong}';
        }

        $this->addTemplateRow($table, ['', 'VẬT TƯ, TRANG BỊ KỸ THUẬT', '', '', ''], $unitValues, $smallBold, $center, $unitColumnWidth);
        $this->addTemplateRow($table, ['${ma_nganh}', '* ${nganh}', '', '', '${so_luong_nganh}'], $unitValues, $smallBold, $left, $unitColumnWidth);
        $this->addTemplateRow($table, ['${ma_loai_vat_tu}', '${loai_vat_tu}', '', '', '${so_luong_loai}'], $unitValues, $smallBold, $left, $unitColumnWidth);
        $this->addTemplateRow($table, ['${ma_vat_tu}', '${ten_vat_tu}', '${don_vi_tinh}', '${phan_cap}', '${so_luong}'], $unitValues, $small, $left, $unitColumnWidth);
        $this->addTemplateRow($table, ['', '${ma_phong}', '', '', '${so_luong_phong}'], $unitValues, $small, $left, $unitColumnWidth);
        $this->addTemplateRow($table, ['', 'TỔNG CỘNG', '', '', '${tong_so_luong}'], $unitValues, $smallBold, $left, $unitColumnWidth);

        $section->addTextBreak(1);
        $foot = $section->addTable(['borderSize' => 0, 'cellMargin' => 0, 'width' => 100 * 50, 'unit' => 'pct']);
        $foot->addRow();
        $leftCell = $foot->addCell(8000);
        $leftCell->addText('Nơi nhận:', $bold, $left);
        $leftCell->addText('- ${noi_nhan};', $normal, $left);
        $leftCell->addText('- Lưu: VT, HC;', $normal, $left);
        $rightCell = $foot->addCell(8000);
        $rightCell->addText('${chuc_danh_ky}', $bold, $center);
        $rightCell->addText('(Ký, ghi rõ họ tên, cấp bậc)', ['italic' => true] + $normal, $center);
        $rightCell->addTextBreak(2);
        $rightCell->addText('${nguoi_ky}', $bold, $center);

        (new \PhpOffice\PhpWord\Writer\Word2007($word))->save($path);
    }
    private function addTemplateCell($table,string $text,int $width,array $font,array $paragraph,array $style=[]): void
    {
        $cell = $table->addCell($width, array_merge(['valign' => 'center'], $style));
        foreach (explode("\n", $text) as $line) {
            $cell->addText($line, $font, $paragraph);
        }
    }
    private function addTemplateRow($table,array $fixed,array $unitValues,array $font,array $paragraph,int $unitColumnWidth = 360): void
    {
        $table->addRow(420);
        $widths = self::INVENTORY_STATUS_FIXED_WIDTHS;
        foreach ($fixed as $index => $value) {
            $this->addTemplateCell($table, $value, $widths[$index] ?? 700, $font, $index === 1 ? $paragraph : ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        }
        foreach ($unitValues as $value) {
            $this->addTemplateCell($table, $value, $unitColumnWidth, $font, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        }
    }
    private function inventoryStatusUnitColumnWidth(int $unitCount): int
    {
        return 360;
    }
    private function writeVariableTemplateZip(string $sourcePath,string $targetPath,string $type): void
    {
        $source=new \ZipArchive();$target=new \ZipArchive();
        abort_unless($source->open($sourcePath)===true&&$target->open($targetPath,\ZipArchive::CREATE|\ZipArchive::OVERWRITE)===true,500,'Không tạo được file mẫu biến.');
        $documentXml=$source->getFromName('word/document.xml');
        abort_unless($documentXml!==false,500,'File mẫu Word không hợp lệ.');
        $xml=new \DOMDocument();$xml->loadXML($documentXml);
        $xpath=new \DOMXPath($xml);$xpath->registerNamespace('w','http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $this->replaceVariableTemplateDate($xpath);
        in_array($type,['position','total-position','unit','using-position','using-total'],true)
            ? $this->variableizePositionTemplate($xml,$xpath)
            : $this->variableizeSimpleTemplate($xml,$xpath,$type);
        $documentXml=$xml->saveXML();
        for($i=0;$i<$source->numFiles;$i++){$name=$source->getNameIndex($i);$target->addFromString($name,$name==='word/document.xml'?$documentXml:$source->getFromIndex($i));}
        $source->close();$target->close();
    }
    private function replaceVariableTemplateDate(\DOMXPath $xpath): void
    {
        foreach($xpath->query('//w:t') as $text){
            $value=$text->nodeValue;
            $value=preg_replace('/Số:\s*(TEST-\d+|\.{2,}\/BC-CDHC)/u','Số: ${so_van_ban}',$value);
            $value=preg_replace('/ngày\s+\d{1,2}\s+tháng\s+\d{1,2}\s+năm\s+\d{4}/u','ngày ${ngay} tháng ${thang} năm ${nam_hien_tai}',$value);
            $value=preg_replace('/ngày\s+\d{1,2}\/\d{1,2}\/\d{4}/u','ngày ${ngay_bao_cao}',$value);
            $value=preg_replace('/\d{1,2}\/\d{1,2}\/\d{4}/u','${ngay_bao_cao}',$value);
            $value=preg_replace('/Số liệu từ ngày\s+…\s+đến ngày\s+…/u','Số liệu từ ngày ${tu_ngay} đến ngày ${den_ngay}',$value);
            $value=str_replace('Phạm vi: Toàn trường','Phạm vi: ${pham_vi}',$value);
            $text->nodeValue=$value;
        }
    }
    private function variableizePositionTemplate(\DOMDocument $xml,\DOMXPath $xpath): void
    {
        $tables=$xpath->query('//w:tbl');$table=$tables->item(1)?:$tables->item(0);if(!$table)return;
        $rows=$xpath->query('./w:tr',$table);if($rows->length<3)return;
        $fixedColumns=$this->positionFixedColumnCount($xpath,$rows->item(0),$rows->item(1));
        if($header=$rows->item(1)){
            $cells=$xpath->query('./w:tc',$header);
            for($i=$fixedColumns;$i<$cells->length;$i++){
                $isLastUnit=$i===$cells->length-1;
                $this->setCellText($xpath,$cells->item($i),$isLastUnit?'KHO':'${don_vi_'.($i-$fixedColumns+1).'}');
            }
        }
        $industryRow=$this->positionDataRow($xpath,$rows,'industry')?:$rows->item(min(3,$rows->length-1));
        $categoryRow=$this->positionDataRow($xpath,$rows,'category')?:$rows->item(min(4,$rows->length-1));
        $itemRow=$this->positionDataRow($xpath,$rows,'item')?:$rows->item(min(5,$rows->length-1));
        $buildingRow=$this->positionDataRow($xpath,$rows,'building')?:$categoryRow;
        $roomRow=$this->positionDataRow($xpath,$rows,'room')?:$buildingRow;
        $totalRow=$this->positionDataRow($xpath,$rows,'total')?:$rows->item($rows->length-1);
        $industryTemplate=$industryRow->cloneNode(true);$categoryTemplate=$categoryRow->cloneNode(true);$itemTemplate=$itemRow->cloneNode(true);$buildingTemplate=$buildingRow->cloneNode(true);$roomTemplate=$roomRow->cloneNode(true);$totalTemplate=$totalRow->cloneNode(true);
        for($i=$rows->length-1;$i>=2;$i--)$table->removeChild($rows->item($i));
        $unitVars=$this->unitVariableValues($xpath,$rows->item(1),$fixedColumns);
        $industryValues=$fixedColumns===5?[null,'${nganh}',null,null,'${so_luong_nganh}']:[null,'${nganh}',null,'${so_luong_nganh}'];
        $categoryValues=$fixedColumns===5?[null,'${loai_vat_tu}',null,null,'${so_luong_loai}']:[null,'${loai_vat_tu}',null,'${so_luong_loai}'];
        $itemValues=$fixedColumns===5?['${ma_vat_tu}','${ten_vat_tu}','${don_vi_tinh}','${phan_cap}','${so_luong}']:['${ma_vat_tu}','${ten_vat_tu}','${don_vi_tinh}','${so_luong}'];
        $buildingValues=$fixedColumns===5?[null,'${toa_nha}',null,null,'${so_luong_toa_nha}']:[null,'${toa_nha}',null,'${so_luong_toa_nha}'];
        $roomValues=$fixedColumns===5?[null,'${phong}',null,null,'${so_luong_phong}']:[null,'${phong}',null,'${so_luong_phong}'];
        $totalValues=$fixedColumns===5?[null,'TỔNG CỘNG',null,null,'${tong_so_luong}']:[null,'TỔNG CỘNG',null,'${tong_so_luong}'];
        $this->setTemplateRow($xml,$industryTemplate,array_merge($industryValues,$unitVars),$table);
        $this->setTemplateRow($xml,$categoryTemplate,array_merge($categoryValues,$unitVars),$table);
        $this->setTemplateRow($xml,$itemTemplate,array_merge($itemValues,$unitVars),$table);
        $this->setTemplateRow($xml,$buildingTemplate,array_merge($buildingValues,$unitVars),$table);
        $this->setTemplateRow($xml,$roomTemplate,array_merge($roomValues,$unitVars),$table);
        $this->setTemplateRow($xml,$totalTemplate,array_merge($totalValues,$unitVars),$table);
    }
    private function positionDataRow(\DOMXPath $xpath,\DOMNodeList $rows,string $kind): ?\DOMNode
    {
        for($i=2;$i<$rows->length;$i++){
            $cells=$xpath->query('./w:tc',$rows->item($i));if($cells->length<2)continue;
            $values=[];foreach($cells as $cell){$values[]=trim(preg_replace('/\s+/u',' ',$cell->textContent));}
            $first=$values[0]??'';$second=$values[1]??'';$third=$values[2]??'';$fourth=$values[3]??'';
            if($kind==='total'&&mb_strtoupper($second,'UTF-8')==='TỔNG CỘNG')return $rows->item($i);
            if(mb_strtoupper($second,'UTF-8')==='TỔNG CỘNG')continue;
            if($kind==='item'&&$first!==''&&$second!==''&&$third!==''&&is_numeric(str_replace(',','.',$fourth)))return $rows->item($i);
            if($kind==='building'&&$first===''&&preg_match('/^\d+\.\s+/u',$second))return $rows->item($i);
            if($kind==='room'&&$first===''&&str_starts_with($second,'|'))return $rows->item($i);
            if($kind==='industry'&&$first===''&&$second!==''&&is_numeric(str_replace(',','.',$fourth))&&$this->positionDataRow($xpath,$rows,'item')&&$i<$this->positionRowIndex($rows,$this->positionDataRow($xpath,$rows,'item')))return $rows->item($i);
            if($kind==='category'&&$first===''&&$second!==''&&is_numeric(str_replace(',','.',$fourth))&&$this->positionDataRow($xpath,$rows,'item')&&$i===$this->positionRowIndex($rows,$this->positionDataRow($xpath,$rows,'item'))-1)return $rows->item($i);
        }
        return null;
    }
    private function positionRowIndex(\DOMNodeList $rows,\DOMNode $target): int
    {
        for($i=0;$i<$rows->length;$i++)if($rows->item($i)->isSameNode($target))return $i;
        return -1;
    }
    private function variableizeSimpleTemplate(\DOMDocument $xml,\DOMXPath $xpath,string $type): void
    {
        $tables=$xpath->query('//w:tbl');$indexes=$tables->length>1?(in_array($type,['warehouse','system-warehouse'],true)?[1,2]:[1]):[0];
        foreach($indexes as $tableIndex){$table=$tables->item($tableIndex);if(!$table)continue;$rows=$xpath->query('./w:tr',$table);if(!$rows->length)continue;$headerRows=$tables->length===1?2:1;$templateIndex=in_array($type,['increase-decrease','period'],true)?min(3,$rows->length-1):min($headerRows,$rows->length-1);$templateRow=$rows->item($templateIndex)->cloneNode(true);$totalRow=$rows->item($rows->length-1)->cloneNode(true);$removeFrom=in_array($type,['increase-decrease','period'],true)?2:$headerRows;for($i=$rows->length-1;$i>=$removeFrom;$i--)$table->removeChild($rows->item($i));$this->setTemplateRow($xml,$templateRow,$this->tableVariables($type,$xpath,$templateRow),$table);$this->setTemplateRow($xml,$totalRow,$this->totalVariables($xpath,$totalRow),$table);}
    }
    private function tableVariables(string $type,\DOMXPath $xpath,\DOMNode $row): array
    {
        $cells=$xpath->query('./w:tc',$row)->length;$map=[
            'increase-decrease'=>['${ten_vat_tu}','${don_vi_tinh}','${phan_cap}','${so_luong_tang}','${so_luong_giam}','${tren_cap}','${mua_sam}','${tang_phan_cap}','${kiem_ke_tang}','${tang_khac}','${tra_tren}','${hao_hut}','${thanh_ly}','${hu_hong}','${kiem_ke_giam}','${giam_khac}'],
            'period'=>['${stt}','${ngay_du_lieu}','${loai_bien_dong}','${ten_vat_tu}','${so_luong}','${ghi_chu}'],
            'transfer'=>['${stt}','${ten_vat_tu}','${don_vi_tinh}','${phan_cap}','${so_luong}','${ghi_chu}'],
            'recall'=>['${stt}','${ten_vat_tu}','${don_vi_tinh}','${phan_cap}','${so_luong}','${ghi_chu}'],
            'update-log'=>['${stt}','${ngay_du_lieu}','${loai_bien_dong}','${ten_vat_tu}','${so_luong}','${truoc}','${sau}','${vi_tri}','${nguoi_thuc_hien}','${ly_do}'],
            'repair'=>['${stt}','${ma_vat_tu}','${ten_vat_tu}','${don_vi_tinh}','${phan_cap}','${so_luong}','${vi_tri}','${trang_thai}','${ghi_chu}'],
        ];$values=$map[$type]??['${stt}','${ma_vat_tu}','${ten_vat_tu}','${don_vi_tinh}','${phan_cap}','${so_luong}','${toa_nha}','${phong}','${trang_thai}','${ghi_chu}'];return array_pad(array_slice($values,0,$cells),$cells,'');
    }
    private function totalVariables(\DOMXPath $xpath,\DOMNode $row): array
    {
        $cells=$xpath->query('./w:tc',$row)->length;return array_pad([null,'TỔNG CỘNG',null,'${tong_so_luong}'],$cells,'');
    }
    private function positionFixedColumnCount(\DOMXPath $xpath,?\DOMNode $firstHeader,?\DOMNode $secondHeader): int
    {
        $text='';
        foreach([$firstHeader,$secondHeader] as $row){
            if(!$row)continue;
            foreach($xpath->query('.//w:t',$row) as $node)$text.=' '.$node->nodeValue;
        }
        return str_contains(mb_strtolower($text,'UTF-8'),'phâ')||str_contains($text,'${phan_cap}')?5:4;
    }
    private function unitVariableValues(\DOMXPath $xpath,?\DOMNode $header,int $fixedColumns=4): array
    {
        if(!$header)return [];$count=max(0,$xpath->query('./w:tc',$header)->length-$fixedColumns);$values=[];for($i=1;$i<=$count;$i++)$values[]='${don_vi_'.$i.'_so_luong}';return $values;
    }
    private function setCellText(\DOMXPath $xpath,\DOMNode $cell,string $value): void
    {
        $nodes=$xpath->query('.//w:t',$cell);if(!$nodes->length)return;$nodes->item(0)->nodeValue=$value;for($i=1;$i<$nodes->length;$i++)$nodes->item($i)->nodeValue='';
    }
    private function setTemplateRow(\DOMDocument $xml,\DOMNode $row,array $values,\DOMNode $table): void
    {
        $xpath=new \DOMXPath($xml);$xpath->registerNamespace('w','http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $cells=$xpath->query('./w:tc',$row);
        foreach($cells as $index=>$cell){
            $nodes=$xpath->query('.//w:t',$cell);if(!$nodes->length)continue;
            $nodes->item(0)->nodeValue=(string)($values[$index]??'');
            for($i=1;$i<$nodes->length;$i++)$nodes->item($i)->nodeValue='';
        }
        $table->appendChild($row);
    }
    private function inferVariableTemplateType(?string $code,?string $name,?string $description=''): string
    {
        $text=mb_strtolower(($code.' '.$name.' '.$description),'UTF-8');
        return match(true){
            str_contains($text,'tang')||str_contains($text,'tăng')||str_contains($text,'giam')||str_contains($text,'giảm')=>'increase-decrease',
            str_contains($text,'ky')||str_contains($text,'kỳ')||str_contains($text,'tong hop')||str_contains($text,'tổng hợp')=>'period',
            str_contains($text,'dieu dong')||str_contains($text,'điều động')||str_contains($text,'transfer')=>'transfer',
            str_contains($text,'thu hoi')||str_contains($text,'thu hồi')||str_contains($text,'recall')=>'recall',
            str_contains($text,'sua chua')||str_contains($text,'sửa chữa')||str_contains($text,'hu hai')||str_contains($text,'hư hại')||str_contains($text,'repair')=>'repair',
            str_contains($text,'cap nhat')||str_contains($text,'cập nhật')||str_contains($text,'update')=>'update-log',
            str_contains($text,'don vi')||str_contains($text,'đơn vị')||str_contains($text,'unit')=>'unit',
            str_contains($text,'kho')||str_contains($text,'warehouse')=>'warehouse',
            str_contains($text,'su dung')||str_contains($text,'sử dụng')||str_contains($text,'using')=>'using-position',
            str_contains($text,'tong the')||str_contains($text,'tổng thể')=>'total-position',
            default=>'position',
        };
    }
    private function defaultReportTemplates(): array
    {
        return [
            'position' => ['name' => 'Theo vị trí lắp đặt', 'report' => 'Thống kê thực lực hiện có', 'scope' => 'Theo vị trí lắp đặt', 'file' => 'bao-cao-thuc-luc-hien-co-theo-vi-tri.docx', 'variable_file' => 'mau-bien-theo-vi-tri.docx'],
            'total-position' => ['name' => 'Tổng hợp toàn bộ', 'report' => 'Thống kê thực lực hiện có', 'scope' => 'Tổng hợp toàn bộ', 'file' => 'bao-cao-thuc-luc-hien-co-tong-the.docx', 'variable_file' => 'mau-bien-tong-hop-toan-bo.docx'],
            'unit' => ['name' => 'Theo đơn vị', 'report' => 'Thống kê thực lực vật tư theo đơn vị', 'file' => 'bao-cao-thuc-luc-hien-co-tong-the.docx', 'variable_file' => 'Mau_bien_Don_vi.docx'],
            'increase-decrease' => ['name' => 'Tăng, giảm', 'report' => 'Thống kê tăng, giảm thực lực vật tư', 'file' => 'bao-cao-tang-giam-thuc-luc-vat-tu.docx', 'variable_file' => 'Mau_bien_Tang_giam.docx'],
            'period' => ['name' => 'Tổng hợp theo kỳ', 'report' => 'Báo cáo tổng hợp theo kỳ', 'file' => 'bao-cao-tong-hop-thuc-luc-theo-ky.docx', 'variable_file' => 'Mau_bien_Tong_hop_theo_ky.docx'],
            'warehouse' => ['name' => 'Kho vật tư', 'report' => 'Báo cáo kho vật tư', 'file' => 'bao-cao-kho-vat-tu.docx', 'variable_file' => 'Mau_bien_Kho.docx'],
            'using-position' => ['name' => 'Theo vị trí lắp đặt', 'report' => 'Báo cáo vật tư đang sử dụng', 'scope' => 'Theo vị trí lắp đặt', 'file' => 'bao-cao-vt-dang-su-dung-vi-tri.docx', 'variable_file' => 'mau-bien-vat-tu-dang-su-dung-theo-vi-tri.docx'],
            'using-total' => ['name' => 'Tổng hợp toàn bộ', 'report' => 'Báo cáo vật tư đang sử dụng', 'scope' => 'Tổng hợp toàn bộ', 'file' => 'bao-cao-vt-dang-su-dung-tong-the.docx', 'variable_file' => 'mau-bien-vat-tu-dang-su-dung-tong-hop.docx'],
            'system-warehouse' => ['name' => 'Hệ thống kho-vật tư', 'report' => 'Báo cáo hệ thống kho-vật tư', 'file' => 'bao-cao-kho-he-thong-kho-vt.docx', 'variable_file' => 'Mau_bien_Kho_vat_tu.docx'],
            'transfer' => ['name' => 'Quyết định điều động', 'report' => 'Quyết định điều động', 'file' => 'bao-cao-quyet-dinh-dieu-dong.docx', 'variable_file' => 'Mau_bien_Phieu_dieu_dong.docx'],
            'recall' => ['name' => 'Quyết định thu hồi', 'report' => 'Quyết định thu hồi', 'file' => 'bao-cao-quyet-dinh-thu-hoi-tra-ve.docx', 'variable_file' => 'Mau_bien_Phieu_thu_hoi.docx'],
            'repair' => ['name' => 'Vật tư hư hại và sửa chữa', 'report' => 'Vật tư đang hư hại và sửa chữa', 'file' => 'bao-cao-vat-tu-dang-hu-hai-va-sua-chua.docx', 'variable_file' => 'Mau_bien_Vat_tu_hu_hai.docx'],
            'update-log' => ['name' => 'Cập nhật vật tư', 'report' => 'Cập nhật vật tư', 'file' => 'bao-cao-cap-nhat-vat-tu.docx', 'variable_file' => 'Mau_bien_Nhat_ki_cap_nhat.docx'],
        ];
    }

    private function variableTemplateColumns(string $type): array
    {
        $common = [
            'STT' => 'stt',
            'Ngày' => 'ngay_du_lieu',
            'Mã vật tư' => 'ma_vat_tu',
            'Tên vật tư' => 'ten_vat_tu',
            'Ngành' => 'nganh',
            'Loại' => 'loai_vat_tu',
            'ĐVT' => 'don_vi_tinh',
            'SL' => 'so_luong',
            'Cấp' => 'phan_cap',
            'Trạng thái' => 'trang_thai',
            'Tòa nhà' => 'toa_nha',
            'Phòng' => 'phong',
            'Đơn vị' => 'don_vi_quan_ly',
            'Vị trí' => 'vi_tri',
            'Ghi chú' => 'ghi_chu',
        ];

        if (in_array($type, ['increase-decrease', 'period', 'update-log'], true)) {
            return [
                'STT' => 'stt',
                'Ngày' => 'ngay_du_lieu',
                'Mã vật tư' => 'ma_vat_tu',
                'Tên vật tư' => 'ten_vat_tu',
                'Biến động' => 'loai_bien_dong',
                'SL' => 'so_luong',
                'Trước' => 'truoc',
                'Sau' => 'sau',
                'Vị trí' => 'vi_tri',
                'Người TH' => 'nguoi_thuc_hien',
                'Lý do' => 'ly_do',
                'Ghi chú' => 'ghi_chu',
            ];
        }

        if (in_array($type, ['transfer', 'recall'], true)) {
            return [
                'STT' => 'stt',
                'Mã vật tư' => 'ma_vat_tu',
                'Tên vật tư' => 'ten_vat_tu',
                'ĐVT' => 'don_vi_tinh',
                'SL' => 'so_luong',
                'Cấp' => 'phan_cap',
                'Phòng' => 'phong',
                'Đơn vị' => 'don_vi_quan_ly',
                'Lý do' => 'ly_do',
                'Ghi chú' => 'ghi_chu',
            ];
        }

        return $common;
    }
    public function repairAssign(Request $r,InventoryRepair $repair){$d=$r->validate(['performer'=>'required|string|max:255','started_at'=>'nullable|date','cost'=>'nullable|numeric|min:0','result_note'=>'nullable|string']);$repair->update($d+['status'=>'ASSIGNED','started_at'=>$d['started_at']??now()]);$repair->asset?->update(['status'=>'REPAIRING','repair_started_at'=>$d['started_at']??now(),'repair_performer'=>$d['performer']]);return back()->with('success','Đã phân công người sửa.');}
}
