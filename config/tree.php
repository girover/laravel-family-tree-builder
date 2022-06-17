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
        | Pivot table between nodeable_table and nodeable_table
        |--------------------------------------------------------------------------
        |
        */
        'marriages' => [
            'table'     =>'marriages',
            'first_id'  => 'nodeable_husband_id',
            'second_id' => 'nodeable_husband_id',
        ],
    ];

    return [
        // Laravel-Family-tree
        // Laravel-Family-tree
        /*
        |--------------------------------------------------------------------------
        | Model That contains tree information in database
        |--------------------------------------------------------------------------
        |
        | If model in the Laravel application creates to represents Tree
        | its name should be provided here
        */
        'tree_model' => \Girover\Tree\Models\Tree::class,

        /*
        |--------------------------------------------------------------------------
        | Model That contain nodes data in database
        |--------------------------------------------------------------------------
        |
        | If model in the Laravel application creates to represents Node
        | its name should be provided here
        */
        'node_model' => \Girover\Tree\Models\Node::class,
        
        /*
        |--------------------------------------------------------------------------
        | Model that contains images of the nodes
        |--------------------------------------------------------------------------
        |
        | string : Name of the model that tree's nodes' images has.
        | The database table that contains all nodes images.
        */
        'node_image_model'=> \Girover\Tree\Models\NodeImage::class,

        /*
        |--------------------------------------------------------------------------
        | Pivot tables
        |--------------------------------------------------------------------------
        | table that has no corresponding Model
        | 
        */
        'pivots' => [
            // pivot table between node and node
            'marriage_table' => 'marriages',
        ],

        /*
        |--------------------------------------------------------------------------
        | Nodes loading limitation
        |--------------------------------------------------------------------------
        |
        | this indicates how many nodes have to be loaded from database table
        */
        'loading_amount'=> 500,

        /*
        |--------------------------------------------------------------------------
        | Photos folder
        |--------------------------------------------------------------------------
        | By default, photos will be stored in Storage folder
        | under storage/app/public/vendor/tree/images/
        | if you want to store them in public folder of the application,
        | change public value to 'true'.
        */
        'photos' => [
            'public'=>false, // if false, photos will be stored in Storage folder. 
                             // if true, photos will be saved in public folder
            'folder' => 'vendor/tree/images/', // images folder
        ]

    ];