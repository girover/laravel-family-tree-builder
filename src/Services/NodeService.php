<?php

namespace Girover\Tree\Services;

use Girover\Tree\Database\Sql\Update;
use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Helpers\TreeHelpers;
use Girover\Tree\Location;
use Girover\Tree\Models\Node;
use \Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NodeService
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     * the model that represents nodeable models
     */
    protected $nodeable;

    /**
     * Constructor
     * @param null|\Illuminate\Database\Eloquent\Model $nodeable
     */
    public function __construct($nodeable = null) {
        $this->nodeable = $nodeable;
    }

    /**
     * set the node
     * 
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function nodeable(Model $nodeable) {
        $this->nodeable = $nodeable;

        return $this;
    }

    /**
     * Determine if the provider gender
     * is a valid gender
     * @param string $gender
     * @return void
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function validateGender($gender)
    {
        if ($gender !== 'm' && $gender !== 'f') {
            throw new TreeException("Invalid gender is provided", 1);            
        }
    }

    /**
     * Create new node
     *
     * @param \Illuminate\Database\Eloquent\Model
     * @param string $location the location of the new [child|sibling|...]
     * @param int $treeable_id the id of the treeable model
     * @param string $gender the gender of the person
     * 
     * @throws \Girover\Tree\Exceptions\TreeException
     * @return \Girover\Tree\Models\Node
     */
    public function createNewNode($data, $location, $gender = 'm')
    {
        Location::validate($location);

        $this->validateGender($gender);

        try {
            DB::beginTransaction();

            $nodeable = $this->createNodeable($data) ?? throw new TreeException("Failed to insert data", 1);
            
            $node     = $this->createNode(['nodeable_id'=>$nodeable->getKey(), 'treeable_id'=>$this->nodeable->treeable_id, 'location'=>$location, 'gender'=>$gender]);

            // if(!$nodeable || !$node){
            if(!$node){
                throw new TreeException("Failed to create the node", 1);               
            }

            DB::commit();

            return true;

        } catch (\Throwable $th) {            
            DB::rollBack();
            throw $th;            
        }
    }

    // NEW
    public function createNodeable($data)
    {
        if ($data instanceof (TreeHelpers::nodeableModel())) {

            // $data is a model and exists in database
            if ($data->exists){
                return $data;
            }

            $data->save();

            return $data;
        }

        if (! is_array($data)) {
            throw new TreeException("Bad argument type. The argument passed to ".__METHOD__." must be an array or an instance of [".TreeHelpers::nodeableModel()."]. ".gettype($data)." is given", 1);
        }

        return (TreeHelpers::nodeableModel())::create($data);
    }

    /**
     * creating new node for the nodeable model
     * 
     * @param array|\Illuminate\Database\Eloquent\Model $dta
     * 
     * @return Girover\Tree\Models\Node
     */
    public function createNode($data)
    {
        return Node::create($data);
    }



    /**
     * Create father for the root node in the tree
     *
     * @param \Illuminate\Database\Eloquent\Model $root data for the new father
     * @param array $data data for the new father
     * 
     * @return mixed
     */
    public function createFather($data)
    {
        if (! $this->nodeable->isRoot()) {
            throw new TreeException("Error: Can't make father for node:[ ".$this->nodeable->location." ]. node should be root to make father for it.", 1);
        }

        DB::beginTransaction();

        try {
            // update all nodes locations in this tree.
            // Firstly prepend all locations with separator '.'
            // to prevent duplicated locations in same tree
            DB::update(Update::prependLocationsWithSeparator(), [$this->nodeable->treeable_id]);
            // then prepend all locations with 'aaa'
            DB::update(Update::prependLocationsWithFirstPossibleSegment(), [$this->nodeable->treeable_id]);

            // create th new root node with given data
            $new_root = $this->createNewNode($data, Location::firstPossibleSegment(), 'm');

            DB::commit();

            return $new_root;
        } catch (\Throwable $th) {
            // Rollback the database changes
            DB::rollBack();

            throw new TreeException("Error: ".$th->getMessage(), 1);
        }
    }
  
    /**
     * Create new sibling for this node
     * by default the new node is son.
     *
     * @param array|static data for the new sibling
     * @param string gender of the new sibling
     * @return \Girover\Tree\Models\Node|null
     */
    public function newSibling($data, $gender = 'm')
    {
        if ($this->nodeable->isRoot()) {
            throw new TreeException("Cannot add sibling to the Root of the tree.", 1);
        }

        if ($this->nodeable->hasSiblings()) {
            $new_sibling_location = Location::generateNextLocation($this->nodeable->lastSibling()->location);
        } else {
            $new_sibling_location = Location::generateNextLocation($this->nodeable->location);
        }

        Location::validate($new_sibling_location);

        return $this->createNewNode($data, $new_sibling_location, $gender);
    }

    /**
     * Throw an exception when trying to get som of these
     * methods if the nodeable model is not connected with a node yet
     * 
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function throwExceptionIfNotNode()
    {
        if (!$this->nodeable->isNode()) {
            throw new TreeException("This model is not a node yet!!!", 1);            
        }
    }

    /**
     * To build a tree as Html from specific node
     * 
     * @return string html representing the tree starting from a node
     * 
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function buildTreeFromANode()
    {
        $this->throwExceptionIfNotNode();

        $tree = (TreeHelpers::treeableModel())::find($this->nodeable->treeable_id);
        
        $tree->pointer()->to($this->nodeable);

        return $tree->draw();
    }
}