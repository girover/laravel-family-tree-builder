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
        // create new node in database table
        $node = $this->createNode();

        $son = $node->newSon($this->makeNode()->toArray());
        $this->assertDatabaseHas('nodes', ['name'=>$son->name]);
        
        $this->assertTrue(Location::areFatherAndSon($node->location, $son->location));
        $this->assertTrue($node->tree_id === $son->tree_id);
    }

    /** @test */
    public function it_can_not_create_new_son_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $father = $this->createNode();

        $father->newSon([]);
    }

    /** @test */
    public function it_can_not_create_new_son_for_node_if_no_name_is_provided()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $father = $this->createNode();
        // no name is provided in data array
        $son_data  = ['f_name'=>'father name']; 
        $father->newSon($son_data);      
    }
}