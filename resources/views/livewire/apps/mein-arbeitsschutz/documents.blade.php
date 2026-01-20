<?php

use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Category;
use function Livewire\Volt\{computed, title};

title('MeinArbeitsschutz - Dokumente');

$categories = computed(function () {
    return Category::query()
        ->orderBy('sort_order')
        ->get();
});

?>
<div>
<x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout heading="MeinArbeitsschutz App" subheading="Dokumente">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach($this->categories as $category)
            <a
                href="{{ route('apps.mein-arbeitsschutz.documents.show', $category->key) }}"
                wire:navigate
                class="flex items-center gap-6 rounded-lg border border-zinc-200 bg-white p-6 text-left transition-all hover:border-zinc-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600"
            >
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900">
                    <flux:icon icon="{{ $category->icon }}" class="h-7 w-7 text-blue-600 dark:text-blue-300" />
                </div>
                <div class="text-left">
                    <flux:heading size="lg">{{ $category->label }}</flux:heading>
                    <flux:text class="text-gray-600 dark:text-gray-400">
                        Dokumente anzeigen
                    </flux:text>
                </div>
            </a>
        @endforeach
    </div>
</x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout>
</div>