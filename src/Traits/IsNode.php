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
     * @var \Girover\Tree\Models\Node|null
     */
    protected $node = null;

    public function assignNode()
    {
    }
}
