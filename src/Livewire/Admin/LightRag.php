<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Livewire\Admin;

use Flux\Flux;
use Hwkdo\IntranetAppMeinArbeitsschutz\Enums\ArbeitsschutzLightRagStatus;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Document;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\DocumentLightRagState;
use Hwkdo\IntranetAppMeinArbeitsschutz\Services\ArbeitsschutzLightRagQueue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LightRag extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filter = 'all';

    public function mount(): void
    {
        $this->authorize('manage-app-mein-arbeitsschutz');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function enqueueAll(ArbeitsschutzLightRagQueue $queue): void
    {
        $this->authorize('manage-app-mein-arbeitsschutz');

        $queued = $queue->enqueueMissing();

        Flux::toast(
            heading: 'LightRAG',
            text: $queued === 0
                ? 'Alle Dokumente sind schon indexiert oder in Arbeit.'
                : $queued.' Dokumente wurden eingereiht.',
            variant: 'success',
        );
    }

    public function retry(int $documentId, ArbeitsschutzLightRagQueue $queue): void
    {
        $this->authorize('manage-app-mein-arbeitsschutz');

        $document = Document::query()->find($documentId);
        if ($document === null) {
            return;
        }

        $queue->enqueue($document);
    }

    /**
     * @return LengthAwarePaginator<int, Document>
     */
    #[Computed]
    public function documents(): LengthAwarePaginator
    {
        return Document::query()
            ->with(['lightRagState', 'media'])
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->filter === 'failed', fn ($query) => $query->whereHas(
                'lightRagState',
                fn ($state) => $state->where('status', ArbeitsschutzLightRagStatus::Failed->value),
            ))
            ->when($this->filter === 'working', fn ($query) => $query->whereHas(
                'lightRagState',
                fn ($state) => $state->whereIn('status', [
                    ArbeitsschutzLightRagStatus::Pending->value,
                    ArbeitsschutzLightRagStatus::Processing->value,
                ]),
            ))
            ->when($this->filter === 'indexed', fn ($query) => $query->whereHas(
                'lightRagState',
                fn ($state) => $state->where('status', ArbeitsschutzLightRagStatus::Processed->value),
            ))
            ->when($this->filter === 'missing', fn ($query) => $query->where(function ($query): void {
                $query->whereDoesntHave('lightRagState')
                    ->orWhereHas('lightRagState', fn ($state) => $state->where('status', ArbeitsschutzLightRagStatus::Failed->value));
            }))
            ->orderBy('title')
            ->paginate(25);
    }

    #[Computed]
    public function hasOpenWork(): bool
    {
        return DocumentLightRagState::query()
            ->whereIn('status', [
                ArbeitsschutzLightRagStatus::Pending->value,
                ArbeitsschutzLightRagStatus::Processing->value,
            ])
            ->exists();
    }

    public function render(): View
    {
        return view('intranet-app-mein-arbeitsschutz::livewire.apps.mein-arbeitsschutz.admin.light-rag');
    }
}
