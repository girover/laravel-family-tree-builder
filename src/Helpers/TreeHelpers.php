<?php

namespace Girover\Tree\Helpers;

use Illuminate\Database\Eloquent\Model;

class TreeHelpers{
    /**
     * Eloquent model that corresponding the table 'trees'
     * @return string class of model
     */
    public static function nodeableModel()
    {
        return config('tree.nodeable_model');
    }

    /**
     * Eloquent model that corresponding the table 'nodes'
     * @return string class of model
     */
    public static function treeableModel()
    {
        return config('tree.treeable_model');
    }

    /**
     * database table name that contains nodes
     * @return string name of the table
     */
    public static function nodeableTable()
    {
        // $model = static::nodeableModel();
        return (new (static::nodeableModel()))->getTable();
    }

    /**
     * database table name that contains 'trees'
     * @return string name of the table
     */
    public static function treeableTable()
    {
        // $model = static::treeableModel();
        return (new (static::treeableModel()))->getTable();
    }

    // NEW
    /**
     * Getting the path to the images folder
     * where the photos of nodes supposed to be stored
     * 
     * @return string
     */
    public static function photoPath()
    {
        return public_path(rtrim(config('tree.photos_folder'), '/\\')) . DIRECTORY_SEPARATOR;
    }
    // NEW
    /**
     * Getting the url to the images folder in public folder
     * where the photos of nodes supposed to be stored
     * 
     * @return string
     */
    public static function photoUrl()
    {
        return rtrim(config('tree.photos_folder'), '/\\') . '/';
    }

    /**
     * return photo name for node [male, female]
     *
     * @param int|string $gender
     * @return string
     */
    public static function photoIcon($gender = 'm')
    {
        return ($gender === 'm') ? static::malePhoto() : static::femalePhoto();
    }

    /**
     * Getting avatar icon for male nodes
     * 
     * @return string
     */
    public static function malePhoto()
    {
        return 'icon_male.png';
    }

    /**
     * Getting avatar icon for female nodes
     * 
     * @return string
     */
    public static function femalePhoto()
    {
        return 'icon_female.png';
    }

    /**
     * Getting url to avatar icon for male nodes
     * 
     * @return string
     */
    public static function maleAsset()
    {
        return static::photoUrl().static::malePhoto();
    }

    /**
     * Getting url to avatar icon for female nodes
     * 
     * @return string
     */
    public static function femaleAsset()
    {
        return static::photoUrl().static::femalePhoto();
    }

    /**
     * Getting url to avatar photo for a given node
     * 
     * @return string
     */
    public static function photoAsset(Model $nodeable_model)
    {
        $photo_path  = static::photoPath() . $nodeable_model->photo;
        $photo_asset = static::photoUrl();
        
        return (file_exists($photo_path) and ! is_dir($photo_path)) 
                ? asset($photo_asset.$nodeable_model->photo) 
                : asset($photo_asset.static::photoIcon($nodeable_model->gender));
    }
}