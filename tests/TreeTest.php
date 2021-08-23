<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Location;
use Girover\Tree\Models\Tree;
use Girover\Tree\Models\Node;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TreeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_can_create_root_for_tree()
    {
        // Create new Tree in database.
        $tree = Tree::factory()->create();
        // Create Root node for the created tree.
        $root = $tree->createRoot(['name'=>'majed', 'tree_id'=>$tree->id]);
       
        $this->assertDatabaseHas('trees', ['name'=>$tree->name]);
        $this->assertDatabaseHas('nodes', [
            'tree_id'=>$tree->id,
            'location'=>$root->location,
        ]);
    }

    /** @test */
    public function test_can_create_new_root_for_tree()
    {
        // Create new Tree in database.
        $tree = Tree::factory()->create();
        // Create Root node for the created tree.
        $root = $tree->createRoot(['name'=>'majed', 'tree_id'=>$tree->id]);
        // Create new Root and make the previously created root son of this new root.
        $new_root = $tree->newRoot(['name'=>'Hussein', 'tree_id'=>$tree->id]);
        
        $this->assertDatabaseHas('trees', ['name'=>$tree->name]);
        $this->assertDatabaseHas('nodes', ['tree_id'=>$tree->id, 'location'=>$root->location]);
        $this->assertDatabaseHas('nodes', ['tree_id'=>$tree->id, 'location'=>$new_root->location]);
        
        // Check if the new created root is father of the firstly created root.
        $old_root = Node::find($root->id);
        $this->assertEquals($new_root->location, Location::father($old_root->location));
    }

    /** @test */
    public function test_can_create_new_son_for_node()
    {
        // create new node in database table
        $node = Node::factory()->create();
        $son = $node->newSon(Node::factory()->make()->toArray());

        $this->assertDatabaseHas('nodes', ['name'=>$node->name]);
        $this->assertDatabaseHas('nodes', ['name'=>$son->name]);

        // Check if the new is father of $son
        $this->assertEquals($node->location, Location::father($son->location));
    }
}
