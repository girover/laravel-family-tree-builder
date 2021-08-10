<?php

namespace Girover\Tree\Database\Sql;

class Delete extends SqlStatements
{
    /**
     * To delete node with its children from database
     * 
     * @param int $tree_id
     * @param int $location
     * @return string
     */
    public static function nodeWithChildren($tree_id, $location)
    {
        return "DELETE FROM `". static::table() ."` 
                WHERE `tree_id` = ".$tree_id." 
                AND `location` like '".$location."%'";
    }
}