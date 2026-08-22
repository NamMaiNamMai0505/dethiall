@extends('layouts.admin')

@section('title', 'Phân công giảng dạy')
@section('page-title', 'Phân công giảng dạy')

@section('content')
    <x-breadcrumb :items="[
            ['title' => 'Trang chủ'],
            ['title' => 'Phân công giảng viên', 'url' => route('teaching-assignments.index')],
            ['title' => 'Tạo phân công']
        ]" />

    <x-page-header title="PHÂN CÔNG GIẢNG VIÊN"
        :actions="[
            [
                'url' => route('teaching-assignments.index'),
                'label' => 'Quay lại',
                'icon' => 'arrow-left',
                'color' => 'gray'
            ]
        ]" />

    <div class="bg-white shadow rounded-lg p-6">
        <form action="{{ route('teaching-assignments.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Chọn giảng viên --}}
            <div>
                <label for="instructor_id" class="block text-sm font-medium text-gray-700 mb-2">Giảng viên *</label>
                <select id="instructor_id" name="instructor_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">-- Chọn giảng viên --</option>
                    @foreach(\Modules\Instructor\Models\Instructor::active()->get() as $ins)
                        <option value="{{ $ins->id }}" {{ old('instructor_id') == $ins->id ? 'selected' : '' }}>
                            {{ $ins->name }}
                        </option>
                    @endforeach
                </select>
                @error('instructor_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tab ngành đào tạo và môn học --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-4">Chọn môn học theo ngành đào tạo *</label>
                
                {{-- Tabs Navigation --}}
                <div class="border-b border-gray-200 mb-4">
                    <nav class="-mb-px flex space-x-8">
                        @foreach($specializations as $index => $specialization)
                            <button type="button" 
                                class="tab-btn py-2 px-1 border-b-2 font-medium text-sm {{ $index === 0 ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                data-tab="spec-{{ $specialization->id }}">
                                {{ $specialization->selection_label }}
                                <span class="ml-1 bg-gray-200 text-gray-600 py-0.5 px-2 rounded-full text-xs tab-count" data-spec="{{ $specialization->id }}">0</span>
                            </button>
                        @endforeach
                    </nav>
                </div>

                {{-- Tab Content --}}
                @foreach($specializations as $index => $specialization)
                    <div class="tab-content {{ $index === 0 ? '' : 'hidden' }}" id="spec-{{ $specialization->id }}">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 p-4 bg-gray-50 rounded-lg max-h-64 overflow-y-auto">
                            @forelse($specialization->subjects as $subject)
                                <label class="flex items-center space-x-2 p-2 hover:bg-white rounded cursor-pointer">
                                    <input type="checkbox" 
                                        name="subject_ids[]" 
                                        value="{{ $subject->id }}"
                                        data-subject-name="{{ $subject->name }}"
                                        data-spec-id="{{ $specialization->id }}"
                                        class="subject-checkbox form-checkbox text-blue-600 rounded focus:ring-blue-500"
                                        {{ in_array($subject->id, old('subject_ids', [])) ? 'checked' : '' }}>
                                    <span class="text-sm">{{ $subject->name }}</span>
                                </label>
                            @empty
                                <p class="col-span-full text-gray-500 text-center py-4">Chưa có môn học nào trong ngành đào tạo này</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach

                @error('subject_ids')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Danh sách môn đã chọn --}}
            <div class="border-t pt-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-medium text-gray-700">
                        Môn đã chọn (<span id="total-selected">0</span>)
                    </h3>
                    <button type="button" id="clear-all" class="text-sm text-red-600 hover:text-red-800 hover:underline">
                        Xóa tất cả
                    </button>
                </div>
                <div id="selected-subjects" class="flex flex-wrap gap-2 min-h-[40px] p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-500 text-sm" id="no-selection">Chưa có môn học nào được chọn</span>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="pt-4 border-t text-right space-x-3">
                <button type="button" onclick="window.history.back()" 
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                    Hủy
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-save mr-2"></i>{{ $instructor ? 'Cập nhật phân công' : 'Lưu phân công' }}
                </button>
            </div>
        </form>
    </div>

    <style>
        .subject-checkbox:checked + span {
            font-weight: 600;
            color: #1e40af;
        }
        
        .tab-btn.active {
            border-color: #3b82f6;
            color: #1e40af;
        }
        
        .selected-pill {
            background: linear-gradient(135deg, #3b82f6, #1e40af);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetTab = this.dataset.tab;
                    
                    // Update tab buttons
                    tabBtns.forEach(b => b.classList.remove('active', 'border-blue-500', 'text-blue-600'));
                    tabBtns.forEach(b => b.classList.add('border-transparent', 'text-gray-500'));
                    this.classList.add('active', 'border-blue-500', 'text-blue-600');
                    this.classList.remove('border-transparent', 'text-gray-500');
                    
                    // Update tab contents
                    tabContents.forEach(content => content.classList.add('hidden'));
                    document.getElementById(targetTab).classList.remove('hidden');
                });
            });

            // Subject selection handling
            const checkboxes = document.querySelectorAll('.subject-checkbox');
            const selectedContainer = document.getElementById('selected-subjects');
            const totalSelected = document.getElementById('total-selected');
            const noSelectionMsg = document.getElementById('no-selection');
            const clearAllBtn = document.getElementById('clear-all');

            function updateSelectedSubjects() {
                const selected = Array.from(checkboxes).filter(cb => cb.checked);
                
                // Update total count
                totalSelected.textContent = selected.length;
                
                // Update tab counts
                const specCounts = {};
                selected.forEach(cb => {
                    const specId = cb.dataset.specId;
                    specCounts[specId] = (specCounts[specId] || 0) + 1;
                });
                
                document.querySelectorAll('.tab-count').forEach(counter => {
                    const specId = counter.dataset.spec;
                    counter.textContent = specCounts[specId] || 0;
                });

                // Update selected pills
                selectedContainer.innerHTML = '';
                
                if (selected.length === 0) {
                    selectedContainer.appendChild(noSelectionMsg);
                } else {
                    selected.forEach(checkbox => {
                        const pill = document.createElement('span');
                        pill.className = 'selected-pill inline-flex items-center px-3 py-1 text-white rounded-full text-sm font-medium shadow-sm';
                        pill.innerHTML = `
                            ${checkbox.dataset.subjectName}
                            <button type="button" class="ml-2 text-white/80 hover:text-white remove-pill" data-subject-id="${checkbox.value}">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                        selectedContainer.appendChild(pill);
                    });
                }
            }

            // Checkbox change handler
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedSubjects);
            });

            // Remove pill handler
            selectedContainer.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-pill') || e.target.parentElement.classList.contains('remove-pill')) {
                    const button = e.target.classList.contains('remove-pill') ? e.target : e.target.parentElement;
                    const subjectId = button.dataset.subjectId;
                    const checkbox = document.querySelector(`input[value="${subjectId}"]`);
                    if (checkbox) {
                        checkbox.checked = false;
                        updateSelectedSubjects();
                    }
                }
            });

            // Clear all handler
            clearAllBtn.addEventListener('click', function() {
                uiConfirm('Bạn có chắc chắn muốn xóa tất cả môn học đã chọn?', {
                    danger: true,
                    confirmText: 'Xóa hết',
                    title: 'Xóa lựa chọn',
                }).then((ok) => {
                    if (!ok) return;
                    checkboxes.forEach(cb => cb.checked = false);
                    updateSelectedSubjects();
                });
            });

            // Initialize
            updateSelectedSubjects();
        });
    </script>
@endsection
