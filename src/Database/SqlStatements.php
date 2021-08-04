<?php

namespace Girover\Tree\Database;

use Girover\Tree\Location;

class SqlStatements
{
    public static $table;
    public static $counter = 1;

    public static function table()
    {
        if (is_null(static::$table)) {
            static::$table = config('tree.nodes_table');
        }

        return static::$table;
    }

    /**
     * Sql statement for updating location column in the table.
     * Find all locations that start with $old_location
     * and replace the occurrence of $old_location with $new_location.
     *
     * Note: when changing location so all descendants that are
     * conected under this location have to be changed too.
     *
     * @example replace 'aa.ff' with 'bb.ss'
     *
     *          UPDATE `nodes`
     *          SET `location` = CONCAT('bb.ss', SUBSTRING(`location`, FROM 6))
     *          WHERE `tree_id` = 2
     *          AND `location` like 'aa.ff%' ;
     * @return string
     */
    public static function updateLocationSql($tree_id, $old_location, $new_location)
    {
        return "UPDATE `". static::table() ." ` 
                SET `location` = CONCAT('".$new_location."', SUBSTRING(`location` FROM ".(strlen($old_location) + 1).")) 
                WHERE `tree_id` = ".$tree_id." 
                AND `location` like '".$old_location."%'";
    }

    public static function updateMakeFatherForRootSql($tree_id)
    {
        return 'UPDATE '. static::table() .
               ' SET `location` = CONCAT("'.Location::firstPossibleSegment().Location::SEPARATOR.'", `location`) 
                 WHERE `tree_id` = '.$tree_id;
    }
}
