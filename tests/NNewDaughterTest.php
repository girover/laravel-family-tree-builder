<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NNewDaughterTest extends TestCase
{
    use DatabaseTransactions;
    use Factoryable;

    /**
     * -------------------------------------------
     * testing newDaughter method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_create_new_daughter_for_node()
    {
        // create new node in database table
        $node = $this->createNode();

        $daughter = $node->newDaughter($this->makeNode()->toArray());
        $this->assertDatabaseHas('nodes', ['name' => $daughter->name]);

        $this->assertTrue(Location::areFatherAndChild($node->location, $daughter->location));
        $this->assertTrue($node->tree_id === $daughter->tree_id);
    }

    /** @test */
    public function it_can_not_create_new_daughter_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $father = $this->createNode();

        $father->newDaughter([]);
    }

    /** @test */
    public function it_can_not_create_new_daughter_for_node_if_no_name_is_provided()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $father = $this->createNode();
        // no name is provided in data array
        $daughter_data = ['f_name' => 'father name'];
        $father->newSon($daughter_data);
    }
}
