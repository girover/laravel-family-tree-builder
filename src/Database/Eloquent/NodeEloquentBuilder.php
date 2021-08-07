<?php

namespace Girover\Tree\Database\Eloquent;

use Girover\Tree\Location;
use Illuminate\Database\Eloquent\Builder;

class NodeEloquentBuilder extends Builder
{
    /**
     * to add constraint [models that belongs the given tree number]
     * to QueryBuilder
     * to achive where tree_id = $tree_id on QueryBuilder
     * @param int $tree_id
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function tree($tree_id)
    {
        return $this->where('tree_id', $tree_id);
    }

    /**
     * to add constraint [models that have the given location]
     * to QueryBuilder
     * @param string $location
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function location($location)
    {
        if ($location != null) {
            Location::validate($location);
        }

        $this->where('location', $location);

        return $this;
    }

    /**
     * to add constraint [models locations come after the given location]
     * to QueryBuilder
     * @param string $location
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function fromLocation($location)
    {
        if ($location != null) {
            Location::validate($location);
        }

        $this->where('location', 'like', $location.'%');

        return $this;
    }

    /**
     * to add constraint [models have not given location]
     * to QueryBuilder
     * @param string $location
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function locationNot($location)
    {
        $this->where('location', '!=', $location);

        return $this;
    }

    /**
     * to add constraint [models have locations bigger than given location]
     * to QueryBuilder
     * @param string $location
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function locationAfter($location)
    {
        $this->where('location', '>', $location);

        return $this;
    }

    /**
     * to add constraint [models have location smaller than given location] 
     * to QueryBuilder
     * @param string $location
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function locationBefore($location)
    {
        $this->where('location', '<', $location);

        return $this;
    }

    /**
     * to add constraint [models have specific regexep] to QueryBuilder
     * @param string $regexp
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function locationREGEXP($regexp)
    {
        $this->where('location', 'REGEXP', $regexp);

        return $this;
    }

    /**
     * to add constraint [models have gender of m] to QueryBuilder
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function male()
    {
        $this->where('gender', 'm');

        return $this;
    }

    /**
     * to add constraint [models have gender of f] to QueryBuilder
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function female()
    {
        $this->where('gender', 'f');

        return $this;
    }

    /**
     *
     */
    public function root()
    {
        return $this->first();
    }

    /**
     * Get the total number of nodes in the tree
     *
     * @return int count of nodes in the tree
     */
    public function total()
    {
        return $this->count();
    }

    /**
     * Get the total number of nodes in the tree
     *
     * @return int count of nodes in the tree
     */
    public function totalNodes()
    {
        return $this->total();
    }

    /**
     * Get how many generations there are in the tree
     * @param int $generation
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function generation($generation)
    {
        return $this->where('location', 'REGEXP', Location::singleGenerationREGEXP($generation));
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
