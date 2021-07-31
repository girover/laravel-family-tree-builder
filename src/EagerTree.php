<?php

namespace Girover\Tree;

class EagerTree extends Tree{

    /**
     * Initializing the tree Pointer
     *
     * Create pointer for this tree and make it free
     * and indicates to null to not querying database.
     *
     * @return void
     */
    public function makePointer()
    {
        $this->pointer = new Pointer($this, true);
    }

}