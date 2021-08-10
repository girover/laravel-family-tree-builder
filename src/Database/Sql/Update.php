<?php

namespace Girover\Tree\Database\Sql;

use Girover\Tree\Database\Sql\SqlStatements;
use Girover\Tree\Location;

class Update extends SqlStatements
{
    /**
     * Sql UPDATE statement to add SEPARATOR '.'
     * to the beginning of all locations in the given tree
     * 
     * @param int $tree_id
     * @return string sql statement
     */
    public static function prependLocationsWithSeparator()
    {
        return 'UPDATE '. static::table() .
               ' SET `location` = CONCAT("'.Location::SEPARATOR.'", `location`)'
               .'WHERE tree_id = ?';
    }

    /**
     * Sql UPDATE statement to add first possible segment
     * 'aaa'|'000' to the beginning of all locations in the given tree
     * NOTE: this method doesn't add '.' to the segment
     * 
     * @param int $tree_id
     * @return string sql statement
     */
    public static function prependLocationsWithFirstPossibleSegmetn()
    {
        return 'UPDATE '. static::table() .
               ' SET `location` = CONCAT("'.Location::firstPossibleSegment().'", `location`)'
               .'WHERE tree_id = ?';
    }

    /**
     * Sql statement for updating location column in the nodes table.
     * Find all locations that start with $old_location
     * and concat them with $new_location to beginning of old location.
     *
     * Note: when changing location so all descendants that are
     * connected under this location have to be changed too.
     *
     * @example replace 'aa.ff' with 'bb.ss'
     *
     *          UPDATE `nodes`
     *          SET `location` = CONCAT('bb.ss', SUBSTRING(`location`, FROM 6))
     *          WHERE `tree_id` = 2
     *          AND `location` like 'aa.ff%' ;
     * @param int $tree_id
     * @param string $old_location
     * @param string $new_location
     * @return string
     */
    public static function changeLocations($tree_id, $old_location, $new_location)
    {
        return "UPDATE `". static::table() ."` 
                SET `location` = CONCAT('".$new_location."', SUBSTRING(`location` FROM ".(strlen($old_location) + 1).")) 
                WHERE `tree_id` = ".$tree_id." 
                AND `location` like '".$old_location."%'";
    }
}