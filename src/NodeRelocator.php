<?php

namespace Girover\Tree;

use Girover\Tree\Exceptions\TreeException;
use Illuminate\Support\Facades\DB;

class NodeRelocator
{
    public static function moveAfter($node_to_move, $target_node)
    {
        static::movementValidate($node_to_move, $target_node);

        try {
            DB::beginTransaction();

            if ($node_to_move->isSiblingOf($target_node)) {
                static::moveAfterSibling($node_to_move, $target_node);
            }else{
                static::moveAfterNoneSibling($node_to_move, $target_node);
            }

            DB::commit();

            return true;
        } catch (\Throwable $th) {
            DB::rollBack();

            throw new TreeException("Could not move the node after this node: ".$th->getMessage(), 1);                      
        }
    }
    public static function moveBefore($node_to_move, $target_node)
    {
        static::movementValidate($node_to_move, $target_node);

        try {
            DB::beginTransaction();

            if ($node_to_move->isSiblingOf($target_node)) {
                static::moveBeforeSibling($node_to_move, $target_node);
            }else{
                static::moveBeforeNoneSibling($node_to_move, $target_node);
            }

            DB::commit();

            return true;

        } catch (\Throwable $th) {
            DB::rollBack();

            throw new TreeException("Could not move the node before this node", 1);            
        }
    }
    /**
     * to move a node after one of his siblings
     * The node that should be moved is
     * sibling for the target node
     * @param \Girover\Tree\Models\Node $node_to_move
     * @param \Girover\Tree\Models\Node $target_node
     */
    public static function moveAfterSibling($node_to_move, $target_node)
    {
        
        // get the node with all its siblings
        $siblings = $node_to_move->withSiblings();
        // Make temporarily location for the node that should be sended
        $new_sibling_location = static::newSiblingLocation($target_node, $siblings->last());
        
        if ($node_to_move->location > $target_node->location) {
            $nodes_to_shift = $siblings->where('location', '>', $target_node->location)
            ->where('location', '<=', $node_to_move->location);
            
            // add new node with new location to the end of the collection                          
            $nodes_to_shift->push(static::tempNodeable($node_to_move->treeable_id, $new_sibling_location));
            
            $nodes_to_shift_reversed = $nodes_to_shift->reverse();
            static::shiftLocations($nodes_to_shift_reversed);
            $nodes_to_shift_reversed->first()->updateLocation($nodes_to_shift_reversed->last()->location);
            
            return true;
        }
        
        $nodes_to_shift = $siblings->where('location', '>=', $node_to_move->location)
                                   ->where('location', '<=', $target_node->location);

        // add new node with new location to the beginning of the collection
        $nodes_to_shift->prepend(static::tempNodeable($node_to_move->treeable_id, $new_sibling_location));

        static::shiftLocations($nodes_to_shift);
        $nodes_to_shift->first()->updateLocation($nodes_to_shift->last()->location);  
    }
    
    /**
     * to move a node before one of his siblings
     * The node that should be moved is
     * sibling for the target node
     * @param \Girover\Tree\Models\Node $node_to_move
     * @param \Girover\Tree\Models\Node $target_node
     */
    public static function moveBeforeSibling($node_to_move, $target_node)
    {
        
        // get the node with all its siblings
        $siblings = $node_to_move->withSiblings();

        // Make temporarily location for the node that should be sended
        $new_sibling_location = static::newSiblingLocation($target_node, $siblings->last());

        if ($node_to_move->location > $target_node->location) {
            $nodes_to_shift = $siblings->where('location', '>=', $target_node->location)
                                       ->where('location', '<=', $node_to_move->location);
            
            // add new node to the end of the collection                          
            $nodes_to_shift->push(static::tempNodeable($node_to_move->treeable_id, $new_sibling_location));
            
            $nodes_to_shift_reversed = $nodes_to_shift->reverse();
            static::shiftLocations($nodes_to_shift_reversed);
            $nodes_to_shift_reversed->first()->updateLocation($nodes_to_shift_reversed->last()->location);
            return true;
        }
        $nodes_to_shift = $siblings->where('location', '>=', $node_to_move->location)
                                    ->where('location', '<', $target_node->location);

        // add new node to the beginning of the collection
        $nodes_to_shift->prepend(static::tempNodeable($node_to_move->treeable_id, $new_sibling_location));
        
        static::shiftLocations($nodes_to_shift);
        $nodes_to_shift->first()->updateLocation($nodes_to_shift->last()->location); 
    }

    /**
     * to move a node after another node which in not a sibling for the node
     * 
     * @param \Girover\Tree\Models\Node $node_to_move
     * @param \Girover\Tree\Models\Node $target_node
     */
    public static function moveAfterNoneSibling($node_to_move, $target_node)
    {
        if ($node_to_move->generation() < $target_node->generation()) {
            return static::moveDownAfter($node_to_move, $target_node);
        }
        return static::moveUpAfter($node_to_move, $target_node);
    }

    /**
     * to move a node before another node which in not a sibling for the node
     * 
     * @param \Girover\Tree\Models\Node $node_to_move
     * @param \Girover\Tree\Models\Node $target_node
     */
    public static function moveBeforeNoneSibling($node_to_move, $target_node)
    {
        if ($node_to_move->generation() < $target_node->generation()) {
            return static::moveDownBefore($node_to_move, $target_node);
        }
        return static::moveUpBefore($node_to_move, $target_node);
    }

