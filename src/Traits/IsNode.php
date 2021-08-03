<?php

namespace Girover\Tree\Traits;

/**
 *  This trait make the authenticated model (User) connected with a node
 */
trait IsNode
{
    /**
     * asign the node to this property of authenticated user
     * 
     * @var null | Girover\Tree\Models\Node
     */
    protected $node = null;

    public function asignNode(){
        
    }
}