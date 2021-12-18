<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Location;
use Girover\Tree\Models\Node;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NMoveToTest extends TestCase
{
    use DatabaseTransactions;
    use Factoryable;

    /**
     * -------------------------------------------
     * testing moveTo method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_move_node_to_be_child_of_another_node()
    {
        // create new node in database table
        $node = $this->createNode();

        $son1 = $node->newSon($this->makeNode()->toArray());
        $son2 = $node->newSon($this->makeNode()->toArray());

        $this->assertTrue($son1->tree_id === $son2->tree_id);
        $this->assertTrue(Location::areSiblings($son1->location, $son2->location));

        // Make son1 as son of son2
        $son1->moveTo($son2);

        $son2_son = $son2->firstChild();
        $this->assertNotNull($son2_son);
    }
}
