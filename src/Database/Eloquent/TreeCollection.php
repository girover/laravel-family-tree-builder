<?php

namespace Girover\Tree\Database\Eloquent;

use Girover\Tree\EagerTree;
use Girover\Tree\Exceptions\TreeException;
use Illuminate\Database\Eloquent\Collection;

class TreeCollection extends Collection{

    /**
     * determine if there are nodes in the collection
     * 
     * @return boolean
     */
    protected function isEmptyCollection()
    {
        return $this->count() > 0 ? false : true;
    }

    /**
     * determine if there is only one node in the collection
     * 
     * @return boolean
     */
    protected function hasOnlyOneNode()
    {
        return $this->count() == 1 ? true : false;
    }

    /**
     * Determine if the collection is correctly sorted.
     * this means if the nodes are sorted ASC.
     * 
     * @return boolean
     */
    protected function isCorrectlySorted()
    {
        if(!$this->isEmptyCollection()){
            if($this->hasOnlyOneNode()){
                return true;
            }
            return $this[0]->location < $this[1]->location ? true : false;
        }
        return true;
    }

    /**
     * Get the collection as tree Html 
     * 
     * @return string tree as Html text
     */
    public function toHtml()
    {
        return $this->toTree();
    }

    /**
     * Get the collection as tree Html 
     * 
     * @return string tree as Html text
     */
    public function draw()
    {
        return $this->toTree();
    }

    /**
     * Get the collection as tree Html 
     * 
     * @return string tree as Html text
     */
    public function toTree()
    {
        if($this->count() == 0){
            return (new EagerTree(null))->emptyTree();
        }
        if(!$this->isCorrectlySorted()){
            throw new TreeException("Error: Tree nodes must be correctly sorted before trying to build it.", 1);           
        }
        return (new EagerTree($this->first()->tree_id))->silentLoad($this)->draw();
    }

}