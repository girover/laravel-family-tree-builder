<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NNewChildTest extends TestCase
{
    use DatabaseTransactions;
    use Factoryable;

    /**
     * -------------------------------------------
     * testing newChild method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_create_new_child_for_node()
    {
        // create new node in database table
        $node = $this->createNode();

        $child = $node->newChild($this->makeNode()->toArray());
        $this->assertDatabaseHas('nodes', ['name' => $child->name]);

        $this->assertTrue(Location::areFatherAndChild($node->location, $child->location));
        $this->assertTrue($node->tree_id === $child->tree_id);
    }

    /** @test */
    public function it_will_create_new_male_child_for_node_when_no_gender_provided()
    {
        // create new node in database table
        $node = $this->createNode();

        $child = $node->newChild($this->makeNode()->toArray());
        $this->assertDatabaseHas('nodes', ['name' => $child->name]);

        $this->assertTrue(Location::areFatherAndChild($node->location, $child->location));
        $this->assertTrue($node->tree_id === $child->tree_id);
        $this->assertTrue($child->gender === 'm');
    }

    /** @test */
    public function it_will_create_new_female_child_for_node_when_no_female_gender_provided()
    {
        // create new node in database table
        $node = $this->createNode();

        $child = $node->newChild($this->makeNode()->toArray(), 'f');

        $this->assertTrue(Location::areFatherAndChild($node->location, $child->location));
        $this->assertTrue($node->tree_id === $child->tree_id);
        $this->assertTrue($child->gender === 'f');
    }

    /** @test */
    public function it_can_not_create_child_for_node_if_wrong_gender_provided()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $node = $this->createNode();

        $node->newChild($this->makeNode()->toArray(), 'wrong-gender');
    }

    /** @test */
    public function it_can_not_create_new_child_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $father = $this->createNode();

        $father->newChild([]);
    }

    /** @test */
    public function it_can_not_create_new_child_for_node_if_no_name_is_provided()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $father = $this->createNode();
        // no name is provided in data array
        $daughter_data = ['f_name' => 'father name'];
        $father->newChild($daughter_data);
    }
}
