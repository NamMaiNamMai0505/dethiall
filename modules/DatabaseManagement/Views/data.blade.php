@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-[1800px] space-y-5 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">Database Management Hub</p><h1 class="mt-1 text-2xl font-bold text-slate-900">Data Explorer</h1><p class="mt-1 text-sm text-slate-500">Xem và cập nhật bản ghi có transaction, audit log.</p></div>
        <span class="rounded-xl bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-800">SUPER ADMIN</span>
    </div>
    <div class="grid gap-5 lg:grid-cols-[300px,1fr]">
        <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><h2 class="mb-3 font-semibold text-slate-900">Bảng dữ liệu</h2><div class="max-h-[70vh] space-y-1 overflow-auto">@foreach($catalog as $item)<a href="{{ route('database-management.data', ['table' => $item['name']]) }}" class="block rounded-lg px-3 py-2 text-sm {{ $table === $item['name'] ? 'bg-blue-600 font-semibold text-white' : 'text-slate-700 hover:bg-slate-100' }}">{{ $item['name'] }} <span class="float-right text-xs opacity-60">{{ count($item['columns']) }}</span></a>@endforeach</div></aside>
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4"><div><h2 class="font-semibold text-slate-900">{{ $table ?: 'Chưa chọn bảng' }}</h2><p class="text-xs text-slate-500">{{ $rows?->total() ?? 0 }} bản ghi</p></div>@if($selected)<form class="flex gap-2"><input type="hidden" name="table" value="{{ $table }}"><input name="q" data-live-search="1" value="{{ request('q') }}" placeholder="Tìm dữ liệu..." class="w-56 rounded-xl border border-slate-300 px-3 py-2 text-sm"><button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Lọc</button></form>@endif</div>
            @if($rows && $selected)
                <div class="overflow-x-auto"><table class="min-w-full text-left text-xs"><thead class="bg-slate-50 uppercase text-slate-500"><tr>@foreach($selected['columns'] as $column)<th class="whitespace-nowrap px-3 py-3">{{ $column['name'] }}</th>@endforeach @if($primaryKey)<th class="px-3 py-3">Thao tác</th>@endif</tr></thead><tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr class="hover:bg-slate-50">@foreach($selected['columns'] as $column)<td class="max-w-xs px-3 py-2 align-top text-slate-700">{{ \Illuminate\Support\Str::limit((string) data_get($row, $column['name'], ''), 120) }}</td>@endforeach @if($primaryKey)<td class="px-3 py-2"><button type="button" data-edit-row="{{ e(json_encode((array) $row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)) }}" class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100">Sửa</button></td>@endif</tr>
                    @empty
                        <tr><td colspan="{{ count($selected['columns']) + ($primaryKey ? 1 : 0) }}" class="px-4 py-10 text-center text-slate-500">Không có dữ liệu.</td></tr>
                    @endforelse
                </tbody></table></div><div class="border-t border-slate-200 p-4">{{ $rows->links() }}</div>
            @else
                <div class="p-12 text-center text-slate-500">Chọn một bảng ở bên trái để bắt đầu.</div>
            @endif
        </section>
    </div>
</div>
@if($rows && $selected && $primaryKey)
<dialog id="db-edit-dialog" class="w-full max-w-2xl rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/40"><form id="db-edit-form" method="dialog" class="space-y-4 p-6"><div class="flex items-center justify-between"><h2 class="text-lg font-bold text-slate-900">Chỉnh sửa bản ghi</h2><button type="button" onclick="document.getElementById('db-edit-dialog').close()" class="text-xl text-slate-400">×</button></div><input type="hidden" name="table" value="{{ $table }}"><input type="hidden" name="record_key" id="db-record-key"><div id="db-edit-fields" class="grid max-h-[55vh] gap-3 overflow-auto md:grid-cols-2"></div><div class="flex justify-end gap-2"><button type="button" onclick="document.getElementById('db-edit-dialog').close()" class="rounded-xl border px-4 py-2 text-sm">Hủy</button><button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Lưu thay đổi</button></div><p id="db-edit-message" class="text-xs"></p></form></dialog>
<script>
const dbColumns=@json($selected['columns']),dbPrimaryKey=@json($primaryKey),dbDialog=document.getElementById('db-edit-dialog'),dbFields=document.getElementById('db-edit-fields');
document.querySelectorAll('[data-edit-row]').forEach((button)=>button.addEventListener('click',()=>{const row=JSON.parse(button.dataset.editRow);document.getElementById('db-record-key').value=row[dbPrimaryKey];dbFields.innerHTML='';dbColumns.filter((column)=>column.name!==dbPrimaryKey&&!column.auto_increment&&!['created_at','updated_at'].includes(column.name)).forEach((column)=>{const label=document.createElement('label');label.className='text-sm';label.innerHTML=`<span class="mb-1 block font-semibold text-slate-700">${column.name}</span>`;const input=document.createElement('input');input.name=`values[${column.name}]`;input.value=row[column.name]??'';input.className='w-full rounded-xl border border-slate-300 px-3 py-2 text-sm';label.appendChild(input);dbFields.appendChild(label);});dbDialog.showModal();}));
document.getElementById('db-edit-form').addEventListener('submit',async(event)=>{event.preventDefault();const form=event.currentTarget,message=document.getElementById('db-edit-message');const response=await fetch('{{ route('database-management.data.update') }}',{method:'PATCH',body:new FormData(form),headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});const payload=await response.json().catch(()=>({}));message.textContent=payload.message||'Không thể cập nhật.';message.className=response.ok?'text-xs text-emerald-700':'text-xs text-red-700';if(response.ok)setTimeout(()=>window.location.reload(),600);});
</script>
@endif
@include('partials.live-search')
@endsection
