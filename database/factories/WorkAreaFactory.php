<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Database\Factories;

use Hwkdo\IntranetAppMeinArbeitsschutz\Models\WorkArea;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkAreaFactory extends Factory
{
    protected $model = WorkArea::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
