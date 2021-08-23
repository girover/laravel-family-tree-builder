<?php

namespace Girover\Tree\Database\Factories;

use Girover\Tree\Models\Tree;
use Illuminate\Database\Eloquent\Factories\Factory;


class TreeFactory extends Factory
{
    protected $model = Tree::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
