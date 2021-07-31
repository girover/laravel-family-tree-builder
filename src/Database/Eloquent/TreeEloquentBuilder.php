<?php

namespace Girover\Tree\Database\Eloquent;

use Girover\Tree\EagerTree;
use Girover\Tree\Location;
use Illuminate\Database\Eloquent\Builder;

class TreeEloquentBuilder extends Builder {

  
    /**
     * @var integer id of the tree
     */
    private $tree_id;

    public function tree($tree_id)
    {
        $this->where('tree_id', $tree_id);
        $this->tree_id = $tree_id;
        
        return $this;
    }
    public function location($location)
    {
        if($location != null)
            Location::validate($location);

        $this->where('location', $location);

        return $this;
    }
    public function fromLocation($location)
    {
        if($location != null)
            Location::validate($location);

        $this->where('location', 'like', $location.'%');

        return $this;
    }
    public function locationNot($location)
    {
        $this->where('location', '!=', $location);

        return $this;
    }
    public function locationAfter($location)
    {
        $this->where('location', '>', $location);

        return $this;
    }
    public function locationBefore($location)
    {
        $this->where('location', '<', $location);

        return $this;
    }
    public function locationREGEXP($regexp)
    {
        $this->where('location', 'REGEXP', $regexp);

        return $this;
    }
    public function male()
    {
        $this->where('gender', 'm');

        return $this;
    }
    public function female()
    {
        $this->where('gender', 'f');

        return $this;
    }

    public function root()
    {
        return $this->first();
    }

    /**
     * Get the total number of nodes in the tree
     * 
     * @return integer count of nodes in the tree
     */
    public function total()
    { 
        return $this->count();
    }

    /**
     * Get the total number of nodes in the tree
     * 
     * @return integer count of nodes in the tree
     */
    public function totalNodes()
    { 
        return $this->total();
    }

    /**
     * Get how many generations there are in the tree
     * 
     * @return integer count of nodes in the tree
     */
    public function generations()
    { 
        return (new EagerTree($this->tree_id))->totalGenerations();
    }

    /**
     * Get the data of the tree as html
     * 
     * @return string tree as html
     */
    public function toTree()
    { 
        return $this->get()->toTree();
    }
    /**
     * Get the data of the tree as html
     * 
     * @return string html string
     */
    public function toHtml()
    {
        return $this->toTree();
    }

    /**
     * Get the data of the tree as html
     * 
     * @return string html string
     */
    public function draw()
    {
        return $this->toTree();
    }
}