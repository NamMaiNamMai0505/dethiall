@props(['autoHide' => true, 'hideDelay' => 5000])

{{-- Success Messages --}}
@if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded flash-message">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm">{{ session('success') }}</p>
            </div>
        </div>
    </div>
@endif

{{-- Error Messages --}}
@if(session('error'))
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded flash-message">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm">{{ session('error') }}</p>
            </div>
        </div>
    </div>
@endif

{{-- Warning Messages --}}
@if(session('warning'))
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 rounded flash-message">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm">{{ session('warning') }}</p>
            </div>
        </div>
    </div>
@endif

{{-- Info Messages --}}
@if(session('info'))
    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4 rounded flash-message">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="bi bi-info-circle-fill"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm">{{ session('info') }}</p>
            </div>
        </div>
    </div>
@endif

@if($autoHide)
@push('scripts')
<script>
// Auto hide flash messages after {{ $hideDelay }}ms
setTimeout(() => {
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(message => {
        message.style.transition = 'opacity 0.5s';
        message.style.opacity = '0';
        setTimeout(() => message.remove(), 500);
    });
}, {{ $hideDelay }});
</script>
@endpush
@endif
