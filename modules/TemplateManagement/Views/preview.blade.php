@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h3>Preview Template - {{ $template->name }}</h3>
            <div>
                <button onclick="preview()" class="btn btn-success">Preview Realtime</button>
                <a href="{{ route('template-management.index') }}" class="btn btn-secondary">Danh sách</a>
            </div>
        </div>
        <div class="card-body">
            <div id="previewContainer" class="border p-3 bg-light" style="min-height: 400px; position: relative;">
                <!-- Preview will render here with mock data -->
            </div>
            <button id="previewBtn" onclick="generatePreview()" class="btn btn-success mt-3">Generate Preview</button>
            <div id="sidebar" class="sidebar" style="position: fixed; right: 20px; top: 100px; width: 300px; background: white; border: 1px solid #ddd; padding: 15px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                <h5>Placeholder Editor</h5>
                <select id="placeholderSelect" onchange="applyStyle(this.value)">
                    <option value="">Chọn Placeholder</option>
                    <option value="class_name">{{class_name}}</option>
                    <option value="teacher">Giảng viên</option>
                    <option value="subject">Môn học</option>
                </select>
                <button onclick="applyStyle()" class="btn btn-sm btn-primary mt-2">Áp dụng Style</button>
                <div id="styleControls">
                    <input type="color" id="color" value="#000" onchange="applyStyle()" style="width: 100%;">
                    <input type="number" id="size" value="12" onchange="applyStyle()" style="width: 100%;">px
                    <select id="bold" onchange="applyStyle()">
                        <option value="">Bold</option>
                        <option value="bold">Bold</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function generatePreview() {
    const container = document.getElementById('previewContainer');
    container.innerHTML = `
        <h4>{{ $template->name }} Preview</h4>
        <p><strong>Mock Data:</strong></p>
        <ul>
            <li>Lớp: Y54</li>
            <li>Đơn vị: Đại đội 4 / Tiếu đoàn 2</li>
            <li>Sĩ số: 60</li>
            <li>Phòng học: 101/H3</li>
            <li>Giảng viên: Nguyễn Văn A</li>
            <li>Môn học: TTT, GPSL, SKSS, NPL</li>
            <li>Tiết: 1-3 TTT, 4-5 GPSL, 6-9 TTT</li>
        </ul>
        <p><strong>Header:</strong> Tuần - Ngày (dựa trên PDF LHL)</p>
        <p><strong>Table:</strong> Các môn học, giảng viên, nội dung...</p>
        <button onclick="this.parentElement.innerHTML = 'Preview updated realtime!'" class="btn btn-sm btn-primary">Update Preview</button>
    `;
}

document.getElementById('preview').addEventListener('click', generatePreview);
</script>
@endsection