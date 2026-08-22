{{-- Load Chart.js only on pages that need charts (not globally in layout) --}}
@once
    @push('scripts')
        {{-- No defer: page scripts in the same stack use Chart immediately after this tag --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @endpush
@endonce
