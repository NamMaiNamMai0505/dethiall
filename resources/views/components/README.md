# Blade Components Documentation

## Tổng quan

Thư mục này chứa các Blade component có thể tái sử dụng trong toàn bộ ứng dụng Laravel. Các component này được thiết kế để giảm thiểu code trùng lặp và đảm bảo tính nhất quán trong UI.

## Cấu trúc

```
resources/views/components/
├── breadcrumb.blade.php              # Breadcrumb navigation
├── page-header.blade.php             # Page header với title và actions
├── sidebar.blade.php                 # Sidebar navigation
├── top-header.blade.php              # Top header với user menu
├── flash-messages.blade.php          # Flash messages component
├── status-badge.blade.php            # Badge hiển thị trạng thái
├── level-badge.blade.php             # Badge hiển thị cấp độ
├── certification-badge.blade.php     # Badge hiển thị loại chứng chỉ
├── custom-badge.blade.php            # Badge tùy chỉnh cho các trạng thái
├── filter-form.blade.php             # Form filter với các field động
├── data-table.blade.php              # Data table component
├── pagination.blade.php              # Pagination component
├── help-sidebar.blade.php            # Sidebar hướng dẫn
├── metadata-card.blade.php           # Card hiển thị metadata
├── prerequisites-manager.blade.php   # Component quản lý prerequisites
├── level-guide.blade.php            # Hướng dẫn cấp độ
├── form/
│   ├── input.blade.php              # Input field component
│   ├── select.blade.php             # Select field component
│   ├── textarea.blade.php           # Textarea component
│   └── checkbox.blade.php           # Checkbox component
└── table/
    └── action-buttons.blade.php      # Action buttons cho table rows
```

## Cách sử dụng

### 1. Breadcrumb Component

```blade
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Chuyên môn hóa', 'url' => route('specializations.index')],
    ['title' => 'Chi tiết']
]" />
```

### 2. Page Header Component

```blade
<x-page-header 
    title="Tiêu đề trang"
    subtitle="Mô tả ngắn"
    :actions="[
        [
            'url' => route('items.create'),
            'label' => 'Tạo mới',
            'icon' => 'plus',
            'color' => 'blue'
        ]
    ]" />
```

### 3. Sidebar Component

```blade
<x-sidebar :menu-items="[
    [
        'label' => 'Dashboard',
        'icon' => 'speedometer2',
        'url' => route('dashboard'),
        'active' => request()->routeIs('dashboard')
    ],
    [
        'label' => 'Menu cha',
        'icon' => 'folder',
        'submenu' => [
            ['label' => 'Submenu 1', 'url' => '#'],
            ['label' => 'Submenu 2', 'url' => '#', 'active' => true]
        ]
    ]
]" />
```

### 4. Top Header Component

```blade
<x-top-header 
    title="Tiêu đề ứng dụng"
    :user="Auth::user()"
    :notifications="5" />
```

### 5. Flash Messages Component

```blade
<x-flash-messages />
<!-- Hoặc tùy chỉnh -->
<x-flash-messages :auto-hide="false" />
<x-flash-messages :hide-delay="3000" />
```

### 6. Badge Components

```blade
<!-- Status Badge -->
<x-status-badge :is-active="true" size="lg" />

<!-- Level Badge -->
<x-level-badge level="advanced" text="Nâng cao" />

<!-- Certification Badge -->
<x-certification-badge type="degree" text="Học vị" />

<!-- Custom Badge -->
<x-custom-badge type="success" text="Thành công" />
<x-custom-badge type="warning" text="Cảnh báo" />
<x-custom-badge type="danger" text="Lỗi" />
```

### 7. Form Components

```blade
<!-- Input -->
<x-form.input 
    name="email"
    label="Email"
    type="email"
    placeholder="Nhập email..."
    required />

<!-- Select -->
<x-form.select 
    name="status"
    label="Trạng thái"
    :options="['active' => 'Hoạt động', 'inactive' => 'Tạm dừng']"
    required />

<!-- Textarea -->
<x-form.textarea 
    name="description"
    label="Mô tả"
    rows="5" />

<!-- Checkbox -->
<x-form.checkbox 
    name="is_active"
    label="Kích hoạt"
    :checked="true" />
```

