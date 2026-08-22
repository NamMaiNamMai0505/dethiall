@php
    $user = auth()->user();
    $canEdit = $user && ($user->can('export-templates.edit') || $user->isSuperAdmin() || $user->isManager());
    $canCreate = $user && ($user->can('export-templates.create') || $user->isSuperAdmin() || $user->isManager());
    $canDelete = $user && ($user->can('export-templates.delete') || $user->isSuperAdmin());
    $manifest = $template->latestVersion?->manifest ?? [];
    $manifestSummary = $manifest['summary'] ?? [];
    $manifestValidation = $manifest['validation'] ?? [];
    $manifestTargets = $manifest['targets'] ?? [];
@endphp

<a href="{{ route('export-templates.portal.index', ['portal' => $portal]) }}"
   class="text-sm text-teal-700 font-semibold hover:underline">← Danh sách mẫu {{ $portalLabel }}</a>

@if(session('success'))
    <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900">
        {{ $errors->first() }}
    </div>
@endif

<div class="bg-white rounded-xl shadow border p-6 mt-3">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-bold">{{ $template->name }}</h1>
                @if($template->is_active)
                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Active</span>
                @else
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{{ $template->status?->label() ?? '—' }}</span>
                @endif
            </div>
            <p class="mt-2 text-sm text-slate-600">
                {{ $portalLabel }} · {{ $template->output_format?->label() ?? 'Không xác định' }}
                · <code class="font-mono text-xs bg-slate-100 px-1 rounded">{{ $template->feature_key }}</code>
            </p>
            <p class="mt-1 text-xs font-mono text-slate-400">{{ $template->code }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($template->is_active)
                <a href="{{ route('export-templates.portal.test-export', [
                    'portal' => $portal,
                    'exportTemplate' => $template,
                ]) }}"
                   class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">
                    Test Export
                </a>
                <a href="{{ route('export-templates.portal.test-export', [
                    'portal' => $portal,
                    'exportTemplate' => $template,
                    'format' => 'pdf',
                ]) }}"
                   class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-sm font-semibold text-violet-700">
                    Test PDF
                </a>
            @endif
            @if($template->latestVersion && !$template->latestVersion->builderDocument?->schema)
                <a href="{{ route('export-templates.portal.versions.download', [
                    'portal' => $portal,
                    'exportTemplate' => $template,
                    'version' => $template->latestVersion,
                ]) }}"
                   class="rounded-lg border px-3 py-2 text-sm font-semibold text-slate-700">Download mới nhất</a>
            @endif
            @if($canCreate)
                <form method="POST"
                      action="{{ route('export-templates.portal.clone', ['portal' => $portal, 'exportTemplate' => $template]) }}"
                      data-confirm="Clone template này thành một bản nháp mới?"
                      data-confirm-title="Clone template"
                      data-confirm-ok="Clone">
                    @csrf
                    <button class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700">Clone</button>
                </form>
            @endif
            @if($canDelete)
                <form method="POST"
                      action="{{ route('export-templates.portal.destroy', ['portal' => $portal, 'exportTemplate' => $template]) }}"
                      data-confirm="Lưu trữ template này? File vẫn được giữ để phục hồi."
                      data-confirm-title="Lưu trữ template"
                      data-confirm-ok="Lưu trữ">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700">Lưu trữ</button>
                </form>
            @endif
        </div>
    </div>

    @if($template->description)
        <p class="mt-4 text-sm text-slate-600">{{ $template->description }}</p>
    @endif

    <div class="mt-5">
        <p class="text-xs font-bold uppercase text-slate-500 mb-1">Placeholder phát hiện từ file legacy</p>
        <div class="flex flex-wrap gap-1">
            @forelse($template->placeholders ?? [] as $placeholder)
                <code class="text-xs bg-slate-100 px-2 py-0.5 rounded">{{ '{'.'{'.$placeholder.'}'.'}' }}</code>
            @empty
                <span class="text-sm text-slate-400">Không có placeholder legacy.</span>
            @endforelse
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow border overflow-hidden mt-5">
    <div class="border-b px-5 py-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-slate-900">Cấu trúc template</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Manifest schema v{{ $manifest['schema_version'] ?? 'legacy' }}
                    · Parser {{ $manifest['parser'] ?? 'chưa phân tích' }}
                </p>
            </div>
            @if(array_key_exists('valid', $manifestValidation))
                @if($manifestValidation['valid'])
                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Cấu trúc hợp lệ</span>
                @else
                    <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">Cấu trúc lỗi</span>
                @endif
            @endif
        </div>
    </div>

    @if($manifest)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-5">
            <div class="rounded-lg bg-slate-50 p-3">
                <div class="text-xs uppercase text-slate-500">Target</div>
                <div class="mt-1 text-xl font-bold">{{ $manifestSummary['target_count'] ?? count($manifestTargets) }}</div>
            </div>
            <div class="rounded-lg bg-slate-50 p-3">
                <div class="text-xs uppercase text-slate-500">Phần tử</div>
                <div class="mt-1 text-xl font-bold">{{ $manifestSummary['element_count'] ?? count($manifest['elements'] ?? []) }}</div>
            </div>
            <div class="rounded-lg bg-slate-50 p-3">
                <div class="text-xs uppercase text-slate-500">Placeholder</div>
                <div class="mt-1 text-xl font-bold">{{ $manifestSummary['placeholder_count'] ?? count($manifest['placeholders'] ?? []) }}</div>
            </div>
            <div class="rounded-lg bg-slate-50 p-3">
                <div class="text-xs uppercase text-slate-500">{{ ($manifest['document']['format'] ?? '') === 'word' ? 'Part' : 'Sheet' }}</div>
                <div class="mt-1 text-xl font-bold">
                    {{ ($manifest['document']['format'] ?? '') === 'word'
                        ? count($manifest['document']['parts'] ?? [])
                        : ($manifestSummary['sheet_count'] ?? 0) }}
                </div>
            </div>
        </div>

        @if(!empty($manifestValidation['warnings']))
            <div class="mx-5 mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                @foreach($manifestValidation['warnings'] as $warning)
                    <div>• {{ $warning }}</div>
                @endforeach
            </div>
        @endif

        <div class="border-t overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Loại target</th>
                    <th class="px-4 py-3 text-left">Tên / dữ liệu</th>
                    <th class="px-4 py-3 text-left">Vị trí</th>
                    <th class="px-4 py-3 text-left">Target ref</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @forelse(array_slice($manifestTargets, 0, 100) as $target)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $target['kind'] ?? 'target' }}</td>
                        <td class="px-4 py-3">
                            {{ $target['data_key'] ?? $target['name'] ?? $target['tag'] ?? $target['alias'] ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            @if(!empty($target['sheet']))
                                {{ $target['sheet'] }}!{{ $target['address'] ?? $target['range'] ?? '' }}
                            @else
                                {{ $target['part_type'] ?? $target['part'] ?? '—' }}
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $target['ref'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-7 text-center text-slate-500">Version legacy chưa có target cấu trúc.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(count($manifestTargets) > 100)
            <p class="border-t px-5 py-3 text-xs text-slate-500">Chỉ hiển thị 100/{{ count($manifestTargets) }} target đầu tiên.</p>
        @endif
    @else
        <p class="px-5 py-6 text-sm text-slate-500">Version này được tạo trước Sprint 9 và chưa có manifest cấu trúc.</p>
    @endif
</div>

@if($canEdit)
    <div class="bg-white rounded-xl shadow border p-5 mt-5">
        <h2 class="font-bold text-slate-900">Tạo phiên bản mới</h2>
        <p class="mt-1 text-sm text-slate-500">
            File mới phải cùng loại {{ $template->output_format?->label() }}. Phiên bản được tạo ở trạng thái nháp và không tự thay thế bản Active.
        </p>
        <form method="POST"
              action="{{ route('export-templates.portal.versions.store', ['portal' => $portal, 'exportTemplate' => $template]) }}"
              enctype="multipart/form-data"
              class="mt-3 flex flex-wrap items-center gap-3">
            @csrf
            <input type="file" name="file"
                   accept="{{ $template->output_format?->value === 'word' ? '.docx' : '.xlsx,.xls,.xlsm,.xlsb' }}"
                   required class="min-w-0 flex-1 text-sm">
            <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Tải phiên bản</button>
        </form>
    </div>
@endif

<div class="bg-white rounded-xl shadow border overflow-hidden mt-5">
    <div class="border-b px-5 py-4">
        <h2 class="font-bold text-slate-900">Lịch sử phiên bản</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3 text-left">Phiên bản</th>
                <th class="px-4 py-3 text-left">File</th>
                <th class="px-4 py-3 text-left">Checksum</th>
                <th class="px-4 py-3 text-left">Người tạo</th>
                <th class="px-4 py-3 text-left">Ngày tạo</th>
                <th class="px-4 py-3 text-right">Thao tác</th>
            </tr>
            </thead>
            <tbody class="divide-y">
            @forelse($template->versions as $version)
                @php($versionActive = $version->activations->isNotEmpty())
                <tr>
                    <td class="px-4 py-3">
                        <span class="font-bold">v{{ $version->version_number }}</span>
                        @if($versionActive)
                            <span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Active</span>
                        @else
                            <span class="ml-1 text-xs text-slate-500">{{ $version->status?->label() }}</span>
                        @endif
                        <div class="mt-1 text-xs text-slate-400">{{ $version->bindings_count }} binding</div>
                        @if($version->builderDocument?->schema)
                            <span class="mt-1 inline-block rounded bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-700">Builder</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div>{{ $version->original_name }}</div>
                        <div class="text-xs text-slate-400">
                            {{ $version->file_size ? number_format($version->file_size / 1024, 1).' KB' : 'Chưa có kích thước' }}
                        </div>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-500" title="{{ $version->checksum_sha256 }}">
                        {{ $version->checksum_sha256 ? substr($version->checksum_sha256, 0, 12).'…' : '—' }}
                    </td>
                    <td class="px-4 py-3">{{ $version->creator?->name ?? 'Hệ thống' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $version->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        @if(!$version->builderDocument?->schema)
                        <a href="{{ route('export-templates.portal.versions.download', [
                            'portal' => $portal,
                            'exportTemplate' => $template,
                            'version' => $version,
                        ]) }}" class="font-semibold text-blue-600">Download</a>
                        @else
                        <a href="{{ route('export-templates.portal.builder.edit', ['portal'=>$portal, 'version'=>$version]) }}" class="font-semibold text-teal-700">Mở Builder</a>
                        @endif
                        @if(!$version->builderDocument?->schema)
                        <a href="{{ route('export-templates.portal.versions.preview', [
                            'portal' => $portal,
                            'exportTemplate' => $template,
                            'version' => $version,
                        ]) }}" class="ml-3 font-semibold text-violet-700">Preview</a>
                        <a href="{{ route('export-templates.portal.versions.bindings.index', [
                            'portal' => $portal,
                            'exportTemplate' => $template,
                            'version' => $version,
                        ]) }}" class="ml-3 font-semibold text-teal-700">Binding</a>
                        @endif
                        @if($canEdit && !$versionActive)
                            <form method="POST"
                                  action="{{ route('export-templates.portal.versions.activate', [
                                      'portal' => $portal,
                                      'exportTemplate' => $template,
                                      'version' => $version,
                                  ]) }}"
                                  class="inline ml-3"
                                  data-confirm="Kích hoạt phiên bản v{{ $version->version_number }}? Template Active hiện tại của cùng feature/loại sẽ bị thay thế."
                                  data-confirm-title="Kích hoạt phiên bản"
                                  data-confirm-ok="Kích hoạt">
                                @csrf
                                <button class="font-semibold text-emerald-700">Active</button>
                            </form>
                        @endif
                        @if($canEdit && !$version->builderDocument?->schema)
                            <form method="POST"
                                  action="{{ route('export-templates.portal.versions.analyze', [
                                      'portal' => $portal,
                                      'exportTemplate' => $template,
                                      'version' => $version,
                                  ]) }}"
                                  class="inline ml-3">
                                @csrf
                                <button class="font-semibold text-violet-700">Phân tích lại</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Chưa có phiên bản.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-xl shadow border overflow-hidden mt-5">
    <div class="border-b px-5 py-4">
        <h2 class="font-bold text-slate-900">Nhật ký gần đây</h2>
    </div>
    <div class="divide-y">
        @forelse($template->auditLogs as $log)
            <div class="flex flex-wrap justify-between gap-2 px-5 py-3 text-sm">
                <div>
                    <span class="font-mono text-xs font-semibold text-slate-700">{{ $log->action }}</span>
                    <span class="ml-2 text-slate-500">{{ $log->actor?->name ?? 'Hệ thống' }}</span>
                </div>
                <time class="text-xs text-slate-400">{{ $log->created_at?->format('d/m/Y H:i:s') }}</time>
            </div>
        @empty
            <p class="px-5 py-6 text-sm text-slate-500">Chưa có nhật ký.</p>
        @endforelse
    </div>
</div>
