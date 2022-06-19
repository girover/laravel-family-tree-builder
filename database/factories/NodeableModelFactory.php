<?php

namespace Girover\Tree\Database\Factories;

use Girover\Tree\Tests\Models\NodeableModel;
use Illuminate\Database\Eloquent\Factories\Factory;


class NodeableModelFactory extends Factory
{
    protected $model = NodeableModel::class;

    
    public function definition()
    {
        // $this->model =  ModelService::nodeModel();
        return [
            'name'        => $this->faker->name(),
            'father_name' => $this->faker->name(),
            'birth_date'  => $this->faker->date(),
        ];
    }
}
