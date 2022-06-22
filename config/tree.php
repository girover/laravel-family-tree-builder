<?php
    return [
        /*
        |--------------------------------------------------------------------------
        | Model That uses trait Girover\Tree\Traits\Treeable
        |--------------------------------------------------------------------------
        |
        | example: App\Models\Family::class
        */
        'treeable_model' => null,
        /*
        |--------------------------------------------------------------------------
        | Model That uses trait Girover\Tree\Traits\Nodeable
        |--------------------------------------------------------------------------
        |
        | example: App\Models\Person::class
        */
        'nodeable_model' => null,

        /*
        |--------------------------------------------------------------------------
        | Folder name for images in the public folder
        |--------------------------------------------------------------------------
        |
        | should be in the public folder
        | example: vendor/tree/images
        |        means that the folder is:
        |        path-to-project/public/vendor/tree/images
        */
        'photos_folder' => 'vendor/tree/images',

        /*
        |--------------------------------------------------------------------------
        | html element with class 'node' will has these attributes
        |--------------------------------------------------------------------------
        | <a class="node" data-id="value" data-name="value" data-gender="value">
        |
        | html_node_attributes = [
        |                       'id', 'name', 'father_name','birth_date'                   
        |                    ]
        | Note : all these attributes must be attributes of nodeable model
        */
        'html_node_attributes' => [
            
        ],
    ];