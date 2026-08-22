{{-- Admin: session flash → popup toast (Notify), không banner xanh/đỏ inline --}}
@if(session('success') || session('error') || session('warning') || session('info') || (isset($errors) && $errors->any()))
    <script type="application/json" id="session-flash-payload">
        @php
            $flashPayload = [
                'success' => session('success'),
                'error' => session('error'),
                'warning' => session('warning'),
                'info' => session('info'),
                'errors' => (isset($errors) && $errors->any()) ? $errors->all() : [],
            ];
        @endphp
        {!! json_encode($flashPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
@endif
