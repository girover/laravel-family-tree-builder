<?php

namespace Girover\Tree\Traits;

use Girover\Tree\Models\Node;
use Girover\Tree\Models\Tree;
use Girover\Tree\Pointer;

/**
 *  The model `Tree` has to use this trait
 */
trait Treeable
{
    /**
     * represent a node in the tree [current node]
     *
     * @var Girover\Tree\Models\Node
     */
    private $pointer = null;

    public static function bootTreeable()
    {
    }

    public function initializeTreeable()
    {
        // $this->pointer = Node::find(1);
        // $this->makePointer();
    }

    /**
     * Tree has a pointer. create and make it free
     *
     * @return Girover\Tree\Pointer
     */
    private function makePointer()
    {
        // make free pointer not indicating to any nodes
        $this->pointer = new Pointer($this, true);
    }

    public function pointer()
    {
        return $this->pointer;
    }

    public function nodeModel()
    {
        return Node::class;
    }

    public function treeModel()
    {
        return Tree::class;
    }

    /**
     * Relationsship for getting the root node with trees
     */
    public function root()
    {
        $model = (null !== config('tree.tree_relationships.root.model'))
                ? config('tree.tree_relationships.root.model')
                : \Girover\Tree\Models\Node::class;

        return $this->hasOne($model);
    }

    /**
     * Relationship to get all nodes of this tree
     *
     * @return illuminate\Database\Eloquent\Relationship
     */
    public function nodes()
    {
        return $this->hasMany(Node::class);
    }
}
