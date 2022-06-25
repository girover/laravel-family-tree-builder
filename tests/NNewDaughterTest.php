<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NNewDaughterTest extends TestCase
{
    use DatabaseTransactions, Factoryable;


    /**
     * -------------------------------------------
     * testing newBrother method
     * --------------------------------------------
     */

    /** @test */
    public function it_can_create_new_daughter_for_node()
    {
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());
        
        $node  = $root->newSon($this->makeNodeable()->toArray());
        
        $daughter = $root->newDaughter($this->makeNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$daughter->name]);        
        $this->assertTrue($root->treeable_id === $daughter->treeable_id);
        $this->assertTrue(Location::areSiblings($node->location, $daughter->location));
    }

    /** @test */
    public function it_should_not_create_new_daughter_for_a_female_node()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());

        $female = $root->newDaughter($this->makeNodeable()->toArray());

        $female->newDaughter($this->makeNodeable()->toArray());
    }

    /** @test */
    public function it_can_not_create_new_daughter_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());

        $root->newDaughter([]);
    }  
}