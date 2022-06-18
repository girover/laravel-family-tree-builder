<?php

namespace Girover\Tree\TreeBuilder;

interface TreeBuilderInterface{

    /**
     * to get empty tree
     */
    public function emptyTree();

    /**
     * Getting the tree as Html string to render in the browser
     */
    public function draw();
}