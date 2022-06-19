<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class TreeTest extends TestCase
{
    use DatabaseTransactions, Factoryable;

    /**
     * -------------------------------------------
     * testing CreateRoot method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_create_a_tree()
    {
        // dd(DB::select('SHOW TABLES'));
        // Create new Tree in database.
        $tree = $this->createTreeable();
       
        $this->assertDatabaseHas('treeables', [
            'name'=>$tree->name,
        ]);
    }
    public function it_can_create_root_for_a_tree()
    {
        // Create new Tree in database.
        $tree = $this->createTreeable();
        // Create Root node for the created tree.
        $root = $tree->createRoot(['name'=>'majed', 'father_name'=>'father name', 'birth_date']);
       
        $this->assertDatabaseHas('treeables', [
            'name'=>$tree->name,
        ]);
        $this->assertDatabaseHas('nodeables', [
            'name'=>$tree->name,
        ]);
    }
}
