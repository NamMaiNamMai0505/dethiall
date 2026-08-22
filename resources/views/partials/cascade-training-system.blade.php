{{--
  Cascade Hệ đào tạo → Ngành đào tạo (Tom Select tương thích).
  Props:
    $systemSelectId   (default training_system_id)
    $specSelectId     (default specialization_id)
    $specializationsBySystem  array systemId => [specId => name]
    $initialSystemId
    $initialSpecId
--}}
@php
    $systemSelectId = $systemSelectId ?? 'training_system_id';
    $specSelectId = $specSelectId ?? 'specialization_id';
    $specializationsBySystem = $specializationsBySystem ?? [];
    $initialSystemId = $initialSystemId ?? null;
    $initialSpecId = $initialSpecId ?? null;
    $specPlaceholder = $specPlaceholder ?? 'Chọn ngành (sau khi chọn hệ)...';
@endphp
<script>
(function () {
    const specsBySystem = @json($specializationsBySystem);
    const systemSelId = @json($systemSelectId);
    const specSelId = @json($specSelectId);
    const placeholder = @json($specPlaceholder);
    const initialSystem = @json($initialSystemId ? (string) $initialSystemId : '');
    const initialSpec = @json($initialSpecId ? (string) $initialSpecId : '');

    function rebuildSpecOptions(systemId, keepId) {
        const specSel = document.getElementById(specSelId);
        if (!specSel) return;
        const map = systemId && specsBySystem[systemId] ? specsBySystem[systemId] : {};
        // Convert object map → entries (keys may be int)
        const entries = Object.keys(map || {}).map(function (k) { return [k, map[k]]; });

        if (specSel.tomselect) {
            const ts = specSel.tomselect;
            ts.clear(true);
            ts.clearOptions();
            ts.addOption({ value: '', text: placeholder });
            entries.forEach(function (pair) {
                ts.addOption({ value: String(pair[0]), text: pair[1] });
            });
            ts.refreshOptions(false);
            if (keepId && map[keepId]) {
                ts.setValue(String(keepId), true);
            } else if (keepId && map[String(keepId)]) {
                ts.setValue(String(keepId), true);
            } else {
                ts.setValue('', true);
            }
        } else {
            specSel.innerHTML = '';
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = placeholder;
            specSel.appendChild(empty);
            entries.forEach(function (pair) {
                const opt = document.createElement('option');
                opt.value = pair[0];
                opt.textContent = pair[1];
                if (keepId && String(keepId) === String(pair[0])) opt.selected = true;
                specSel.appendChild(opt);
            });
        }
    }

    function onSystemChange(val) {
        rebuildSpecOptions(val, null);
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === systemSelId) {
            onSystemChange(e.target.value);
        }
    });

    function boot() {
        const systemSel = document.getElementById(systemSelId);
        if (!systemSel) return;
        const sys = systemSel.value || initialSystem;
        if (sys) {
            rebuildSpecOptions(sys, initialSpec || null);
        } else if (initialSpec) {
            // Tìm hệ từ ngành đã chọn
            let found = null;
            Object.keys(specsBySystem).forEach(function (sid) {
                if (specsBySystem[sid][initialSpec] || specsBySystem[sid][String(initialSpec)]) {
                    found = sid;
                }
            });
            if (found) {
                if (systemSel.tomselect) systemSel.tomselect.setValue(String(found), true);
                else systemSel.value = found;
                rebuildSpecOptions(found, initialSpec);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', boot);
    document.addEventListener('turbo:load', boot);
    if (document.readyState !== 'loading') setTimeout(boot, 50);
})();
</script>
