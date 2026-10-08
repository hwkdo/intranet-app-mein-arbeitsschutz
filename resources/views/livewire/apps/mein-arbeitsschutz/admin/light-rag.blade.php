<div @class(['space-y-4']) @if($this->hasOpenWork) wire:poll.15s @endif>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="lg">LightRAG</flux:heading>
            <flux:text class="mt-1">Neue Dokumente gehen von allein in die Instanz Mein Arbeitsschutz. Titel und Zuordnung ändern die Datei nicht. Fehler bleiben hier stehen.</flux:text>
        </div>
        <flux:button variant="primary" icon="arrow-up-tray" wire:click="enqueueAll" wire:loading.attr="disabled">
            Alle indexieren
        </flux:button>
    </div>

    <div class="flex flex-wrap items-center gap-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Suche…" icon="magnifying-glass" class="max-w-sm" />
        <flux:select wire:model.live="filter" variant="listbox" class="max-w-xs">
            <flux:select.option value="all">Alle</flux:select.option>
            <flux:select.option value="working">In Arbeit</flux:select.option>
            <flux:select.option value="indexed">In LightRAG</flux:select.option>
            <flux:select.option value="failed">Fehlgeschlagen</flux:select.option>
            <flux:select.option value="missing">Nicht indexiert</flux:select.option>
        </flux:select>
    </div>

    <flux:table :paginate="$this->documents">
        <flux:table.columns>
            <flux:table.column>Dokument</flux:table.column>
            <flux:table.column>LightRAG</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($this->documents as $document)
                <flux:table.row wire:key="arbeitsschutz-lightrag-{{ $document->id }}">
                    <flux:table.cell>{{ $document->title }}</flux:table.cell>
                    <flux:table.cell>
                        @if($document->lightRagState === null)
                            <flux:badge>nicht indexiert</flux:badge>
                        @elseif($document->lightRagIsCurrent() && $document->lightRagState->status === \Hwkdo\IntranetAppMeinArbeitsschutz\Enums\ArbeitsschutzLightRagStatus::Processed)
                            <flux:badge color="green">{{ $document->lightRagState->status->label() }}</flux:badge>
                        @elseif($document->lightRagState->status === \Hwkdo\IntranetAppMeinArbeitsschutz\Enums\ArbeitsschutzLightRagStatus::Failed)
                            <flux:badge color="red">{{ $document->lightRagState->status->label() }}</flux:badge>
                        @elseif($document->lightRagIsCurrent())
                            <flux:badge color="amber">{{ $document->lightRagState->status->label() }}</flux:badge>
                        @else
                            <flux:badge>nicht indexiert</flux:badge>
                        @endif
                        @if($document->lightRagState?->status === \Hwkdo\IntranetAppMeinArbeitsschutz\Enums\ArbeitsschutzLightRagStatus::Failed && $document->lightRagState->error_message)
                            <flux:text class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $document->lightRagState->error_message }}</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if(! $document->lightRagIsCurrent())
                            <flux:button size="sm" wire:click="retry({{ $document->id }})" wire:loading.attr="disabled">
                                Erneut versuchen
                            </flux:button>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">Keine Dokumente.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
