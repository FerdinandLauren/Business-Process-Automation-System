@if(count($payload['rows']) === 0)
    <x-empty-state :title="$payload['empty']['title']" :description="$payload['empty']['description']" />
@else
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    @foreach($payload['columns'] as $column)
                        <th @class(['text-right' => ($column['align'] ?? 'left') === 'right'])>{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($payload['rows'] as $row)
                    <tr>
                        @foreach($row as $index => $cell)
                            <td @class([
                                'text-right' => ($payload['columns'][$index]['align'] ?? 'left') === 'right',
                                'max-w-[320px]' => $index === 0,
                            ])>
                                @if(is_array($cell))
                                    @if(array_key_exists('bar', $cell))
                                        <div class="flex items-center justify-end gap-2.5">
                                            <span class="h-1.5 w-20 flex-none overflow-hidden rounded-[1px] bg-paper-deep sm:w-28">
                                                <span class="block h-full bg-accent" style="width: {{ $cell['bar'] > 0 ? max(2, round($cell['bar'] * 100)) : 0 }}%"></span>
                                            </span>
                                            <span class="mono w-16 text-[10.5px] text-ink-faint">{{ $cell['value'] ?? '' }}</span>
                                        </div>
                                    @elseif(isset($cell['label'], $cell['tone']))
                                        <x-status :label="$cell['label']" :tone="$cell['tone']" />
                                    @elseif(isset($cell['primary']))
                                        @if(! empty($cell['href']))
                                            <a href="{{ $cell['href'] }}" class="block truncate font-medium text-ink underline-offset-2 hover:text-accent hover:underline">{{ $cell['primary'] }}</a>
                                        @else
                                            <p class="truncate font-medium text-ink">{{ $cell['primary'] }}</p>
                                        @endif
                                        @if(! empty($cell['secondary']))
                                            <p class="mono mt-0.5 text-[10.5px] text-ink-faint">{{ $cell['secondary'] }}</p>
                                        @endif
                                    @else
                                        <span @class([
                                            'mono text-[11px]',
                                            'text-danger' => ($cell['tone'] ?? null) === 'danger',
                                            'text-positive' => ($cell['tone'] ?? null) === 'positive',
                                            'text-caution' => ($cell['tone'] ?? null) === 'caution',
                                            'text-ink-soft' => ! isset($cell['tone']),
                                        ])>{{ $cell['value'] ?? '—' }}</span>
                                    @endif
                                @else
                                    <span class="text-[12px] text-ink-soft">{{ $cell }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($payload['paginator'])
        <x-pagination :paginator="$payload['paginator']" />
    @endif
@endif
