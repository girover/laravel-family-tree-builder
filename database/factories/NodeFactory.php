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
            'name' => $this->faker->name(),
            'location' => Location::firstPossibleSegment(),
            'tree_id' => 1,
        ];
    }
}
