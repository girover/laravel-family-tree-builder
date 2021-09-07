<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Models\Node;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NodeTest extends TestCase
{
    use DatabaseTransactions;


    /**
     * -------------------------------------------
     * testing newSon method
     * --------------------------------------------
     */
    /** @test */
    public function test_can_create_new_son_for_node()
    {
        // create new node in database table
        $node = Node::factory()->create();

        $son = $node->newSon(Node::factory()->make()->toArray());
        $this->assertDatabaseHas('nodes', ['name'=>$son->name]);
        
        $this->assertTrue(Location::areFatherAndSon($node->location, $son->location));
        $this->assertTrue($node->tree_id === $son->tree_id);
    }

    /** @test */
    public function test_can_not_create_new_son_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $father = Node::factory()->create();

        $son = $father->newSon([]);
        $this->assertDatabaseHas('nodes', ['name'=>$son->name]);
    }

    /** @test */
    public function test_can_not_create_new_son_for_node_if_no_name_is_provided()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $father = Node::factory()->create();
        $son_data  = ['f_name'=>'father name']; 
        $father->newSon($son_data);        
    }
}
