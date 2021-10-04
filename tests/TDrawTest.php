<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TDrawTest extends TestCase
{
    use DatabaseTransactions, Factoryable;

    /**
     * -------------------------------------------
     * testing Draw method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_draw_a_tree()
    {
        // create new tree in database table
        $tree = $this->createTree();

        $root = $tree->createRoot(['name'=>'Root']);
        $this->assertDatabaseHas('nodes', ['tree_id'=>$tree->id, 'name'=>$root->name]);

        $root->newSon($this->makeNode()->toArray());
        $root->newSon($this->makeNode()->toArray());

        $html = $tree->draw();
        $this->assertStringContainsString('<div id="tree" class="tree">', $html);
    }
}