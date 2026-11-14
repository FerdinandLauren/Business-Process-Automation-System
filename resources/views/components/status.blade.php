@props(['label', 'tone' => 'neutral'])

@php
    $tones = [
        'neutral' => 'text-ink-soft',
        'accent' => 'text-accent',
        'positive' => 'text-positive',
        'caution' => 'text-caution',
        'danger' => 'text-danger',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'status-pill '.($tones[$tone] ?? $tones['neutral'])]) }}>{{ $label }}</span>
