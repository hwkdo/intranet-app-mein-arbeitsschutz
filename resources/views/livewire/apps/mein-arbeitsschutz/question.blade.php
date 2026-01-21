<?php

use Flux\Flux;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\IntranetAppMeinArbeitsschutzSettings;
use Hwkdo\OpenwebuiApiLaravel\Services\OpenWebUiRagService;
use Illuminate\Support\Facades\Log;

use function Livewire\Volt\{state, title};

title('MeinArbeitsschutz - Frage');

state([
    'question' => '',
    'answer' => '',
    'isLoading' => false,
]);

$askQuestion = function (OpenWebUiRagService $ragService) {
    if (empty($this->question)) {
        Flux::toast(text: 'Bitte geben Sie eine Frage ein.', variant: 'error');

        return;
    }

    $this->isLoading = true;
    $this->answer = '';

    try {
        $settings = IntranetAppMeinArbeitsschutzSettings::current();

        if (! $settings) {
            Flux::toast(text: 'Einstellungen nicht gefunden.', variant: 'error');
            $this->isLoading = false;

            return;
        }

        $collectionId = $settings->settings->openWebUiCollectionId;

        if (empty($collectionId)) {
            Flux::toast(text: 'Keine Collection-ID konfiguriert.', variant: 'error');
            $this->isLoading = false;

            return;
        }

        $model = config('openwebui-api-laravel.default_model');
        $messages = [
            ['role' => 'user', 'content' => $this->question],
        ];

        $result = $ragService->chatWithCollection($model, $messages, $collectionId);

        // Extrahiere die Antwort aus dem Result
        $this->answer = $result['choices'][0]['message']['content'] ?? 'Keine Antwort erhalten.';

        Log::info('OpenWebUI Question answered', [
            'question' => $this->question,
            'collection_id' => $collectionId,
        ]);
    } catch (\Exception $e) {
        Log::error('OpenWebUI Question failed', [
            'question' => $this->question,
            'error' => $e->getMessage(),
        ]);

        Flux::toast(text: 'Fehler beim Verarbeiten der Frage: '.$e->getMessage(), variant: 'error');
        $this->answer = 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.';
    } finally {
        $this->isLoading = false;
    }
};

?>
<div>
<x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout heading="Frage stellen" subheading="Stellen Sie eine Frage zu den Arbeitsschutz-Dokumenten">
    <flux:card>
        <flux:heading size="lg" class="mb-4">Frage an die KI</flux:heading>
        <flux:text class="mb-6">
            Stellen Sie eine Frage zu den Arbeitsschutz-Dokumenten. Die KI durchsucht die hochgeladenen Dokumente und gibt Ihnen eine Antwort.
        </flux:text>

        <div class="space-y-6">
            <flux:field>
                <flux:label>Ihre Frage</flux:label>
                <flux:textarea
                    wire:model="question"
                    placeholder="z.B. Was sind die wichtigsten Sicherheitsregeln?"
                    rows="4"
                    wire:keydown.enter.prevent="askQuestion"
                />
                <flux:description>Drücken Sie Enter, um die Frage zu stellen</flux:description>
            </flux:field>

            <flux:button
                wire:click="askQuestion"
                wire:loading.attr="disabled"
                variant="primary"
                class="w-full sm:w-auto"
            >
                <span wire:loading.remove>Frage stellen</span>
                <span wire:loading>Sende Frage...</span>
            </flux:button>

            @if ($answer)
                <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="md" class="mb-3">Antwort</flux:heading>
                    <div class="prose prose-sm max-w-none dark:prose-invert">
                        <flux:text class="whitespace-pre-wrap">{{ $answer }}</flux:text>
                    </div>
                </div>
            @endif

            @if ($isLoading)
                <div class="mt-6 flex items-center justify-center">
                    <flux:icon icon="arrow-path" class="h-6 w-6 animate-spin text-zinc-500" />
                    <flux:text class="ml-2">Verarbeite Frage...</flux:text>
                </div>
            @endif
        </div>
    </flux:card>
</x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout>
</div>