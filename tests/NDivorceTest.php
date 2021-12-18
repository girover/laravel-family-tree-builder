<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NDivorceTest extends TestCase
{
    use DatabaseTransactions;
    use Factoryable;

    /**
     * -------------------------------------------
     * testing divorce method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_divorce_a_node()
    {
        // create new node in database table
        $man = $this->createMaleNode();
        $woman = $this->createFemaleNode(['location' => 'sd.er']);

        $man->getMarriedWith($woman);

        $man->divorce($woman);

        $this->assertDatabaseHas('marriages', ['husband_id' => $man->id, 'wife_id' => $woman->id, 'divorced' => true]);
    }

    /** @test */
    public function it_throws_TreeException_if_given_param_is_not_node()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $man = $this->createMaleNode();

        $man->divorce('not node');
    }

    /** @test */
    public function it_throws_TreeException_if_female_node_tries_to_divorce_node()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $woman = $this->createFemaleNode();
        // creating another male node
        $man = $this->createMaleNode(['location' => 'aa.ww']);

        $woman->divorce($man);
    }

    /** @test */
    public function it_throws_TreeException_if_given_node_is_male_node()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $man = $this->createMaleNode();
        // creating another male node
        $another_man = $this->createMaleNode(['location' => 'aa.ww']);

        $man->divorce($another_man);
    }
}
