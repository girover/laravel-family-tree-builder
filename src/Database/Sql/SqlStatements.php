<?php

namespace Girover\Tree\Database\Sql;

use Girover\Tree\Helpers\TreeHelpers;

class SqlStatements
{
    public static $counter = 1;

    /**
     * table that contains nodes
     * @return string
     */
    public static function nodesTable()
    {
        return "nodes";
    }
}
