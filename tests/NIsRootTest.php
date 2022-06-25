<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NIsRootTest extends TestCase
{
    use DatabaseTransactions, Factoryable;


    /**
     * -------------------------------------------
     * testing isRoot method
     * --------------------------------------------
     */
    /** @test */
    public function it_should_returns_true_when_applying_on_root_node()
    {
        // Create new Tree in database.
        $tree = $this->createTreeable();
        // Create Root node for the created tree.
        $root = $tree->createRoot(['name'=>'root', 'father_name'=>'root', 'birth_date'=>'1111-11-11']); 
        
        $this->assertTrue($root->isRoot());
    }

    /** @test */
    public function it_should_returns_false_when_applying_on_none_root_node()
    {
        // Create new Tree in database.
        $tree = $this->createTreeable();
        // Create Root node for the created tree.
        $root = $tree->createRoot(['name'=>'root','father_name'=>'root', 'birth_date'=>'1111-11-11']); 
        
        $son = $root->newSon(['name'=>'son name','father_name'=>'fathername','birth_date'=>'1111-11-11']);

        $this->assertFalse($son->isRoot());
    }
}