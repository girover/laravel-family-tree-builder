<?php

namespace Girover\Tree\Helpers;

class DBHelper{
    /**
     * Eloquent model that corresponding the table 'trees'
     * @return string class of model
     */
    public static function nodeModel()
    {
        return config('tree.node_model');
    }

    /**
     * Eloquent model that corresponding the table 'nodes'
     * @return string class of model
     */
    public static function treeModel()
    {
        return config('tree.tree_model');
    }

    /**
     * Eloquent model that corresponding the table 'nodes'
     * @return string class of model
     */
    public static function nodeImageModel()
    {
        return config('tree.node_image_model');
    }

    /**
     * database table name that contains nodes
     * @return string name of the table
     */
    public static function nodeTable()
    {
        $model = static::nodeModel();
        return (new $model)->getTable();
    }

    /**
     * database table name that contains 'trees'
     * @return string name of the table
     */
    public static function treeTable()
    {
        $model = static::treeModel();
        return (new $model)->getTable();
    }

    /**
     * database table name that contains 'trees'
     * @return string name of the table
     */
    public static function nodeImageTable()
    {
        $model = static::nodeImageModel();
        return (new $model)->getTable();
    }

    /**
     * database table name that contains 'trees'
     * @return string name of the table
     */
    public static function marriageTable()
    {
        return config('tree.pivots.marriage_table');
    }
}