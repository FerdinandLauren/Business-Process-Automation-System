@props(['paginator'])

@if($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3 border-t border-line px-4 py-3" aria-label="Pagination">
        <p class="mono text-[11px] text-ink-faint">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </p>
        <div class="flex items-center gap-1">
            @if($paginator->onFirstPage())
                <span class="btn btn-sm btn-quiet opacity-40">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-sm btn-quiet" rel="prev">Previous</a>
            @endif

            @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 1), min($paginator->lastPage(), $paginator->currentPage() + 1)) as $page => $url)
                @if($page == $paginator->currentPage())
                    <span class="mono grid h-7 min-w-7 place-items-center rounded-[2px] bg-ink px-1.5 text-[11px] text-paper">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="mono grid h-7 min-w-7 place-items-center rounded-[2px] px-1.5 text-[11px] text-ink-soft hover:bg-paper-deep">{{ $page }}</a>
                @endif
            @endforeach

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-sm btn-quiet" rel="next">Next</a>
            @else
                <span class="btn btn-sm btn-quiet opacity-40">Next</span>
            @endif
        </div>
    </nav>
@endif
