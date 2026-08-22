<div
    data-lesson-import-feedback
    class="hidden rounded-xl border border-blue-200 bg-blue-50/80 p-3.5"
    role="status"
    aria-live="polite"
>
    <div class="flex items-start gap-3">
        <span
            data-import-status-icon
            class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700"
            aria-hidden="true"
        >
            <i class="bi bi-cloud-arrow-up"></i>
        </span>
        <div class="min-w-0 flex-1">
            <p data-import-status-title class="text-sm font-semibold text-blue-900">Đang chuẩn bị import</p>
            <p data-import-status-message class="mt-0.5 break-words text-xs leading-5 text-blue-700">
                Hệ thống đang kiểm tra file Excel.
            </p>
        </div>
        <span data-import-status-percent class="text-xs font-bold tabular-nums text-blue-700">0%</span>
    </div>
    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-blue-100">
        <div
            data-import-progress
            class="h-full rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 transition-[width] duration-300 ease-out"
            style="width: 0%"
        ></div>
    </div>
</div>