    /**
     * To move a node after a node which has older generation
     * 
     * @param \Girover\Tree\Models\Node $node_to_move
     * @param \Girover\Tree\Models\Node $target_node
     */
    public static function moveUpAfter($node_to_move, $target_node)
    {
        $to_move_nodes_to_shift = $node_to_move->youngerSiblings();
        $to_move_nodes_to_shift->prepend(clone $node_to_move);

        $new_sibling_location = static::newSiblingLocation($target_node, $target_node->lastSibling());
        
        $node_to_move->updateLocation($new_sibling_location);
        $node_to_move->location = $new_sibling_location;
        
        static::shiftLocations($to_move_nodes_to_shift);
        
        static::moveAfterSibling($node_to_move, $target_node);
    }

    /**
     * To move a node after a node which has younger generation
     * 
     * @param \Girover\Tree\Models\Node $node_to_move
     * @param \Girover\Tree\Models\Node $target_node
     */
    public static function moveDownAfter($node_to_move, $target_node)
    {
        $to_move_nodes_to_shift = $node_to_move->youngerSiblings();
        $to_move_nodes_to_shift->prepend(clone $node_to_move);
        
        $new_sibling_location = static::newSiblingLocation($target_node, $target_node->lastSibling());
        
        $node_to_move->updateLocation($new_sibling_location);
        $node_to_move->location = $new_sibling_location;
        
        static::moveAfterSibling($node_to_move, $target_node);

        static::shiftLocations($to_move_nodes_to_shift);
    }

    /**
     * To move a node before a node which has older generation
     * 
     * @param \Girover\Tree\Models\Node $node_to_move
     * @param \Girover\Tree\Models\Node $target_node
     */
    public static function moveUpBefore($node_to_move, $target_node)
    {
        $to_move_nodes_to_shift = $node_to_move->youngerSibling();
        $to_move_nodes_to_shift->prepend(clone $node_to_move);

        $new_sibling_location = static::newSiblingLocation($target_node, $target_node->lastSibling());
        
        $node_to_move->updateLocation($new_sibling_location);
        $node_to_move->location = $new_sibling_location;
        
        static::shiftLocations($to_move_nodes_to_shift);
        
        static::moveBeforeSibling($node_to_move, $target_node); 
    }

    /**
     * To move a node before a node which has younger generation
     * 
     * @param \Girover\Tree\Models\Node $node_to_move
     * @param \Girover\Tree\Models\Node $target_node
     */
    public static function moveDownBefore($node_to_move, $target_node)
    {
        $to_move_nodes_to_shift = $node_to_move->youngerSiblings();
        $to_move_nodes_to_shift->prepend(clone $node_to_move);;
        
        $new_sibling_location = static::newSiblingLocation($target_node, $target_node->lastSibling());
        
        $node_to_move->updateLocation($new_sibling_location);
        $node_to_move->location = $new_sibling_location;
        
        static::moveBeforeSibling($node_to_move, $target_node);

        static::shiftLocations($to_move_nodes_to_shift);
    }

    /**
     * Shift the given nodes
     * Change the location of the given nodes
     * 
     * @param \Illuminate\Database\Eloquent\Collection $nodes_to_shift
     * @return bool
     */
    public static function shiftLocations($nodes_to_shift)
    {
        $location = null;

        foreach ($nodes_to_shift as $node_to_shift) {

            // First node in the collection will be removed
            if ($location === null) {
                $location = $node_to_shift->location;
                continue;
            }
            // Give the next node the location of the previous node
            $node_to_shift->updateLocation($location);
            
            $location = $node_to_shift->location;
        }

        return true;
    }

    /**
     * To generate location for new sibling for the target node
     * 
     * @param \Girover\Tree\Models\Node $target_node
     * @param \Girover\Tree\Models\Node $last_sibling
     * 
     * @return string new location
     */
    public static function newSiblingLocation($target_node, $last_sibling)
    {
        if ($last_sibling && ($last_sibling->location > $target_node->location)) {
            return Location::nextSibling($last_sibling->location);
        }else{
            return Location::nextSibling($target_node->location);
        }
    }

    /**
     * generate temporarily node
     * that helps on shifting
     * 
     * @param string $location
     * @return \Girover\Tree\Models\Node
     */
    public static function tempNodeable($treeable_id, $location)
    {
        $temp_node = new (nodeableModel());
        $temp_node->treeable_id = $treeable_id;
        $temp_node->location = $location;
        
        return $temp_node;
    }

    /**
     * Determine if it is possible to move the node to the given location
     * 
     * @param \Girover\Tree\Models\Node $node_to_move
     * @param \Girover\Tree\Models\Node $target_node
     *
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public static function MovementValidate($node_to_move, $target_node)
    {        
        // to prevent making ancestor as child of a descendant
        if ($node_to_move->isAncestorOf($target_node)) {
            throw new TreeException("Can not make ancestors as child of descendants", 1);            
        }
        // The root node is not moveable in a tree
        if ($node_to_move->isRoot()) {
            throw new TreeException("Can not move the Root node in a tree", 1);            
        }
        // not allowed to add nodes after or before the Root in a tree
        if ($target_node->isRoot()) {
            throw new TreeException("Can not move a node after or before the Root node in a tree, all nodes should be under the Root", 1);            
        }
    }
}
