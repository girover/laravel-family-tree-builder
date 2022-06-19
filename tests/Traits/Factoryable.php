<?php

namespace Girover\Tree\Tests\Traits;

use Girover\Tree\Tests\Models\TreeableModel;
use Girover\Tree\Tests\Models\NodeableModel;
use Girover\Tree\Models\Node;

/**
 * 
 */
trait Factoryable
{
    public function createTreeable()
    { 
        return TreeableModel::factory()->create();
    }
    public function makeTreeable()
    {
        return TreeableModel::factory()->make();
    }
    public function createNodeable()
    { 
        return NodeableModel::factory()->create();
    }
    public function makeNodeable()
    {
        return NodeableModel::factory()->make();
    }
    public function createNode($data = [])
    {
        return Node::factory()->create($data);
    }
    public function makeNode($data = [])
    {
        return Node::factory()->make($data);
    }
    public function createMaleNode($data = [])
    {
        $data['gender'] = 'm';
        return Node::factory()->create($data);
    }
    public function createFemaleNode($data = [])
    {
        $data['gender'] = 'f';
        return Node::factory()->create($data);
    }
    public function makeMaleNode()
    {
        return Node::factory()->make(['gender'=>'m']);
    }
    public function makeFemaleNode()
    {
        return Node::factory()->make(['gender'=>'f']);
    }
}
