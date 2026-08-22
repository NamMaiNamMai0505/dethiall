@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3>Quản lý Mẫu Xuất</h3>
            <a href="{{ route('template-management.create') }}" class="btn btn-primary">
                <i class="fas fa-upload"></i> Tải Template Mới
            </a>
            <a href="{{ route('template-management.index') }}" class="btn btn-secondary" onclick="window.location.href = '{{ route('template-management.index') }}'">Danh sách</a>
        </div>
        <div class="card-body">
            <table class="table table-striped" id="templatesTable">
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Scope</th>
                        <th>Loại</th>
                        <th>Active</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($templates as $template)
                    <tr>
                        <td>{{ $template->name }}</td>
                        <td>{{ $template->scope }}</td>
                        <td>{{ $template->mime }}</td>
                        <td>{{ $template->is_active ? 'Có' : 'Không' }}</td>
                        <td>
                            <a href="{{ route('template-management.show', $template->id) }}" class="btn btn-sm btn-info">Xem</a>
                            <form method="POST" action="{{ route('template-management.destroy', $template->id) }}" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection