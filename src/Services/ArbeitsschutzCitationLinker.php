<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Services;

use Hwkdo\IntranetAppBase\Contracts\LightRagCitationLinker;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Document;
use Illuminate\Support\Facades\Route;

class ArbeitsschutzCitationLinker implements LightRagCitationLinker
{
    public function instance(): string
    {
        return 'arbeitsschutz';
    }

    public function pattern(): string
    {
        return '/(?<![\p{L}\p{N}_-])arbeitsschutz-\d+\.[A-Za-z0-9]{1,8}(?![\p{L}\p{N}_-])/iu';
    }

    public function resolveMany(array $fileNames): array
    {
        if (! Route::has('apps.mein-arbeitsschutz.documents.open')) {
            return [];
        }

        $idsByName = [];
        foreach ($fileNames as $fileName) {
            $key = $this->key($fileName);
            if (preg_match('/^arbeitsschutz-(\d+)\.[a-z0-9]{1,8}$/', $key, $match) !== 1) {
                continue;
            }

            $idsByName[$key] = (int) $match[1];
        }

        if ($idsByName === []) {
            return [];
        }

        $documents = Document::query()
            ->whereIn('id', array_values(array_unique($idsByName)))
            ->get(['id', 'title'])
            ->keyBy('id');

        $resolved = [];
        foreach ($idsByName as $key => $id) {
            $document = $documents->get($id);
            if ($document === null) {
                continue;
            }

            $title = trim((string) $document->title);
            $resolved[$key] = [
                'url' => route('apps.mein-arbeitsschutz.documents.open', $document),
                'title' => $title !== '' ? $title : $key,
            ];
        }

        return $resolved;
    }

    private function key(string $fileName): string
    {
        return strtolower(basename(str_replace('\\', '/', trim($fileName))));
    }
}
