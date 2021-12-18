<?php

namespace Girover\Tree\Helpers;

class Photos
{
    /**
     * Determine if the photos will be stored in
     * public folder of the application
     * or in Storage folder
     *
     * @return bool
     */
    public static function isPublic()
    {
        return config('tree.photos.public');
    }

    /**
     * Photos folder is placed in storage/app/public
     * uses to store node photos
     *
     * @return string the name of photos folder inside storage/app/public
     */
    public static function folder()
    {
        return config('tree.photos.folder');
    }

    /**
     * for building url to photo
     *
     * @return string
     */
    public static function assetFolder()
    {
        // if the photos folder in public folder
        if (static::isPublic()) {
            return static::folder();
        }

        // the photos folder is in storage
        return 'storage/'.static::folder();
    }

    /**
     * Storing a photo in storage folder
     * @param \Illuminate\Http\UploadedFile $photo
     * @param string $name
     *
     * @return string|false
     */
    public static function store($photo, $name = '')
    {
        if (static::isPublic()) {
            return static::storeInPublic($photo, $name);
        }

        return static::storeInStorage($photo, $name);
    }

    /**
     * Storing a photo in storage folder
     * @param \Illuminate\Http\UploadedFile $photo
     * @param string $name
     *
     * @return string|false
     */
    public static function storeInStorage($photo, $name)
    {
        return $photo->storeAs(Photos::folder(), $name, 'public');
    }

    /**
     * Storing a photo in storage folder
     * @param \Illuminate\Http\UploadedFile $photo
     * @param string $name
     *
     * @return string|false
     */
    public static function storeInPublic($photo, $name)
    {
        return $photo->move(public_path(Photos::folder()), $name);
    }

    /**
     * Generate new name for uploaded photo
     * @param \Illuminate\Http\UploadedFile $photo
     * @param string $name
     * @return string
     */
    public static function newName($photo, $name = '')
    {
        if ($name === '') {
            return date('YmdHis') . '.' . $photo->extension();
        }

        return date('YmdHis') . '_' . $name . '.'.$photo->extension();
    }
}
