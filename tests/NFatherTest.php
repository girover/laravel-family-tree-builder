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
        // create new node in database table
        $node = $this->createMaleNode(['location'=>'aa.bb']);
        $son  = $this->createMaleNode(['location'=>'aa.bb.aa']);

        $father = $son->father();
        
        $this->assertTrue($node->id === $father->id);
    }
    /** @test */
    public function it_throws_TreeException_when_trying_to_get_father_of_the_root()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $root = $this->createMaleNode(['location'=>'aa']);

        $root->father();
    }
}