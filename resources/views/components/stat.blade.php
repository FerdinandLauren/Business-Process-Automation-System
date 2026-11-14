@props(['label', 'value', 'meta' => null, 'tone' => 'default'])

@php
    $valueTone = match ($tone) {
        'positive' => 'text-positive',
        'caution' => 'text-caution',
        'danger' => 'text-danger',
        default => 'text-ink',
    };
@endphp

<div class="panel flex min-w-0 flex-col justify-between p-4">
    <div class="flex items-start justify-between gap-3">
        <p class="text-[11px] font-medium uppercase tracking-[0.08em] text-ink-faint">{{ $label }}</p>
        <span class="mt-[3px] h-1.5 w-1.5 flex-none rounded-full {{ $tone === 'default' ? 'bg-line-strong' : ($tone === 'positive' ? 'bg-positive' : ($tone === 'caution' ? 'bg-caution' : ($tone === 'danger' ? 'bg-danger' : 'bg-accent'))) }}"></span>
    </div>
    <p class="mono mt-3 text-[28px] font-medium leading-none tracking-[-0.03em] {{ $valueTone }}">{{ $value }}</p>
    @if($meta)
        <p class="mt-2 truncate text-[11.5px] text-ink-faint">{{ $meta }}</p>
    @endif
</div>
