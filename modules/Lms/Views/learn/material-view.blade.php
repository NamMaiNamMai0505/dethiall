@extends('layouts.lms-learner')

@section('title', $material->title)

@section('content')
@php
    $kind = $material->kind;
    $mime = (string) $material->mime;
    $ext = strtolower(pathinfo($material->original_name ?? $material->path ?? '', PATHINFO_EXTENSION));
    $officeExts = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'];
    $isOffice = in_array($ext, $officeExts, true)
        || str_contains($mime, 'officedocument')
        || str_contains($mime, 'msword')
        || str_contains($mime, 'ms-excel')
        || str_contains($mime, 'ms-powerpoint');
    $isPpt = in_array($ext, ['ppt', 'pptx', 'odp'], true) || $kind === 'slide';
    $isText = in_array($ext, ['txt', 'md', 'csv', 'log', 'json', 'xml', 'html', 'htm', 'css', 'js'], true)
        || str_starts_with($mime, 'text/')
        || $mime === 'application/json';
    $isAudio = str_starts_with($mime, 'audio/') || in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'aac'], true);
    $isPdf = $kind === 'pdf' || $ext === 'pdf' || str_contains($mime, 'pdf');
    $isVideo = $kind === 'video' || str_starts_with($mime, 'video/');
    $isImage = $kind === 'image' || str_starts_with($mime, 'image/');
    $publicUrl = $url;
    // Office Online cần absolute public URL
    $absoluteUrl = str_starts_with((string) $url, 'http') ? $url : url($url);
@endphp

<div class="mb-4 flex flex-wrap justify-between gap-2 items-center">
    <a href="{{ route('lms.learn.courses.show', $course) }}?tab=materials" class="text-sm text-teal-700 hover:underline">← {{ $course->title }}</a>
    <a href="{{ $url }}" target="_blank" download class="lms-btn lms-btn-ghost">
        <i class="bi bi-download"></i> Tải xuống
    </a>
</div>

<div class="lms-card overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-100 flex flex-wrap justify-between gap-2">
        <div>
            <h1 class="font-bold text-slate-900">{{ $material->title }}</h1>
            <p class="text-xs text-slate-500">{{ $material->kindLabel() }} · {{ $material->humanSize() }} · {{ $material->original_name }}</p>
        </div>
    </div>

    @if($isPdf)
        <iframe src="{{ $url }}#toolbar=1&navpanes=0"
                class="w-full border-0 bg-slate-100"
                style="min-height: 78vh; height: 80vh;"
                title="{{ $material->title }}"></iframe>

    @elseif($isVideo)
        <div class="bg-black flex justify-center p-2">
            <video src="{{ $url }}" controls playsinline class="w-full" style="max-height:80vh">
                Trình duyệt không hỗ trợ video.
            </video>
        </div>

    @elseif($isAudio)
        <div class="p-8 flex flex-col items-center gap-4 bg-slate-50">
            <i class="bi bi-music-note-beamed text-4xl text-teal-600"></i>
            <audio src="{{ $url }}" controls class="w-full max-w-xl">Trình duyệt không hỗ trợ audio.</audio>
        </div>

    @elseif($isImage)
        <div class="p-4 flex justify-center bg-slate-50">
            <img src="{{ $url }}" alt="{{ $material->title }}" class="max-w-full max-h-[80vh] object-contain rounded-lg shadow">
        </div>

    @elseif($isPpt)
        {{-- PPTX: ưu tiên SCORM; Office Viewer chỉ là thử; không coi là xem native --}}
        <div class="p-6 text-center space-y-4">
            <p class="text-sm text-slate-600 max-w-xl mx-auto">
                File slide (<strong>{{ $material->original_name }}</strong>) nên đóng gói <strong>SCORM</strong> để học trên LMS.
                Có thể thử Microsoft Office Viewer (cần URL public) hoặc tải về.
            </p>
            <div class="flex flex-wrap justify-center gap-2">
                <a href="{{ $url }}" download class="lms-btn lms-btn-primary">Tải slide</a>
                <a href="https://view.officeapps.live.com/op/embed.aspx?src={{ urlencode($absoluteUrl) }}"
                   target="_blank" class="lms-btn lms-btn-ghost">Thử Office Viewer</a>
            </div>
            @php
                $scorm = \Modules\Lms\Models\LmsScormPackage::query()
                    ->where('lms_material_id', $material->id)
                    ->first();
            @endphp
            @if($scorm)
                <a href="{{ route('lms.learn.scorm.play', [$course, $scorm]) }}" class="lms-btn lms-btn-primary">
                    Mở SCORM player
                </a>
            @endif
            <iframe src="https://view.officeapps.live.com/op/embed.aspx?src={{ urlencode($absoluteUrl) }}"
                    class="w-full border border-slate-200 rounded-lg mt-2 bg-white"
                    style="min-height: 65vh; height: 70vh;"
                    title="Office viewer"></iframe>
        </div>

    @elseif($isOffice)
        {{-- DOC/XLS và Office khác: xem qua Office Online Viewer --}}
        <div class="p-4 border-b border-slate-100 text-sm text-slate-600 flex flex-wrap gap-2 justify-between items-center">
            <span>Xem trên trình duyệt qua Microsoft Office Viewer (cần file public).</span>
            <a href="{{ $url }}" download class="lms-btn lms-btn-ghost" style="padding:0.35rem 0.75rem">Tải file</a>
        </div>
        <iframe src="https://view.officeapps.live.com/op/embed.aspx?src={{ urlencode($absoluteUrl) }}"
                class="w-full border-0 bg-white"
                style="min-height: 78vh; height: 80vh;"
                title="{{ $material->title }}"></iframe>

    @elseif($isText)
        @php
            $body = '';
            try {
                $body = \Illuminate\Support\Facades\Storage::disk($material->disk ?: 'public')->get($material->path);
            } catch (\Throwable $e) {
                $body = '';
            }
            if (strlen($body) > 500000) {
                $body = substr($body, 0, 500000)."\n\n… (đã cắt bớt file lớn)";
            }
        @endphp
        @if(in_array($ext, ['html', 'htm'], true))
            <iframe srcdoc="{{ e($body) }}" class="w-full border-0 bg-white" style="min-height:70vh"></iframe>
        @else
            <pre class="p-4 text-sm overflow-auto bg-slate-50 text-slate-800 whitespace-pre-wrap" style="max-height:78vh">{{ $body }}</pre>
        @endif

    @elseif($kind === 'scorm')
        <div class="p-8 text-center text-sm space-y-3">
            <p>Đây là gói SCORM. Mở trình chiếu trong player:</p>
            @php
                $scorm = \Modules\Lms\Models\LmsScormPackage::query()
                    ->where('lms_material_id', $material->id)
                    ->first();
            @endphp
            @if($scorm)
                <a href="{{ route('lms.learn.scorm.play', [$course, $scorm]) }}" class="lms-btn lms-btn-primary">Mở SCORM player</a>
            @else
                <a href="{{ $url }}" class="lms-btn lms-btn-ghost">Tải file ZIP</a>
            @endif
        </div>

    @else
        <div class="p-8 text-center text-sm text-slate-600 space-y-3">
            <p>Định dạng này chưa có viewer riêng. Thử mở file hoặc tải về.</p>
            <div class="flex flex-wrap justify-center gap-2">
                <a href="{{ $url }}" target="_blank" class="lms-btn lms-btn-primary">Mở tab mới</a>
                <a href="{{ $url }}" download class="lms-btn lms-btn-ghost">Tải xuống</a>
            </div>
        </div>
    @endif
</div>
@endsection
