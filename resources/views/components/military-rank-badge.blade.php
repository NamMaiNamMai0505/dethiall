@props(['rank', 'compact' => false])

@if($rank)
    @php
        $tone = match($rank->group_key) {
            'officer_general' => 'bg-rose-50 text-rose-800 border-rose-200',
            'officer_field' => 'bg-amber-50 text-amber-800 border-amber-200',
            'officer_company' => 'bg-blue-50 text-blue-800 border-blue-200',
            'professional' => 'bg-fuchsia-50 text-fuchsia-800 border-fuchsia-200',
            default => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        };
        $stars = $rank->stars ? str_repeat('★', min((int) $rank->stars, 4)) : '◆';
        $barCount = min((int) ($rank->bars ?? 0), 2);
        $bars = $barCount > 0 ? str_repeat('|', $barCount) : '';
        $rankMark = $stars.($bars !== '' ? ' '.$bars : '');
        $isProfessional = $rank->group_key === 'professional';
    @endphp
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-semibold '.$tone]) }}
          title="{{ $rank->group_name }}{{ $rank->navy_equivalent ? ' · '.$rank->navy_equivalent : '' }}">
        <span aria-hidden="true"
              data-rank-bars="{{ $barCount }}"
              style="display:inline-flex;align-items:center;gap:.16rem;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:.015em;white-space:pre">
            <span>{{ $rankMark }}</span>
            @if($isProfessional)
                <span title="Vạch QNCN" style="color:#db2777;font-weight:900">|</span>
            @endif
        </span>
        <span>{{ $compact ? ($rank->abbreviation ?: $rank->name) : $rank->name }}</span>
    </span>
@endif
