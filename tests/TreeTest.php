<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
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
        $root = $tree->createRoot(['name'=>'majed']);
       
        $this->assertDatabaseHas('trees', ['name'=>$tree->name]);
        $this->assertDatabaseHas('nodes', [
            'tree_id'=>$tree->id,
            'location'=>$root->location,
        ]);
    }

    /** @test */
    public function test_can_not_create_root_for_tree_if_root_exists()
    {
        $this->expectException(TreeException::class);        
        // Create new Tree in database.
        $tree = Tree::factory()->create();
        // Create Root node for the created tree.
        $root = $tree->createRoot(['name'=>'Root']);

        $this->assertDatabaseHas('nodes', [
            'tree_id'=>$tree->id,
            'location'=>$root->location,
        ]);
        $another_root = $tree->createRoot(['name'=>'Another root']);
        $this->assertNotInstanceOf(Node::class, $another_root); 
    }

    /** @test */
    public function test_can_not_create_root_for_tree_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);        
        // Create new Tree in database.
        $tree = Tree::factory()->create();
        // Create Root node for the created tree.
        $root_data = [];
        $tree->createRoot($root_data); 
    }

    /** @test */
    public function test_can_create_new_root_for_tree()
    {
        // Create new Tree in database.
        $tree = Tree::factory()->create();
        // Create Root node for the created tree.
        $root = $tree->createRoot(['name'=>'majed']);
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
    public function test_can_not_create_new_root_for_tree_if_no_data_are_provided()
    {
        // Create new Tree in database.
        $tree = Tree::factory()->create();
        $this->assertDatabaseHas('trees', ['name'=>$tree->name]);
        // Create Root node for the created tree.
        $root = $tree->createRoot(['name'=>'majed']);
        $this->assertDatabaseHas('nodes', ['tree_id'=>$tree->id, 'location'=>$root->location]);
        // Try to create root with no data provided
        $new_root = $tree->newRoot([]);
        $this->assertNull($new_root);        
    }
}
