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
    public function assignCategory(Request $r){$d=$r->validate(['user_id'=>'required|exists:users,id','category_id'=>'required|exists:inventory_categories,id']);InventoryUserCategory::firstOrCreate($d);InventoryAuditLog::create(['user_id'=>$r->user()->id,'action'=>'ASSIGN','entity_type'=>'user_category','entity_id'=>$d['category_id'],'details'=>$d]);return back()->with('success','Đã gán ngành vật tư.');}
    public function myCatalog(Request $r){$categories=InventoryCategory::withCount('materials')->whereHas('userAssignments',fn($q)=>$q->where('user_id',$r->user()->id))->orderBy('name')->get();return view('inventory::feature',['section'=>'my-catalog','title'=>'Ngành vật tư của tôi','categories'=>$categories]);}
    public function liquidation(){$liquidated=InventoryProposal::where('type','LIQUIDATION')->whereIn('status',['APPROVED','COMPLETED'])->with('items')->get()->sum(fn($p)=>$p->items->sum('quantity'));$recalled=InventoryProposal::where('type','RECALL')->whereIn('status',['APPROVED','COMPLETED'])->with('items')->get()->sum(fn($p)=>$p->items->sum('quantity'));$repaired=InventoryRepair::where('status','COMPLETED')->with('asset')->get()->sum(fn($r)=>(int)($r->asset?->quantity??0));return view('inventory::feature',['section'=>'liquidation','title'=>'Đề xuất / thanh lý','proposals'=>InventoryProposal::with(['unit','items'])->where('type','LIQUIDATION')->latest()->get(),'liquidatedQuantity'=>$liquidated,'recalledQuantity'=>$recalled,'repairedQuantity'=>$repaired,'units'=>\Modules\Unit\Models\Unit::active()->orderBy('name')->get(),'assets'=>InventoryAsset::with('classroom')->where('status','<>','LIQUIDATED')->orderBy('name')->get(),'materials'=>InventoryMaterial::with('category')->orderBy('name')->get(),'categories'=>InventoryCategory::where('active',true)->whereNull('parent_id')->orderBy('code')->get(),'types'=>InventoryCategory::where('active',true)->whereNotNull('parent_id')->orderBy('code')->get(),'classrooms'=>Classroom::active()->with('building')->orderBy('name')->get()]);}

    public function buildings(Request $r){$buildings=Building::withCount('classrooms')->when($r->filled('search'),fn($q)=>$q->where(fn($x)=>$x->where('code','like','%'.$r->search.'%')->orWhere('name','like','%'.$r->search.'%')))->when($r->filled('status'),fn($q)=>$q->where('status',$r->status))->orderBy('name')->get();return view('inventory::feature',['section'=>'inventory-buildings','title'=>'Danh mục tòa nhà','buildings'=>$buildings]);}
    public function classrooms(Request $r){$classrooms=Classroom::with(['building','managingUnit'])->withCount('inventoryAssets')->when($r->filled('search'),fn($q)=>$q->where(fn($x)=>$x->where('code','like','%'.$r->search.'%')->orWhere('name','like','%'.$r->search.'%')))->when($r->filled('building_id'),fn($q)=>$q->where('building_id',$r->building_id))->when($r->filled('floor'),fn($q)=>$q->where('floor',$r->floor))->when($r->filled('status'),fn($q)=>$q->where('status',$r->status))->orderBy('building_id')->orderBy('floor')->orderBy('name')->get();return view('inventory::feature',['section'=>'inventory-classrooms','title'=>'Danh mục phòng','classrooms'=>$classrooms,'buildings'=>Building::where('status',true)->orderBy('name')->get()]);}
    public function building(Request $r,Building $building){$classrooms=Classroom::where('building_id',$building->id)->orderBy('floor')->orderBy('name')->get();$classrooms->each(fn($room)=>$room->inventory_count=InventoryAsset::where('classroom_id',$room->id)->sum('quantity'));$selectedRoom=null;$roomData=[];if($r->filled('room')){$selectedRoom=$classrooms->firstWhere('id',(int)$r->input('room'));if($selectedRoom){$selectedRoom->load(['building','managingUnit']);$roomData=['assets'=>InventoryAsset::where('classroom_id',$selectedRoom->id)->with(['material.category.parent','holdingUnit'])->orderBy('name')->get(),'materials'=>InventoryMaterial::with('category.parent')->orderBy('name')->get(),'roomImages'=>InventoryRoomImage::where('classroom_id',$selectedRoom->id)->latest()->get(),'roomUsers'=>InventoryRoomUser::where('classroom_id',$selectedRoom->id)->with('user')->latest()->get(),'users'=>\App\Models\User::where('status',1)->orderBy('name')->get(),'breakReports'=>\Modules\Inventory\Models\InventoryRoomBreakReport::where('classroom_id',$selectedRoom->id)->with('reporter')->latest()->get(),'roomRepairs'=>\Modules\Inventory\Models\InventoryRoomRepair::where('classroom_id',$selectedRoom->id)->latest()->get(),'roomInventories'=>\Modules\Inventory\Models\InventoryRoomInventory::where('classroom_id',$selectedRoom->id)->latest()->get(),'roomReplacements'=>\Modules\Inventory\Models\InventoryRoomReplacement::where('classroom_id',$selectedRoom->id)->latest()->get(),'industries'=>InventoryCategory::whereNull('parent_id')->where('active',true)->orderBy('code')->get(),'categories'=>InventoryCategory::whereNotNull('parent_id')->where('active',true)->orderBy('code')->get(),'units'=>\Modules\Unit\Models\Unit::active()->orderBy('name')->get()];}}return view('inventory::feature',['section'=>'building','title'=>'Tòa nhà: '.$building->name,'building'=>$building,'classrooms'=>$classrooms,'selectedRoom'=>$selectedRoom]+$roomData);}
    public function room(Classroom $classroom){return view('inventory::feature',['section'=>'room','title'=>'Phòng: '.$classroom->name,'classroom'=>$classroom->load(['building','managingUnit']),'assets'=>InventoryAsset::where('classroom_id',$classroom->id)->with(['material.category.parent','holdingUnit'])->orderBy('name')->get(),'materials'=>InventoryMaterial::with('category.parent')->orderBy('name')->get(),'roomImages'=>InventoryRoomImage::where('classroom_id',$classroom->id)->latest()->get(),'roomUsers'=>InventoryRoomUser::where('classroom_id',$classroom->id)->with('user')->latest()->get(),'users'=>\App\Models\User::where('status',1)->orderBy('name')->get(),'breakReports'=>\Modules\Inventory\Models\InventoryRoomBreakReport::where('classroom_id',$classroom->id)->with('reporter')->latest()->get(),'roomRepairs'=>\Modules\Inventory\Models\InventoryRoomRepair::where('classroom_id',$classroom->id)->latest()->get(),'roomInventories'=>\Modules\Inventory\Models\InventoryRoomInventory::where('classroom_id',$classroom->id)->latest()->get(),'roomReplacements'=>\Modules\Inventory\Models\InventoryRoomReplacement::where('classroom_id',$classroom->id)->latest()->get(),'industries'=>InventoryCategory::whereNull('parent_id')->where('active',true)->orderBy('code')->get(),'categories'=>InventoryCategory::whereNotNull('parent_id')->where('active',true)->orderBy('code')->get(),'units'=>\Modules\Unit\Models\Unit::active()->orderBy('name')->get()]);}
    public function roomAssetStore(Request $r,Classroom $classroom){$d=$r->validate(['material_id'=>'required|exists:inventory_materials,id','quantity'=>'required|numeric|min:.01']);$material=InventoryMaterial::with('category')->findOrFail($d['material_id']);abort_unless($classroom->managing_unit_id,'Phòng chưa có đơn vị quản lý.');$d['holding_unit_id']=$classroom->managing_unit_id;$asset=InventoryAsset::updateOrCreate(['material_id'=>$material->id,'classroom_id'=>$classroom->id],['asset_code'=>$material->code,'name'=>$material->name,'quantity'=>$d['quantity'],'unit'=>$material->unit,'holding_unit_id'=>$d['holding_unit_id'],'grade'=>1,'status'=>'NORMAL']);InventoryAuditLog::create(['user_id'=>auth()->id(),'action'=>'CREATE','entity_type'=>'asset','entity_id'=>$asset->id,'details'=>['reason'=>'Thêm vật tư vào phòng','quantity'=>$d['quantity'],'classroom_id'=>$classroom->id,'asset_code'=>$material->code,'name'=>$material->name]]);return back()->with('success','Đã thêm vật tư có sẵn vào phòng.');}
    public function roomAssetImport(Request $r,Classroom $classroom){$d=$r->validate(['file'=>'required|file|mimes:xlsx,xls,csv,txt|max:20480']);abort_unless($classroom->managing_unit_id,'Phòng chưa có đơn vị quản lý.');$d['holding_unit_id']=$classroom->managing_unit_id;$rows=\PhpOffice\PhpSpreadsheet\IOFactory::load($r->file('file')->getRealPath())->getActiveSheet()->toArray(null,true,true,true);$headers=array_map(fn($v)=>strtolower(trim((string)$v)),array_shift($rows));$count=0;foreach($rows as $row){$values=[];foreach($headers as $key=>$header)$values[$header]=trim((string)($row[$key]??''));if(empty($values['code']))continue;$material=InventoryMaterial::where('code',$values['code'])->first();if(!$material)continue;$asset=InventoryAsset::updateOrCreate(['material_id'=>$material->id,'classroom_id'=>$classroom->id],['asset_code'=>$material->code,'name'=>$material->name,'quantity'=>(float)($values['quantity']?:1),'unit'=>$material->unit,'holding_unit_id'=>$d['holding_unit_id'],'grade'=>1,'status'=>'NORMAL']);InventoryAuditLog::create(['user_id'=>auth()->id(),'action'=>'CREATE','entity_type'=>'asset','entity_id'=>$asset->id,'details'=>['reason'=>'Import vật tư vào phòng','quantity'=>(float)($values['quantity']?:1),'classroom_id'=>$classroom->id,'asset_code'=>$material->code,'name'=>$material->name]]);$count++;}return back()->with('success',"Đã import {$count} vật tư có sẵn vào phòng.");}
    public function roomAssetUpdate(Request $r,Classroom $classroom,InventoryAsset $asset){abort_unless((int)$asset->classroom_id===(int)$classroom->id,404);abort_unless($classroom->managing_unit_id,'Phòng chưa có đơn vị quản lý.');$d=$r->validate(['quantity'=>'required|numeric|min:.01','grade'=>'required|integer|min:1|max:5','status'=>'required|in:NORMAL,BROKEN,REPAIRING,LIQUIDATED']);$before=(int)$asset->quantity;$d['holding_unit_id']=$classroom->managing_unit_id;$asset->update($d);InventoryAuditLog::create(['user_id'=>auth()->id(),'action'=>'UPDATE','entity_type'=>'asset','entity_id'=>$asset->id,'details'=>$d+['reason'=>$r->input('reason')?:'Sửa thông tin vật tư trong phòng','before'=>$before,'after'=>(int)$asset->quantity,'quantity'=>abs((int)$asset->quantity-$before),'classroom_id'=>$classroom->id]]);return back()->with('success','Đã sửa vật tư trong phòng.');}
    public function roomAssetDelete(Request $r,Classroom $classroom,InventoryAsset $asset){abort_unless((int)$asset->classroom_id===(int)$classroom->id,404);abort_if($asset->repairs()->exists()||$asset->proposals()->exists(),422,'Không thể xóa vật tư đã có lịch sử.');$details=['reason'=>$r->input('reason')?:'Xóa vật tư khỏi phòng','quantity'=>(int)$asset->quantity,'classroom_id'=>$classroom->id,'asset_code'=>$asset->asset_code,'name'=>$asset->name];$id=$asset->id;$asset->delete();InventoryAuditLog::create(['user_id'=>auth()->id(),'action'=>'DELETE','entity_type'=>'asset','entity_id'=>$id,'details'=>$details]);return back()->with('success','Đã xóa vật tư khỏi phòng.');}
    public function roomImageStore(Request $r,Classroom $classroom){$d=$r->validate(['image'=>'required|image|max:10240','caption'=>'nullable|string|max:255']);InventoryRoomImage::create(['classroom_id'=>$classroom->id,'path'=>$r->file('image')->store('inventory-room-images'),'caption'=>$d['caption']??null,'uploaded_by'=>$r->user()->id]);return back()->with('success','Đã thêm ảnh phòng.');}
    public function roomImageDelete(InventoryRoomImage $image){abort_unless(auth()->user()?->can('inventory.delete'),403);Storage::delete($image->path);$image->delete();return back()->with('success','Đã xóa ảnh phòng.');}
    public function roomUserStore(Request $r,Classroom $classroom){$d=$r->validate(['user_id'=>'required|exists:users,id','role'=>'nullable|string|max:100']);InventoryRoomUser::updateOrCreate(['classroom_id'=>$classroom->id,'user_id'=>$d['user_id']],['role'=>$d['role']??null]);return back()->with('success','Đã gán người phụ trách phòng.');}
    public function roomUserDelete(InventoryRoomUser $roomUser){$roomUser->delete();return back()->with('success','Đã bỏ người phụ trách phòng.');}

    public function reports(Request $r){$months=max(1,min(60,(int)$r->input('months',3)));$from=$r->input('from');$to=$r->input('to');$materials=InventoryMaterial::with(['category','building','classroom'])->when($r->filled('category_id'),fn($q)=>$q->where('category_id',$r->category_id))->when($r->filled('building_id'),fn($q)=>$q->where('building_id',$r->building_id))->when($r->filled('classroom_id'),fn($q)=>$q->where('classroom_id',$r->classroom_id))->when($r->filled('search'),fn($q,$s)=>$q->where(fn($x)=>$x->where('code','like',"%$s%")->orWhere('name','like',"%$s%")))->orderBy('name')->get();$assets=InventoryAsset::with(['classroom.building','holdingUnit'])->orderBy('name')->get();$auditLogs=InventoryAuditLog::with('user')->when($from,fn($q)=>$q->whereDate('created_at','>=',$from))->when($to,fn($q)=>$q->whereDate('created_at','<=',$to))->latest()->limit(300)->get()->each->resolveDetails();$repairs=InventoryRepair::with(['asset','assignee'])->when($from,fn($q)=>$q->whereDate('opened_at','>=',$from))->when($to,fn($q)=>$q->whereDate('opened_at','<=',$to))->latest()->get();$brokenLogs=InventoryBrokenLog::with(['asset','actor'])->when($from,fn($q)=>$q->whereDate('event_at','>=',$from))->when($to,fn($q)=>$q->whereDate('event_at','<=',$to))->latest()->get();$transfers=InventoryTransfer::with(['asset','fromClassroom','toClassroom'])->latest()->limit(100)->get();$stats=['records'=>InventoryMaterial::count(),'quantity'=>InventoryMaterial::sum('quantity'),'groups'=>InventoryCategory::where('active',true)->count(),'buildings'=>InventoryMaterial::whereNotNull('building_id')->distinct()->count('building_id'),'normal'=>InventoryAsset::where('status','NORMAL')->count(),'broken'=>InventoryAsset::whereIn('status',['BROKEN','REPAIRING'])->count()];$expiringAssets=InventoryMaterial::with(['building','classroom'])->whereNotNull('expiry_date')->whereBetween('expiry_date',[now()->toDateString(),now()->addMonths($months)->toDateString()])->orderBy('expiry_date')->get();return view('inventory::feature',['section'=>'reports','title'=>'Báo cáo vật tư','materials'=>$materials,'assets'=>$assets,'auditLogs'=>$auditLogs,'repairs'=>$repairs,'brokenLogs'=>$brokenLogs,'transfers'=>$transfers,'expiringAssets'=>$expiringAssets,'stats'=>$stats,'categories'=>InventoryCategory::where('active',true)->orderBy('name')->get(),'buildings'=>Building::orderBy('name')->get(),'classrooms'=>Classroom::active()->orderBy('name')->get(),'months'=>$months,'from'=>$from,'to'=>$to,'repairCount'=>$repairs->whereIn('status',['OPEN','ASSIGNED'])->count()]);}
    public function reportWord(Request $r){$types=['position'=>'BÁO CÁO THỐNG KÊ THỰC LỰC VẬT TƯ, TRANG BỊ KỸ THUẬT HIỆN CÓ — CHI TIẾT THEO VỊ TRÍ','total-position'=>'BÁO CÁO THỐNG KÊ THỰC LỰC VẬT TƯ, TRANG BỊ KỸ THUẬT HIỆN CÓ — TỔNG HỢP THEO TÒA','period'=>'BÁO CÁO TỔNG HỢP THỰC LỰC TRANG BỊ, VẬT TƯ KỸ THUẬT','increase-decrease'=>'BÁO CÁO TĂNG, GIẢM THỰC LỰC TRANG BỊ, VẬT TƯ KỸ THUẬT','using-position'=>'BÁO CÁO VẬT TƯ ĐANG SỬ DỤNG — THEO VỊ TRÍ','using-total'=>'BÁO CÁO VẬT TƯ ĐANG SỬ DỤNG — TỔNG THỂ','warehouse'=>'BÁO CÁO VỀ TÌNH HÌNH KHO VẬT TƯ','system-warehouse'=>'BÁO CÁO VỀ TÌNH HÌNH KHO VẬT TƯ — KHO HỆ THỐNG KHO-VT','transfer'=>'QUYẾT ĐỊNH VỀ VIỆC ĐIỀU ĐỘNG VẬT TƯ','recall'=>'QUYẾT ĐỊNH VỀ VIỆC THU HỒI, TRẢ VỀ VẬT TƯ','repair'=>'BÁO CÁO VỀ VẬT TƯ ĐANG HƯ HẠI VÀ SỬA CHỮA','update-log'=>'BÁO CÁO VỀ NHẬT KÝ CẬP NHẬT VẬT TƯ'];$word=new \PhpOffice\PhpWord\PhpWord();$word->setDefaultFontName('Arial');$section=$word->addSection();$section->addTitle($types[$r->input('report_type','position')]??$types['position'],1);$section->addText('Thời gian xuất: '.now()->format('d/m/Y H:i'));$table=$section->addTable(['borderSize'=>6,'borderColor'=>'999999']);foreach(['STT','Mã vật tư','Tên vật tư','Loại / nhóm','Tòa nhà','Phòng','Số lượng','Phân cấp','Trạng thái'] as $h)$table->addCell(1500)->addText($h);foreach(InventoryAsset::with(['classroom.building'])->orderBy('name')->get() as $i=>$a){$row=$table->addRow();foreach([$i+1,$a->asset_code,$a->name,$a->category?:'Chưa phân loại',$a->classroom?->building?->name?:'—',$a->classroom?->name?:'—',$a->quantity.' '.($a->unit?:''),$a->grade?:'—',['NORMAL'=>'Bình thường','BROKEN'=>'Hỏng','REPAIRING'=>'Đang sửa','LIQUIDATED'=>'Đã thanh lý'][$a->status]??$a->status] as $v)$row->addCell(1500)->addText((string)$v);} $path=storage_path('app/report-inventory-'.now()->format('YmdHis').'.docx');(new \PhpOffice\PhpWord\Writer\Word2007($word))->save($path);return response()->download($path,'bao-cao-vat-tu.docx')->deleteFileAfterSend(true);}
    public function reportCsv(){$rows=InventoryMaterial::orderBy('name')->get();$callback=function()use($rows){$out=fopen('php://output','w');fputcsv($out,['No','Code','Name','Unit','Quantity','Location']);foreach($rows as $i=>$m)fputcsv($out,[$i+1,$m->code,$m->name,$m->unit,$m->quantity,$m->location]);fclose($out);};return response()->streamDownload($callback,'inventory-report.csv',['Content-Type'=>'text/csv; charset=UTF-8']);}
    public function templates(){return view('inventory::feature',['section'=>'templates','title'=>'Mẫu báo cáo Word','templates'=>InventoryReportTemplate::where('active',true)->latest()->get()]);}
    public function templateStore(Request $r){$d=$r->validate(['code'=>'required|string|max:80|unique:inventory_report_templates,code','name'=>'required|string|max:255','description'=>'nullable|string','file'=>'nullable|file|mimes:doc,docx|max:10240']);if($r->hasFile('file'))$d['file_path']=$r->file('file')->store('inventory-templates');unset($d['file']);InventoryReportTemplate::create($d);return back()->with('success','Đã lưu mẫu Word.');}
    public function repairAssign(Request $r,InventoryRepair $repair){$d=$r->validate(['performer'=>'required|string|max:255','started_at'=>'nullable|date','cost'=>'nullable|numeric|min:0','result_note'=>'nullable|string']);$repair->update($d+['status'=>'ASSIGNED','started_at'=>$d['started_at']??now()]);$repair->asset?->update(['status'=>'REPAIRING','repair_started_at'=>$d['started_at']??now(),'repair_performer'=>$d['performer']]);return back()->with('success','Đã phân công người sửa.');}
}
