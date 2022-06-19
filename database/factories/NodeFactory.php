<?php

namespace Girover\Tree\Database\Factories;

use Girover\Tree\Location;
use Girover\Tree\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;


class NodeFactory extends Factory
{
    protected $model = Node::class;

    
    public function definition()
    {
        // $this->model =  ModelService::nodeModel();
        return [
            'name'        => $this->faker->name(),
            'nodeable_id' => 1,
            'treeable_id' => 1,
            'location'    => Location::firstPossibleSegment(),
            'gender'      => $this->faker->randomElement(['m','f']),
        ];
    }
}
