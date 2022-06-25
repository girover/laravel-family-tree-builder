<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NNewSonTest extends TestCase
{
    use DatabaseTransactions, Factoryable;

    /**
     * -------------------------------------------
     * testing newSon method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_create_new_son_for_node()
    {
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());
        
        $son  = $root->newSon($this->makeNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$son->name]);        
        $this->assertTrue(Location::areFatherAndChild($root->location, $son->location));
        $this->assertTrue($root->treeable_id === $son->treeable_id);
    }

    /** @test */
    public function it_can_not_create_new_son_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());

        $root->newSon([]);
    }

    /** @test */
    public function it_should_not_create_new_son_for_a_female_node()
    {
        $this->expectException(TreeException::class);
        
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());

        $female = $root->newDaughter($this->makeNodeable()->toArray());

        $this->assertTrue($female->isFemale());

        $female->newSon($this->makeNodeable()->toArray());
    }
}