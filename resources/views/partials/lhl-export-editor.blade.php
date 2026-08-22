{{--
  Biểu mẫu LHL (mẫu HK2 25-26): header/meta/3 chữ ký + preview có gạch chéo.
  Usage: @include('partials.lhl-export-editor', ['editorId' => 'lhlExportEditor'])
--}}
@php
    $editorId = $editorId ?? 'lhlExportEditor';
    $cfg = config('lhl_export', []);
    $sigSvc = app(\App\Services\DigitalSignatureService::class);
    try {
        $sigSvc->seedSystemTemplates();
        if (auth()->check()) {
            $sigSvc->claimMatchingTemplates(auth()->user());
        }
    } catch (\Throwable $e) {
        // migration chưa chạy — fallback config
    }
    $signers = $cfg['signers'] ?? [];
    $orgLeft = old('org_left', $cfg['org_left'] ?? '');
    $title = old('title', $cfg['title'] ?? 'LỊCH HUẤN LUYỆN');
    $semesterLine = old('semester_line', '');
    $respectLine = old('respect_line', $cfg['respect_line'] ?? '');
    $note = old('note', $cfg['note'] ?? '');
    $dateLine = old('date_line', 'Ngày     tháng      năm '.now()->format('Y'));
@endphp

<div id="{{ $editorId }}" class="lhl-export-editor space-y-4" data-lhl-export-editor>
    <div class="rounded-lg border border-teal-100 bg-teal-50/70 px-3 py-2 text-xs text-teal-900">
        <i class="bi bi-info-circle mr-1"></i>
        <strong>Excel:</strong> clone mẫu HK2 + gạch chéo + Ngày/Tiết + chữ ký.
        <strong>Word:</strong> hàng Tháng / <em>Tuần</em> / gạch chéo 1 đường (không chữ X).
        Chọn chữ ký từ <a href="{{ route('signatures.index') }}" class="underline font-semibold">Chữ ký số</a>.
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        {{-- Editors --}}
        <div class="space-y-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Header trái (đơn vị)</label>
                <textarea data-field="org_left" rows="3"
                          class="w-full text-sm border rounded-lg p-2 font-serif leading-snug">{{ $orgLeft }}</textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tiêu đề</label>
                    <input type="text" data-field="title" value="{{ $title }}"
                           class="w-full text-sm border rounded-lg p-2 font-semibold text-center">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Học kỳ / năm học</label>
                    <input type="text" data-field="semester_line" value="{{ $semesterLine }}"
                           placeholder="Học kỳ 2 năm học 2025-2026"
                           class="w-full text-sm border rounded-lg p-2">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Dòng kính gửi</label>
                <input type="text" data-field="respect_line" value="{{ $respectLine }}"
                       class="w-full text-sm border rounded-lg p-2">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Đơn vị / Khoa</label>
                    <input type="text" data-field="unit_name" value="{{ old('unit_name') }}"
                           class="w-full text-sm border rounded-lg p-2" placeholder="Khoa …">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Phòng học</label>
                    <input type="text" data-field="classroom" value="{{ old('classroom') }}"
                           class="w-full text-sm border rounded-lg p-2" placeholder="208-H1">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Sĩ số</label>
                    <input type="text" data-field="class_size" value="{{ old('class_size') }}"
                           class="w-full text-sm border rounded-lg p-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Số tổ</label>
                    <input type="text" data-field="groups" value="{{ old('groups') }}"
                           class="w-full text-sm border rounded-lg p-2">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Chủ nhiệm lớp</label>
                    <input type="text" data-field="class_leader" value="{{ old('class_leader') }}"
                           class="w-full text-sm border rounded-lg p-2">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Ghi chú cuối lịch</label>
                <textarea data-field="note" rows="2" class="w-full text-sm border rounded-lg p-2">{{ $note }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Dòng ngày ký</label>
                <input type="text" data-field="date_line" value="{{ $dateLine }}"
                       class="w-full text-sm border rounded-lg p-2">
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3 space-y-2.5">
                <div class="text-xs font-bold text-slate-700 flex items-center gap-2 justify-between">
                    <span><i class="bi bi-pen text-teal-700"></i> Chữ ký số — tick để ký</span>
                    <a href="{{ route('signatures.index') }}" class="text-teal-700 font-normal underline text-[11px]">Quản lý chữ ký</a>
                </div>
                <p class="text-[11px] text-slate-500 -mt-1">Chỉ hiện khung chọn chữ ký khi tick. Bỏ tick = không chèn chữ ký vị trí đó.</p>
                @foreach($signers as $s)
                    @php
                        $key = $s['key'] ?? ('s'.$loop->index);
                        $options = collect();
                        try {
                            $options = $sigSvc->optionsForExportSlot($key);
                        } catch (\Throwable $e) {
                            $options = collect();
                        }
                        $defaultOpt = $options->firstWhere('slot_key', $key) ?? $options->first();
                        $imgUrl = $defaultOpt?->imageUrl()
                            ?? asset('images/signatures/lhl/'.basename($s['image'] ?? ''));
                        $label = $s['role_line1'] ?? ('Chữ ký #'.$loop->iteration);
                    @endphp
                    <div class="lhl-signer-card rounded-xl border border-slate-200/90 bg-white shadow-sm overflow-hidden transition-all duration-200"
                         data-signer-block="{{ $key }}">
                        {{-- Tick row --}}
                        <label class="lhl-signer-tick flex items-center gap-3 px-3 py-2.5 cursor-pointer select-none hover:bg-teal-50/50 transition-colors">
                            <span class="lhl-tick-box relative inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-md border-2 border-slate-300 bg-white transition-all">
                                <input type="checkbox"
                                       data-field="signer_{{ $key }}_enabled"
                                       data-signer-enable
                                       value="1"
                                       class="peer sr-only">
                                <i class="bi bi-check-lg text-white text-sm leading-none opacity-0 scale-75 transition-all peer-checked:opacity-100 peer-checked:scale-100"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-bold text-slate-800">{{ $label }}</span>
                                <span class="block text-[10px] text-slate-500 truncate" data-signer-tick-sub>
                                    Tick để chọn chữ ký số · {{ $defaultOpt->display_name ?? ($s['name'] ?? '—') }}
                                </span>
                            </span>
                            <i class="bi bi-chevron-down text-slate-400 text-xs transition-transform duration-200" data-signer-chevron></i>
                        </label>
                        {{-- Panel: ẩn khi chưa tick --}}
                        <div class="lhl-signer-panel hidden border-t border-slate-100 bg-gradient-to-b from-slate-50/80 to-white px-3 py-3 space-y-2"
                             data-signer-panel>
                            <div class="grid grid-cols-1 sm:grid-cols-[4.5rem_1fr] gap-2 items-start">
                                <div class="rounded-lg border border-teal-100 bg-white p-1.5 flex items-center justify-center min-h-[3.5rem] shadow-inner">
                                    <img data-signer-preview-img src="{{ $imgUrl }}" alt="" class="max-h-14 object-contain" onerror="this.style.display='none'">
                                </div>
                                <div class="space-y-1.5">
                                    <select data-field="signer_{{ $key }}_id"
                                            data-signer-select
                                            class="w-full text-xs border border-slate-200 rounded-lg px-2 py-1.5 font-medium bg-white focus:border-teal-400 focus:ring-1 focus:ring-teal-200">
                                        <option value="">— Mẫu mặc định —</option>
                                        @foreach($options as $opt)
                                            <option value="{{ $opt->id }}"
                                                    data-name="{{ $opt->display_name }}"
                                                    data-role1="{{ $opt->role_line1 }}"
                                                    data-role2="{{ $opt->role_line2 }}"
                                                    data-img="{{ $opt->imageUrl() }}"
                                                    @selected($defaultOpt && $defaultOpt->id === $opt->id)>
                                                {{ $opt->display_name }}
                                                @if($opt->is_system_template) (mẫu) @endif
                                                @if($opt->user_id) · #{{ $opt->user_id }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="text" data-field="signer_{{ $key }}_role1"
                                           value="{{ $defaultOpt->role_line1 ?? ($s['role_line1'] ?? '') }}"
                                           class="w-full text-xs border border-slate-200 rounded-lg px-2 py-1 font-semibold" placeholder="Chức danh dòng 1">
                                    <input type="text" data-field="signer_{{ $key }}_role2"
                                           value="{{ $defaultOpt->role_line2 ?? ($s['role_line2'] ?? '') }}"
                                           class="w-full text-xs border border-slate-200 rounded-lg px-2 py-1" placeholder="Chức danh dòng 2">
                                    <input type="text" data-field="signer_{{ $key }}_name"
                                           value="{{ $defaultOpt->display_name ?? ($s['name'] ?? '') }}"
                                           class="w-full text-xs border border-slate-200 rounded-lg px-2 py-1 font-medium text-teal-900" placeholder="Họ tên">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Preview --}}
        <div class="rounded-xl border-2 border-dashed border-teal-200 bg-white p-4 shadow-inner min-h-[28rem] overflow-x-auto">
            <div class="text-[10px] uppercase tracking-wider text-slate-400 mb-2">Preview LHL (gần mẫu Word/Excel)</div>

            <div class="grid grid-cols-[1.1fr_1.4fr_1fr] gap-2 border-b border-slate-200 pb-2 mb-2">
                <div data-preview="org_left" class="text-center text-[10px] font-bold font-serif whitespace-pre-line leading-tight"></div>
                <div class="text-center">
                    <div data-preview="title" class="font-bold text-sm text-slate-900"></div>
                    <div data-preview="semester_line" class="text-[11px] font-semibold text-slate-700 mt-0.5"></div>
                </div>
                <div class="text-[10px] text-slate-700 space-y-0.5">
                    <div>Lớp: <span class="font-semibold text-slate-400">(từ lịch chọn)</span></div>
                    <div>Đơn vị: <span data-preview="unit_name" class="font-semibold"></span></div>
                    <div>Sĩ số: <span data-preview="class_size"></span> · Tổ: <span data-preview="groups"></span></div>
                    <div>CN: <span data-preview="class_leader"></span></div>
                    <div>Phòng: <span data-preview="classroom"></span></div>
                </div>
            </div>
            <div data-preview="respect_line" class="text-center text-[10px] text-slate-500 mb-2"></div>

            {{-- Mini grid with diagonal corner --}}
            <div class="border border-slate-300 rounded overflow-hidden text-[9px]">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-100">
                            <th class="border border-slate-300 px-1 py-1 w-8">Thứ</th>
                            <th class="border border-slate-300 px-0 py-0 w-16 relative lhl-diag-cell" title="Ngày trên-phải · Tiết dưới-trái · gạch chéo giữa">
                                <span class="lhl-diag-line" aria-hidden="true"></span>
                                <span class="absolute top-0.5 right-1 font-semibold text-[9px]">Ngày</span>
                                <span class="absolute bottom-0.5 left-1 font-semibold text-[9px]">Tiết</span>
                            </th>
                            {{-- Ô tuần: dd trên-trái · dd dưới-phải, không gạch --}}
                            <th class="border border-slate-300 px-1 py-1 relative h-12 align-top">
                                <div class="absolute top-0.5 left-1 font-bold text-[10px]">02</div>
                                <div class="absolute bottom-0.5 right-1 font-bold text-[10px]">08</div>
                            </th>
                            <th class="border border-slate-300 px-1 py-1 relative h-12">
                                <div class="absolute top-0.5 left-1 font-bold text-[10px]">09</div>
                                <div class="absolute bottom-0.5 right-1 font-bold text-[10px]">15</div>
                            </th>
                            <th class="border border-slate-300 px-1 py-1 relative h-12">
                                <div class="absolute top-0.5 left-1 font-bold text-[10px]">16</div>
                                <div class="absolute bottom-0.5 right-1 font-bold text-[10px]">22</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-slate-300 text-center font-bold" rowspan="2">2</td>
                            <td class="border border-slate-300 text-center">1÷5</td>
                            <td class="border border-slate-300 text-center font-bold text-white" style="background:#00B050">TH</td>
                            <td class="border border-slate-300 text-center font-bold" style="background:#FFFF00">GDTC</td>
                            <td class="border border-slate-300 text-center font-bold text-white" style="background:#0070C0">PL</td>
                        </tr>
                        <tr>
                            <td class="border border-slate-300 text-center">6÷9</td>
                            <td class="border border-slate-300 text-center font-bold text-white" style="background:#7030A0">HS</td>
                            <td class="border border-slate-300 text-center font-bold text-white" style="background:#FF0000">SHDT</td>
                            <td class="border border-slate-300"></td>
                        </tr>
                        <tr>
                            <td class="border border-slate-300 text-center font-bold" rowspan="2">3</td>
                            <td class="border border-slate-300 text-center">1÷5</td>
                            <td class="border border-slate-300 text-center font-bold text-white" style="background:#002060">VSV</td>
                            <td class="border border-slate-300"></td>
                            <td class="border border-slate-300 text-center font-bold" style="background:#D99694">TLHYĐ</td>
                        </tr>
                        <tr>
                            <td class="border border-slate-300 text-center">6÷9</td>
                            <td class="border border-slate-300"></td>
                            <td class="border border-slate-300 text-center font-bold text-white" style="background:#808080">XSTK</td>
                            <td class="border border-slate-300"></td>
                        </tr>
                    </tbody>
                </table>
                <div class="bg-slate-50 text-[9px] text-slate-500 px-2 py-1 italic">
                    … lưới tuần đầy đủ + tên viết tắt môn sẽ được điền theo lịch đã chọn khi xuất …
                </div>
            </div>

            <p data-preview="note" class="text-[10px] text-slate-600 mt-2 whitespace-pre-line"></p>
            <div data-preview="date_line" class="text-right text-[10px] mt-3 text-slate-600"></div>

            <div class="grid grid-cols-3 gap-2 mt-3" data-preview-signers>
                @foreach($signers as $s)
                    @php $key = $s['key'] ?? ('s'.$loop->index); @endphp
                    <div class="text-center text-[10px]" data-signer-preview="{{ $key }}">
                        <div data-signer-role1 class="font-bold"></div>
                        <div data-signer-role2 class="font-semibold text-slate-600"></div>
                        <div class="my-1 min-h-[2.5rem] flex items-center justify-center">
                            @if(!empty($s['image']))
                                <img src="{{ asset('images/signatures/lhl/'.basename($s['image'])) }}"
                                     alt="" class="max-h-10 object-contain opacity-90"
                                     onerror="this.style.display='none'">
                            @endif
                        </div>
                        <div data-signer-name class="font-bold text-teal-900"></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .lhl-diag-cell { height: 2.6rem; min-width: 3.2rem; }
    .lhl-diag-line {
        position: absolute; inset: 0;
        background: linear-gradient(to bottom right, transparent calc(50% - 0.7px), #334155 50%, transparent calc(50% + 0.7px));
        pointer-events: none;
    }
    .lhl-diag-mini { font-weight: 700; letter-spacing: 0.02em; }
    .lhl-diag-mini span { color: #64748b; margin: 0 1px; }

    /* Tick chữ ký số */
    .lhl-signer-card.is-on {
        border-color: rgba(13, 148, 136, 0.45);
        box-shadow: 0 0 0 1px rgba(13, 148, 136, 0.12), 0 8px 20px -14px rgba(13, 148, 136, 0.45);
    }
    .lhl-signer-card.is-on .lhl-tick-box {
        border-color: #0d9488;
        background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.18);
    }
    .lhl-signer-card.is-on .lhl-tick-box i {
        opacity: 1 !important;
        transform: scale(1) !important;
        color: #fff;
    }
    .lhl-signer-card.is-on [data-signer-chevron] {
        transform: rotate(180deg);
        color: #0d9488;
    }
    .lhl-signer-panel {
        animation: lhlPanelIn 0.2s ease;
    }
    @keyframes lhlPanelIn {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: none; }
    }
