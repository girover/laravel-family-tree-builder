<?php

namespace Girover\Tree\Tests\Traits;

use Girover\Tree\Models\Treeable;
use Girover\Tree\Models\Nodeable;
use Girover\Tree\Models\Node;

/**
 * 
 */
trait Factoryable
{
    public function createTreeable()
    { 
        return Treeable::factory()->create();
    }
    public function makeTreeable()
    {
        return Treeable::factory()->make();
    }
    public function createNodeable()
    { 
        return Nodeable::factory()->create();
    }
    public function makeNodeable()
    {
        return Nodeable::factory()->make();
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
