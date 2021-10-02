<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NGrandfatherTest extends TestCase
{
    use DatabaseTransactions, Factoryable;

    /**
     * -------------------------------------------
     * testing grandfather method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_get_grandfather_of_node()
    {
        // create new node in database table
        $root = $this->createMaleNode(['location'=>'aa']);
        $grandson  = $this->createMaleNode(['location'=>'aa.bb.cc']);

        $grandfather = $grandson->grandfather();
        
        $this->assertTrue($root->id === $grandfather->id);
    }
}