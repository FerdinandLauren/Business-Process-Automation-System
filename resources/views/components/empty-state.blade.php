@props(['title' => 'Nothing here yet', 'description' => null])

<div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <span class="grid h-9 w-9 place-items-center rounded-full border border-line">
        <svg class="h-4 w-4 text-ink-faint" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.3">
            <path d="M4 5h12M4 10h12M4 15h7"/>
        </svg>
    </span>
    <p class="mt-3 text-[13.5px] font-medium">{{ $title }}</p>
    @if($description)
        <p class="mt-1 max-w-[380px] text-[12.5px] leading-relaxed text-ink-faint">{{ $description }}</p>
    @endif
    @if(! $slot->isEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
