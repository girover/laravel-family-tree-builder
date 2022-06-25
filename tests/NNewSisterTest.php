<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NNewSisterTest extends TestCase
{
    use DatabaseTransactions, Factoryable;


    /**
     * -------------------------------------------
     * testing newSister method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_create_new_sister_for_node()
    {
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());
        
        $node  = $root->newSon($this->makeNodeable()->toArray());
        
        $sister = $node->newSister($this->makeNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$sister->name]);        
        $this->assertTrue($sister->isFemale());
        $this->assertTrue($root->treeable_id === $sister->treeable_id);
        $this->assertTrue(Location::areSiblings($node->location, $sister->location));
    }

    /** @test */
    public function it_can_not_create_new_sister_for_root_node()
    {
        $this->expectException(TreeException::class);
        
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());

        $root->newSister($this->makeNode()->toArray());
    }

    /** @test */
    public function it_can_not_create_new_sister_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());

        $son = $root->newSon($this->makeNodeable()->toArray());

        $son->newSister([]);
    }
}