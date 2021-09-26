<?php

namespace Girover\Tree\Tests\Traits;

use Girover\Tree\Models\Tree;
use Girover\Tree\Models\Node;

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
    public function createNode()
    {
        return Node::factory()->create();
    }
    public function makeNode()
    {
        return Node::factory()->make();
    }
}
