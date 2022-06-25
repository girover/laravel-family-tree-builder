<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NFatherTest extends TestCase
{
    use DatabaseTransactions, Factoryable;

    /**
     * -------------------------------------------
     * testing father method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_get_father_of_node()
    {
        $tree = $this->createTreeable();
        // create new node in database table
        $nodeable = $this->createNodeable();
        $root = $tree->createRoot($nodeable);

        $son  = $root->newSon(['name'=>'son', 'father_name'=>'sonfathername', 'birth_date'=>'1111-11-11']);
        
        
        $father = $son->father();
        $this->assertTrue(Location::areFatherAndChild($father->location, $son->location));
        // $this->assertTrue($node->id === $father->id);
    }
    /** @test */
    public function it_throws_TreeException_when_trying_to_get_father_of_the_root()
    {
        $this->expectException(TreeException::class);

        $tree = $this->createTreeable();
        // create new node in database table
        $nodeable = $this->createNodeable();
        $root = $tree->createRoot($nodeable);

        $root->father();
    }
}