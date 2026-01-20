<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Database\Factories;

use Hwkdo\IntranetAppMeinArbeitsschutz\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(),
            'label' => $this->faker->words(2, true),
            'icon' => 'document-text',
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
