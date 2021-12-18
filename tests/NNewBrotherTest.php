<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NNewBrotherTest extends TestCase
{
    use DatabaseTransactions;
    use Factoryable;

    /**
     * -------------------------------------------
     * testing newBrother method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_create_new_brother_for_node()
    {
        // create new node in database table
        $root = $this->createNode();

        $son = $root->newSon($this->makeNode()->toArray());
        $brother = $son->newBrother($this->makeNode()->toArray());
        $this->assertDatabaseHas('nodes', ['name' => $brother->name]);

        $this->assertTrue($root->tree_id === $brother->tree_id);
        $this->assertTrue(Location::areSiblings($son->location, $brother->location));
    }

    /** @test */
    public function it_can_not_create_new_brother_for_root_node()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $node = $this->createNode();

        $brother = $node->newBrother($this->makeNode()->toArray());
    }

    /** @test */
    public function it_can_not_create_new_brother_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        $root = $this->createNode();

        $son = $root->newSon($this->makeNode()->toArray());
        $son->newBrother([]);
    }

    // /** @test */
    public function it_can_not_create_new_brother_for_node_if_no_name_is_provided()
    {
        $this->expectException(TreeException::class);
        $root = $this->createNode();

        $son = $root->newSon($this->makeNode()->toArray());
        $son->newBrother(['f_name' => 'father name']);
    }
}
