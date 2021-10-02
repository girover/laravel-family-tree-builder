<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NIsRootTest extends TestCase
{
    use DatabaseTransactions, Factoryable;


    /**
     * -------------------------------------------
     * testing isRoot method
     * --------------------------------------------
     */
    /** @test */
    public function it_should_returns_true_when_applying_on_root_node()
    {
        // Create new Tree in database.
        $tree = $this->createTree();
        // Create Root node for the created tree.
        $root = $tree->createRoot(['name'=>'majed']); 

        $this->assertTrue($root->isRoot());
    }

    /** @test */
    public function it_should_returns_false_when_applying_on_none_root_node()
    {
        // Create new Tree in database.
        $tree = $this->createTree();
        // Create Root node for the created tree.
        $root = $tree->createRoot(['name'=>'root']); 
        $son = $root->newSon(['name'=>'son name']);
        $this->assertFalse($son->isRoot());
    }
}