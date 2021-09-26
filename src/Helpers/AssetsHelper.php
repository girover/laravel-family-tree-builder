<?php

namespace Girover\Tree\Helpers;

abstract class AssetsHelper{

    /**
     * Photos folder is placed in storage/app/public
     * uses to store node photos
     * 
     * @return string the name of photos folder inside storage/app/public
     */
    public static function photosStorageFolder()
    {
        return config('tree.storage.photos_folder');
    }

    /**
     * Uses for asset node photos
     * asset($photo_name);
     * 
     * @return string 
     */
    public static function photosAssetFolder()
    {
        return 'storage/'.static::photosStorageFolder();
    }
}