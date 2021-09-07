<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Models\Tree;
use Girover\Tree\Models\Node;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TreeTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * -------------------------------------------
     * testing CreateRoot method
     * --------------------------------------------
     */
    /** @test */
    public function test_can_create_root_for_tree()
    {
        // Create new Tree in database.
        $tree = Tree::factory()->create();
        // Create Root node for the created tree.
        $root = $tree->createRoot(['name'=>'majed']);
       
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
            'name'=>'Root',
            'tree_id'=>$tree->id,
            'location'=>$root->location,
        ]);
        $tree->createRoot(['name'=>'Another root']);
    }

    /** @test */
    public function test_can_not_create_root_for_tree_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);        
        // Create new Tree in database.
        $tree = Tree::factory()->create();
        // try to create Root node for the created tree, when no data are provided.
        $tree->createRoot([]); 
    }

    /** @test */
    public function test_can_not_create_root_for_tree_if_field_name_is_not_provided()
    {
        $this->expectException(TreeException::class);        
        // Create new Tree in database.
        $tree = Tree::factory()->create();
        // try to create Root node for the created tree. when the field 'name' is not provided.
        $tree->createRoot(['not_found_field_name'=>'tree name']);
    }

    /**
     * -------------------------------------------
     * testing newRoot method
     * --------------------------------------------
     */

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
        $this->assertTrue(Location::areFatherAndSon($new_root->location, $old_root->location));
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
        // Trying to create root with no data provided
        $new_root = $tree->newRoot([]);
        $this->assertNull($new_root);        
    }

    /** @test */
    public function test_can_not_create_new_root_for_tree_if_field_name_is_not_provided()
    {
        $this->expectException(TreeException::class);        
        // Create new Tree in database.
        $tree = Tree::factory()->create();
        // Create new Root node for the created tree.
        $root_data = ['not_found_field_name'=>'tree name'];
        $tree->newRoot($root_data);
    }

    /** @test */
    public function test_creating_new_root_for_tree_will_create_first_root_if_the_tree_is_empty()
    {
        // Create new Tree in database.
        $tree = Tree::factory()->create();
        $this->assertDatabaseHas('trees', ['name'=>$tree->name]);
        // Trying to make new root for the created tree.
        $root = $tree->newRoot(['name'=>'new root']);
        // insure that the location of created root is 'aa'.
        $this->assertEquals(Location::firstPossibleSegment(), $root->location);
        $this->assertDatabaseHas('nodes', ['tree_id'=>$tree->id, 'location'=>$root->location]);       
    }
}
