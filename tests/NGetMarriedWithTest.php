<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NGetMarriedWithTest extends TestCase
{
    use DatabaseTransactions;
    use Factoryable;

    /**
     * -------------------------------------------
     * testing getMarriedWith($wife) method
     * --------------------------------------------
     */
    /** @test 1 */
    public function it_can_get_married_with_female_node()
    {
        // create new node in database table
        $man = $this->createNode();
        $woman = $this->createFemaleNode(['location' => 'aa.aa']);

        $man->getMarriedWith($woman);

        $this->assertDatabaseHas('marriages', ['husband_id' => $man->id, 'wife_id' => $woman->id]);
    }

    /** @test 2 */
    public function it_throws_TreeException_if_given_param_is_not_Node()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $man = $this->createNode();
        $woman = 'string instead of Model object';

        // wrong date is provided
        $man->getMarriedWith($woman);
    }

    /** @test 3 */
    public function it_throws_TreeException_if_given_param_is_male_Node()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $man = $this->createMaleNode();
        $woman = $this->createMaleNode(['location' => 'ss.dd']);

        // wrong date is provided
        $man->getMarriedWith($woman);
    }

    /** @test 4 */
    public function it_throws_TreeException_if_female_tries_to_get_married()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $man = $this->createFemaleNode(['location' => 'ss.dd']);
        $woman = $this->createFemaleNode();

        // wrong date is provided
        $man->getMarriedWith($woman);
    }

    /** @test 5 */
    public function it_can_not_get_married_with_one_who_already_married_with()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $man = $this->createNode();
        $woman = $this->createFemaleNode(['location' => 'aa.aa']);

        //Getting married with woman
        $man->getMarriedWith($woman);
        //trying to get married with same woman
        $man->getMarriedWith($woman);
    }

    /** @test 6 */
    public function it_throws_TreeException_when_trying_getting_married_with_himself()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $man = $this->createNode();

        //Getting married with woman
        $man->getMarriedWith($man);
    }

    /** @test 7 */
    public function it_can_save_marriage_info_in_database_if_provided()
    {
        // create new node in database table
        $man = $this->createMaleNode();
        $woman = $this->createFemaleNode(['location' => 'aa.aa']);

        $data = ['date_of_marriage' => '2000/01/01','marriage_desc' => 'Marriage Description'];
        //Getting married with woman
        $man->getMarriedWith($woman, $data);

        $this->assertDatabaseHas('marriages', ['husband_id' => $man->id, 'wife_id' => $woman->id, 'date_of_marriage' => $data['date_of_marriage'], 'marriage_desc' => $data['marriage_desc']]);
    }
}
