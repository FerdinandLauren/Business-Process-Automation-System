<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="eyebrow">Process catalogue · {{ $typeCode }}</p>
            <h1 class="mt-1 text-[22px] font-semibold tracking-[-0.01em]">
                @if($locked)
                    Form version {{ $version }}
                @elseif($templateId)
                    Editing form draft v{{ $version }}
                @else
                    Draft form version {{ $version }}
                @endif
            </h1>
            <p class="mt-1 max-w-[720px] text-[12.5px] leading-relaxed text-ink-soft">
                Fields appear in this order on the request form. Published versions are frozen so submitted requests keep the questions they were answered against.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.request-types.index') }}" class="btn btn-sm">Catalogue</a>
            <a href="{{ route('admin.request-types.edit', $typeCode) }}" class="btn btn-sm">Type settings</a>
        </div>
    </div>

    @if($locked)
        <div class="border border-line bg-paper-deep/50 px-4 py-3">
            <p class="text-[12.5px] font-medium">This version is published and read-only.</p>
            <p class="mt-1 text-[11.5px] leading-relaxed text-ink-soft">
                Requests were validated against these fields. Start a new draft to change the form without touching requests already in flight.
            </p>
            <a href="{{ route('admin.request-types.forms.create', $typeCode) }}" class="btn btn-sm btn-primary mt-3">Draft a new version</a>
        </div>
    @endif

    @error('form')
        <div class="border border-[#c0392b]/40 bg-[#c0392b]/5 px-4 py-3 text-[12px] text-[#8e2a1f]">{{ $message }}</div>
    @enderror

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-4">
            <section class="panel">
                <div class="grid gap-4 p-4 sm:grid-cols-[minmax(0,1fr)_160px]">
                    <div>
                        <label class="label" for="form-name">Form name</label>
                        <input id="form-name" type="text" wire:model="name" class="input" @disabled($locked)>
                        @error('name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <span class="label">Version</span>
                        <p class="mono flex h-[34px] items-center border border-line bg-paper-deep/40 px-2.5 text-[12px]">v{{ $version }}</p>
                    </div>
                </div>
            </section>

            @error('fields') <p class="error">{{ $message }}</p> @enderror

            @foreach($fields as $index => $field)
                <section class="panel" wire:key="field-{{ $index }}">
                    <div class="panel-head flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <span class="mono grid h-6 w-6 place-items-center rounded-full border border-line-strong bg-surface text-[10px] text-ink-soft">{{ $index + 1 }}</span>
                            <h2 class="text-[13.5px] font-semibold">{{ $field['label'] !== '' ? $field['label'] : 'Untitled field' }}</h2>
                            <span class="mono rounded-[2px] border border-line bg-paper px-1.5 py-0.5 text-[10px] uppercase tracking-[0.06em] text-ink-faint">{{ $fieldTypes[$field['type']] ?? $field['type'] }}</span>
                        </div>
                        @unless($locked)
                            <div class="flex items-center gap-1">
                                <button type="button" wire:click="moveField({{ $index }}, -1)" class="btn btn-sm btn-quiet" @disabled($index === 0) aria-label="Move field up">↑</button>
                                <button type="button" wire:click="moveField({{ $index }}, 1)" class="btn btn-sm btn-quiet" @disabled($index === count($fields) - 1) aria-label="Move field down">↓</button>
                                <button type="button" wire:click="removeField({{ $index }})" class="btn btn-sm btn-quiet text-[#8e2a1f]" wire:confirm="Remove this field from the draft?">Remove</button>
                            </div>
                        @endunless
                    </div>

                    <div class="grid gap-4 p-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="label-{{ $index }}">Label</label>
                            <input id="label-{{ $index }}" type="text" wire:model="fields.{{ $index }}.label" class="input" placeholder="What the requester sees" @disabled($locked)>
                            @error("fields.{$index}.label") <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="key-{{ $index }}">Key</label>
                            <input id="key-{{ $index }}" type="text" wire:model="fields.{{ $index }}.key" class="input mono" placeholder="e.g. cost_centre" @disabled($locked)>
                            <p class="hint">Stored with the answer. Leave blank to derive it from the label.</p>
                            @error("fields.{$index}.key") <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="type-{{ $index }}">Type</label>
                            <select id="type-{{ $index }}" wire:model.live="fields.{{ $index }}.type" class="select" @disabled($locked)>
                                @foreach($fieldTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error("fields.{$index}.type") <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-end">
                            <label class="flex cursor-pointer items-center gap-2.5 pb-1.5 text-[12px]">
                                <input type="checkbox" wire:model="fields.{{ $index }}.required" class="h-3.5 w-3.5 accent-[#2f45c5]" @disabled($locked)>
                                Required before submission
                            </label>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label" for="help-{{ $index }}">Helper text</label>
                            <input id="help-{{ $index }}" type="text" wire:model="fields.{{ $index }}.help" class="input" placeholder="Optional guidance shown under the field" @disabled($locked)>
                            @error("fields.{$index}.help") <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label" for="placeholder-{{ $index }}">Placeholder</label>
                            <input id="placeholder-{{ $index }}" type="text" wire:model="fields.{{ $index }}.placeholder" class="input" placeholder="Optional example value" @disabled($locked)>
                            @error("fields.{$index}.placeholder") <p class="error">{{ $message }}</p> @enderror
                        </div>

                        @if(in_array($field['type'], $choiceTypes, true))
                            <div class="sm:col-span-2">
                                <label class="label" for="options-{{ $index }}">Options</label>
                                <textarea id="options-{{ $index }}" rows="4" wire:model="fields.{{ $index }}.options" class="textarea mono" placeholder="One option per line" @disabled($locked)></textarea>
                                <p class="hint">One option per line. Order is preserved in the form.</p>
                                @error("fields.{$index}.options") <p class="error">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                </section>
            @endforeach

            @unless($locked)
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="addField('text')" class="btn btn-sm">Add text field</button>
                    <button type="button" wire:click="addField('textarea')" class="btn btn-sm">Add long text</button>
                    <button type="button" wire:click="addField('dropdown')" class="btn btn-sm">Add dropdown</button>
                    <button type="button" wire:click="addField('file')" class="btn btn-sm">Add file upload</button>
                </div>
            @endunless
        </div>

        <aside class="space-y-4">
            @unless($locked)
                <section class="panel p-4">
                    <p class="eyebrow">Save</p>
                    <p class="mt-2 text-[11.5px] leading-relaxed text-ink-soft">
                        Drafts are private to administrators. Publishing makes this version the one employees answer and freezes it.
                    </p>
                    <div class="mt-4 flex flex-col gap-2">
                        <button type="button" wire:click="saveDraft" class="btn w-full" wire:loading.attr="disabled" wire:target="saveDraft">Save draft</button>
                        <button type="button" wire:click="publish" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="publish" wire:confirm="Publish this form version? It becomes read-only and is used for all new requests.">Publish version</button>
                    </div>
                </section>
            @endunless

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">Preview</p>
                        <h2 class="mt-0.5 text-[13.5px] font-semibold">How the requester sees it</h2>
                    </div>
                </div>
                <div class="space-y-4 p-4">
                    @forelse($fields as $field)
                        @php($key = $field['key'] !== '' ? $field['key'] : 'field-'.$loop->index)
                        <div>
                            <span class="label">{{ $field['label'] !== '' ? $field['label'] : 'Untitled field' }}@if($field['required']) <span class="text-[#8e2a1f]">*</span>@endif</span>
                            @switch($field['type'])
                                @case('textarea')
                                    <div class="min-h-[64px] border border-line bg-paper px-2.5 py-2 text-[11.5px] text-ink-faint">{{ $field['placeholder'] ?: 'Long answer' }}</div>
                                    @break
                                @case('dropdown')
                                    <div class="flex h-[34px] items-center justify-between border border-line bg-paper px-2.5 text-[11.5px] text-ink-faint">
                                        <span>{{ $field['placeholder'] ?: 'Choose an option' }}</span>
                                        <span aria-hidden="true">▾</span>
                                    </div>
                                    @break
                                @case('radio')
                                @case('checkbox')
                                    <ul class="space-y-1.5 border border-line bg-paper px-2.5 py-2">
                                        @forelse(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $field['options']))) as $option)
                                            <li class="flex items-center gap-2 text-[11.5px] text-ink-soft">
                                                <span class="h-2.5 w-2.5 flex-none border border-line-strong"></span>{{ $option }}
                                            </li>
                                        @empty
                                            <li class="text-[11.5px] text-ink-faint">No options yet</li>
                                        @endforelse
                                    </ul>
                                    @break
                                @case('file')
                                    <div class="flex h-[34px] items-center border border-dashed border-line-strong bg-paper px-2.5 text-[11.5px] text-ink-faint">Choose a file</div>
                                    @break
                                @default
                                    <div class="flex h-[34px] items-center border border-line bg-paper px-2.5 text-[11.5px] text-ink-faint">{{ $field['placeholder'] ?: ucfirst(str_replace('_', ' ', $key)) }}</div>
                            @endswitch
                            @if(filled($field['help']))
                                <p class="hint">{{ $field['help'] }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-[12px] text-ink-faint">Add a field to see the preview.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>