### 8. Data Table Component

```blade
<x-data-table 
    :headers="[
        ['label' => 'ID', 'key' => 'id'],
        ['label' => 'Tên', 'key' => 'name'],
        ['label' => 'Trạng thái', 'key' => 'status', 'type' => 'badge', 'text_key' => 'status_text'],
        ['label' => 'Mã', 'key' => 'code', 'type' => 'code']
    ]"
    :data="$items"
    :actions="[
        [
            'type' => 'link',
            'icon' => 'eye',
            'color' => 'blue',
            'title' => 'Xem',
            'url' => '/items/{id}'
        ]
    ]" />
```

### 9. Filter Form Component

```blade
<x-filter-form 
    :action="route('items.index')"
    :filters="[
        ['type' => 'search', 'name' => 'search', 'placeholder' => 'Tìm kiếm...'],
        ['type' => 'select', 'name' => 'status', 'options' => [...]]
    ]" />
```

### 10. Pagination Component

```blade
<x-pagination 
    :current-page="1"
    :total-pages="10"
    base-url="/items"
    :query-params="['search' => 'keyword']" />
```

### 11. Help Sidebar

```blade
<x-help-sidebar 
    :tips="['Mẹo 1', 'Mẹo 2']"
    :warnings="['Lưu ý 1', 'Lưu ý 2']" />
```

### 12. Prerequisites Manager

```blade
<x-prerequisites-manager 
    :values="['Yêu cầu 1', 'Yêu cầu 2']" />
```

## Layout Integration

### Simple Partial Approach

Ngoài components phức tạp, chúng ta cũng sử dụng **Blade partials** đơn giản cho các phần cố định:

```
resources/views/partials/
├── sidebar.blade.php          # Sidebar navigation cố định
├── top-header.blade.php       # Top header với user menu  
└── flash-messages.blade.php   # Flash messages
```

**Sử dụng partials:**
```blade
<!-- Thay vì component phức tạp -->
<x-sidebar :menu-items="[...]" />

<!-- Dùng partial đơn giản -->
@include('partials.sidebar')
```

**Lợi ích partials:**
- ✅ **Đơn giản**: Không cần props phức tạp
- ✅ **Dễ maintenance**: Sửa một file, áp dụng toàn bộ
- ✅ **Performance**: Nhanh hơn components động
- ✅ **Consistency**: Menu giống nhau 100%

## Ví dụ thực tế

### 1. Module Specialization (Refactored)
- `modules/Specialization/Views/index.blade.php`
- `modules/Specialization/Views/show.blade.php`
- `modules/Specialization/Views/create.blade.php`
- `modules/Specialization/Views/edit.blade.php`

### 2. Module Dashboard (Refactored)
- `modules/Dashboard/Views/index.blade.php`

### 3. Admin Layout (Refactored)
- `resources/views/layouts/admin.blade.php`

## Migration Guide

### From Manual HTML to Components

**Before:**
```blade
<div class="bg-white rounded-lg border p-4">
    <form method="GET">
        <div class="grid grid-cols-4 gap-4">
            <input type="text" name="search" placeholder="Search...">
            <select name="status">...</select>
        </div>
    </form>
</div>
```

**After:**
```blade
<x-filter-form 
    :filters="[
        ['type' => 'search', 'name' => 'search'],
        ['type' => 'select', 'name' => 'status', 'options' => [...]]
    ]" />
```

## Performance Benefits

- ⚡ **Giảm 70-80% code trùng lặp**
- 🔧 **Easier maintenance** 
- 🎨 **Consistent UI/UX**
- 📱 **Responsive design built-in**
- 🐛 **Fewer bugs through standardization**
- 🚀 **Faster development**

## Best Practices

1. **Consistency**: Sử dụng components thống nhất
2. **Reusability**: Tạo component mới khi có pattern lặp ≥3 lần
3. **Props Validation**: Luôn kiểm tra props
4. **Documentation**: Cập nhật docs khi thêm component mới
5. **Testing**: Test components trong các context khác nhau 