</style>
@endpush
@push('scripts')
<script>
(function () {
    function bindLhlExportEditor(root) {
        if (!root || root.dataset.bound === '1') return;
        root.dataset.bound = '1';

        const simpleFields = [
            'org_left', 'title', 'semester_line', 'respect_line',
            'unit_name', 'classroom', 'class_size', 'groups', 'class_leader',
            'note', 'date_line',
        ];

        function signerKeys() {
            return Array.from(root.querySelectorAll('[data-signer-block]')).map(function (el) {
                return el.getAttribute('data-signer-block');
            });
        }

        function syncSignerToggle(block) {
            if (!block) return;
            var cb = block.querySelector('[data-signer-enable]');
            var panel = block.querySelector('[data-signer-panel]');
            var on = !!(cb && cb.checked);
            block.classList.toggle('is-on', on);
            if (panel) {
                panel.classList.toggle('hidden', !on);
            }
            var sub = block.querySelector('[data-signer-tick-sub]');
            var nameEl = block.querySelector('[data-field$="_name"]');
            if (sub) {
                sub.textContent = on
                    ? ('Đang ký · ' + (nameEl && nameEl.value ? nameEl.value : 'chọn chữ ký bên dưới'))
                    : 'Tick để chọn chữ ký số';
            }
        }

        function sync() {
            simpleFields.forEach(function (f) {
                const input = root.querySelector('[data-field="' + f + '"]');
                const prev = root.querySelector('[data-preview="' + f + '"]');
                if (input && prev) prev.textContent = input.value || '';
            });
            signerKeys().forEach(function (key) {
                const card = root.querySelector('[data-signer-block="' + key + '"]');
                syncSignerToggle(card);
                const block = root.querySelector('[data-signer-preview="' + key + '"]');
                if (!block) return;
                const enabled = root.querySelector('[data-field="signer_' + key + '_enabled"]');
                const role1 = root.querySelector('[data-field="signer_' + key + '_role1"]');
                const role2 = root.querySelector('[data-field="signer_' + key + '_role2"]');
                const name = root.querySelector('[data-field="signer_' + key + '_name"]');
                const on = enabled && enabled.checked;
                block.style.display = on ? '' : 'none';
                const r1 = block.querySelector('[data-signer-role1]');
                const r2 = block.querySelector('[data-signer-role2]');
                const nm = block.querySelector('[data-signer-name]');
                if (r1) r1.textContent = role1 ? role1.value : '';
                if (r2) r2.textContent = role2 ? role2.value : '';
                if (nm) nm.textContent = name ? name.value : '';
            });
        }

        root.querySelectorAll('[data-field]').forEach(function (input) {
            input.addEventListener('input', sync);
            input.addEventListener('change', sync);
        });

        root.querySelectorAll('[data-signer-enable]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                syncSignerToggle(cb.closest('[data-signer-block]'));
                sync();
            });
            // mặc định chưa tick
            syncSignerToggle(cb.closest('[data-signer-block]'));
        });

        // Chọn chữ ký từ kho → điền name/role + preview ảnh
        root.querySelectorAll('[data-signer-select]').forEach(function (sel) {
            sel.addEventListener('change', function () {
                var opt = sel.options[sel.selectedIndex];
                if (!opt) return;
                var block = sel.closest('[data-signer-block]');
                if (!block) return;
                var name = opt.getAttribute('data-name') || '';
                var r1 = opt.getAttribute('data-role1') || '';
                var r2 = opt.getAttribute('data-role2') || '';
                var img = opt.getAttribute('data-img') || '';
                var key = block.getAttribute('data-signer-block');
                var nEl = root.querySelector('[data-field="signer_' + key + '_name"]');
                var r1El = root.querySelector('[data-field="signer_' + key + '_role1"]');
                var r2El = root.querySelector('[data-field="signer_' + key + '_role2"]');
                if (nEl && name) nEl.value = name;
                if (r1El && (r1 || opt.value)) r1El.value = r1;
                if (r2El) r2El.value = r2;
                var imgEl = block.querySelector('[data-signer-preview-img]');
                if (imgEl && img) {
                    imgEl.style.display = '';
                    imgEl.src = img;
                }
                var prevBlock = root.querySelector('[data-signer-preview="' + key + '"]');
                if (prevBlock) {
                    var pImg = prevBlock.querySelector('img');
                    if (pImg && img) pImg.src = img;
                }
                sync();
            });
        });

        sync();

        root.getMeta = function () {
            const meta = {};
            root.querySelectorAll('[data-field]').forEach(function (input) {
                const f = input.getAttribute('data-field');
                if (!f) return;
                if (input.type === 'checkbox') {
                    meta[f] = input.checked ? '1' : '0';
                } else {
                    // Chỉ lấy field trong panel chữ ký khi đã tick
                    var block = input.closest('[data-signer-block]');
                    if (block && f.indexOf('signer_') === 0 && f.indexOf('_enabled') === -1) {
                        var en = block.querySelector('[data-signer-enable]');
                        if (en && !en.checked) {
                            meta[f] = '';
                            return;
                        }
                    }
                    meta[f] = input.value || '';
                }
            });
            return meta;
        };
    }

    window.bindLhlExportEditor = bindLhlExportEditor;
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-lhl-export-editor]').forEach(bindLhlExportEditor);
    });
    document.addEventListener('turbo:load', function () {
        document.querySelectorAll('[data-lhl-export-editor]').forEach(function (el) {
            el.dataset.bound = '';
            bindLhlExportEditor(el);
        });
    });
})();
</script>
@endpush
@endonce
