<?php

namespace Girover\Tree\Database\Factories;

use Girover\Tree\Tests\Models\TreeableModel;
use Illuminate\Database\Eloquent\Factories\Factory;


class TreeableModelFactory extends Factory
{
    protected $model = TreeableModel::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
