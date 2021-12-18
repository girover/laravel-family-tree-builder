<?php

namespace Girover\Tree\Tests\Traits;

use Girover\Tree\Models\Node;
use Girover\Tree\Models\Tree;

/**
 *
 */
trait Factoryable
{
    public function createTree()
    {
        return Tree::factory()->create();
    }

    public function makeTree()
    {
        return Tree::factory()->make();
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

    public function makeFemaleNode()
    {
        return Node::factory()->make(['gender' => 'f']);
    }
}
