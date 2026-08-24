<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Search;

use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Document;
use Illuminate\Support\Collection;

class DocumentSearch
{
    /**
     * @return Collection<int, Document>
     */
    public static function query(string $query, int $limit): Collection
    {
        return Document::search($query)
            ->query(fn ($builder) => $builder->with('media'))
            ->take($limit)
            ->get();
    }
}
