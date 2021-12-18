<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NNewSisterTest extends TestCase
{
    use DatabaseTransactions;
    use Factoryable;

    /**
     * -------------------------------------------
     * testing newSister method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_create_new_sister_for_node()
    {
        // create new node in database table
        $root = $this->createNode();

        $son = $root->newSon($this->makeNode()->toArray());
        $sister = $son->newSister($this->makeNode()->toArray());

        $this->assertTrue($root->tree_id === $sister->tree_id);
        $this->assertTrue(Location::areSiblings($son->location, $sister->location));
    }

    /** @test */
    public function it_can_not_create_new_sister_for_root_node()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $node = $this->createNode();

        $node->newSister($this->makeNode()->toArray());
    }

    /** @test */
    public function it_can_not_create_new_sister_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        $root = $this->createNode();

        $son = $root->newSon($this->makeNode()->toArray());
        $son->newSister([]);
    }

    // /** @test */
    public function it_can_not_create_new_sister_for_node_if_no_name_is_provided()
    {
        $this->expectException(TreeException::class);
        $root = $this->createNode();

        $son = $root->newSon($this->makeNode()->toArray());
        $son->newSister(['f_name' => 'father name']);
    }
}
