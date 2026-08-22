@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3>Tải Template Mới</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('template-management.store') }}" enctype="multipart/form-data">
                @csrf
                @method('POST')
                <div class="mb-3">
                    <label for="name" class="form-label">Tên Template</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="scope" class="form-label">Scope</label>
                    <select class="form-select" id="scope" name="scope" required>
                        <option value="dashboard">Dashboard</option>
                        <option value="lms">LMS</option>
                        <option value="grades">Quản lý điểm</option>
                        <option value="shared">Dùng chung</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="file" class="form-label">File Template (Word/Excel)</label>
                    <input type="file" class="form-control" id="file" name="file" accept=".docx,.doc,.xlsx,.xls,.pdf" required>
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Ghi chú</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Tải lên và Quét Template</button>
            </form>
        </div>
    </div>
</div>
@endsection