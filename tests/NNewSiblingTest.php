<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NNewSiblingTest extends TestCase
{
    use DatabaseTransactions, Factoryable;


    /**
     * -------------------------------------------
     * testing newSibling method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_create_new_sibling_for_node()
    {
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());
        
        $node  = $root->newSon($this->makeNodeable()->toArray());
        
        $sibling = $node->newSibling($this->makeNodeable()->toArray(), 'm');

        $this->assertDatabaseHas('nodeables', ['name'=>$sibling->name]);        
        $this->assertTrue($root->treeable_id === $sibling->treeable_id);
        $this->assertTrue(Location::areSiblings($node->location, $sibling->location));
    }

    /** @test */
    public function it_will_create_new_male_sibling_when_no_gender_provided()
    {
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());
        
        $node  = $root->newSon($this->makeNodeable()->toArray());
        
        $sibling = $node->newSibling($this->makeNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$sibling->name]);        
        $this->assertTrue($root->treeable_id === $sibling->treeable_id);
        $this->assertTrue(Location::areSiblings($node->location, $sibling->location));
        $this->assertTrue($sibling->isMale());
    }

    /** @test */
    public function it_will_create_new_female_sibling_when_female_gender_provided()
    {
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());
        
        $node  = $root->newSon($this->makeNodeable()->toArray());
        
        $sibling = $node->newSibling($this->makeNodeable()->toArray(), 'f');

        $this->assertDatabaseHas('nodeables', ['name'=>$sibling->name]);        
        $this->assertTrue($root->treeable_id === $sibling->treeable_id);
        $this->assertTrue(Location::areSiblings($node->location, $sibling->location));
        $this->assertTrue($sibling->isFemale());
    }

    /** @test */
    public function it_can_not_create_sibling_for_node_if_wrong_gender_provided()
    {
        $this->expectException(TreeException::class);

        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());
        
        $node  = $root->newSon($this->makeNodeable()->toArray());
        
        $node->newSibling($this->makeNodeable()->toArray(), 'g');
    }

    /** @test */
    public function it_can_not_create_new_sibling_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());
        
        $child  = $root->newSon($this->makeNodeable()->toArray());

        $child->newSibling([]);
    }
}