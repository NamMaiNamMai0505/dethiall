@props([
    'item' => null,
    'routes' => [],
    'showActions' => ['view', 'edit', 'toggle', 'delete'],
    'deleteConfirmMessage' => null,
])

@php
    $modelName = strtolower(class_basename($item));

    $moduleMapping = [
        'trainingschedule' => 'training-schedules',
        'teachingassignment' => 'teaching-assignments',
        'scheduledetail' => 'schedule-details',
        'user' => 'users',
        'classmodel' => 'classes',
        'class' => 'classes',
        'instructor' => 'instructors',
        'subject' => 'subjects',
        'unit' => 'units',
        'classroom' => 'classrooms',
        'building' => 'buildings',
        'specialization' => 'specializations',
        'role' => 'roles',
        'permission' => 'permissions',
        'home' => 'home',
        'dashboard' => 'dashboard',
    ];

    $moduleName = $moduleMapping[$modelName] ?? $modelName;

    $permissions = [
        'view' => "$moduleName.show",
        'edit' => "$moduleName.edit",
        'delete' => "$moduleName.delete",
    ];
    $permissions['toggle'] = $permissions['edit'];
@endphp

@if($item)
    {{-- class khớp admin-theme: khung icon + glow hover --}}
    <div class="flex space-x-2 items-center action-icons">
        @if(in_array('view', $showActions) && isset($routes['show']))
            @canPermission($permissions['view'])
                <a href="{{ route($routes['show'], $item) }}"
                   class="action-icon text-blue-600"
                   title="Xem chi tiết">
                    <i class="bi bi-eye"></i>
                </a>
            @endcanPermission
        @endif

        @if(in_array('edit', $showActions) && isset($routes['edit']))
            @canPermission($permissions['edit'])
                <a href="{{ route($routes['edit'], $item) }}"
                   class="action-icon text-green-600"
                   title="Chỉnh sửa">
                    <i class="bi bi-pencil"></i>
                </a>
            @endcanPermission
        @endif

        @if(in_array('toggle', $showActions) && isset($routes['toggle']))
            @canPermission($permissions['toggle'])
                @php $isActive = (bool) ($item->is_active ?? $item->status ?? false); @endphp
                <form method="POST"
                      action="{{ route($routes['toggle'], $item) }}"
                      class="inline"
                      data-confirm="Bạn có chắc chắn muốn {{ $isActive ? 'tạm dừng' : 'kích hoạt' }} mục này?">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="action-icon {{ $isActive ? 'text-orange-600' : 'text-green-600' }}"
                            title="{{ $isActive ? 'Tạm dừng' : 'Kích hoạt' }}">
                        <i class="bi bi-{{ $isActive ? 'pause' : 'play' }}"></i>
                    </button>
                </form>
            @endcanPermission
        @endif

        @if(in_array('delete', $showActions) && isset($routes['destroy']))
            @canPermission($permissions['delete'])
                <form method="POST"
                      action="{{ route($routes['destroy'], $item) }}"
                      class="inline"
                      data-confirm="{{ $deleteConfirmMessage ?? 'Bạn có chắc chắn muốn xóa mục này?' }}"
                      data-confirm-danger="1"
                      data-confirm-ok="Xóa">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="action-icon text-red-600"
                            title="Xóa">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            @endcanPermission
        @endif

        {{ $slot }}
    </div>
@endif
