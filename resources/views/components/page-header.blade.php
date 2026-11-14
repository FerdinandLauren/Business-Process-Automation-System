@props(['eyebrow' => null, 'title', 'description' => null])

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        @if($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-1.5 text-[24px] font-semibold leading-tight tracking-[-0.02em]">{{ $title }}</h1>
        @if($description)
            <p class="mt-1.5 max-w-[640px] text-[13px] leading-relaxed text-ink-soft">{{ $description }}</p>
        @endif
    </div>
    @if(! $slot->isEmpty())
        <div class="flex flex-none flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
