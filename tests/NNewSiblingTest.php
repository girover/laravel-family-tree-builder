<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NNewSiblingTest extends TestCase
{
    use DatabaseTransactions;
    use Factoryable;

    /**
     * -------------------------------------------
     * testing newSibling method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_create_new_sibling_for_node()
    {
        // create new node in database table
        $root = $this->createNode();

        $son = $root->newSon($this->makeNode()->toArray());
        $brother = $son->newSibling($this->makeNode()->toArray());
        $this->assertDatabaseHas('nodes', ['name' => $brother->name]);

        $this->assertTrue($root->tree_id === $brother->tree_id);
        $this->assertTrue(Location::areSiblings($son->location, $brother->location));
    }

    /** @test */
    public function it_will_create_new_male_sibling_for_node_when_no_gender_provided()
    {
        // create new node in database table
        $node = $this->createNode();

        $son = $node->newChild($this->makeNode()->toArray());
        $sibling = $node->newChild($this->makeNode()->toArray());
        $this->assertDatabaseHas('nodes', ['name' => $son->name]);

        $this->assertTrue(Location::areSiblings($son->location, $sibling->location));
        $this->assertTrue($son->tree_id === $sibling->tree_id);
        $this->assertTrue($sibling->gender === 'm');
    }

    /** @test */
    public function it_will_create_new_female_sibling_for_node_when_no_female_gender_provided()
    {
        // create new node in database table
        $node = $this->createNode();

        $child = $node->newChild($this->makeNode()->toArray());
        $sibling = $child->newSibling($this->makeNode()->toArray(), 'f');

        $this->assertTrue(Location::areSiblings($child->location, $sibling->location));
        $this->assertTrue($child->tree_id === $sibling->tree_id);
        $this->assertTrue($sibling->gender === 'f');
    }

    /** @test */
    public function it_can_not_create_sibling_for_node_if_wrong_gender_provided()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $node = $this->createNode();

        $child = $node->newChild($this->makeNode()->toArray());
        $child->newSibling($this->makeNode()->toArray(), 'wrong-gender-here');
    }

    /** @test */
    public function it_can_not_create_new_sibling_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $root = $this->createNode();
        $child = $root->newChild($this->makeNode()->toArray());
        $child->newSibling([]);
    }

    /** @test */
    public function it_can_not_create_new_sibling_for_node_if_no_name_is_provided()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $father = $this->createNode();
        $son = $father->newChild($this->makeNode()->toArray());
        // no name is provided in data array
        $sibling_data = ['f_name' => 'father name'];
        $son->newSibling($sibling_data);
    }
}
