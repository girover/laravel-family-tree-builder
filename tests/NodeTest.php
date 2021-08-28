<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Models\Tree;
use Girover\Tree\Models\Node;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NodeTest extends TestCase
{
    use DatabaseTransactions;

    
    /** @test */
    public function test_can_create_new_son_for_node()
    {
        // create new node in database table
        $node = Node::factory()->create();
        $son = $node->newSon(Node::factory()->make()->toArray());
        
        $this->assertStringStartsWith($node->location, $son->location);
        $this->assertDatabaseHas('nodes', ['name'=>$node->name]);
        $this->assertDatabaseHas('nodes', ['name'=>$son->name]);

        // Check if the new is father of $son
        $this->assertEquals($node->location, Location::father($son->location));
    }
}
