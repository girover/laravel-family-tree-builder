<?php

    return [

        /*
        |--------------------------------------------------------------------------
        | Model That contains trees information in database
        |--------------------------------------------------------------------------
        |
        | string : Name of the model that holds trees information.
        | The database table that contains all trees.
        */
        'tree_model' => \Girover\Tree\Models\Tree::class,

        /*
        |--------------------------------------------------------------------------
        | Model That contain nodes data in database
        |--------------------------------------------------------------------------
        |
        | string : Name of the model that tree gets data from.
        | The database table that contains all nodes.
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
            'public'=>false, // if true, photos will be stored in storage. 
                            //if false, photos will be saved in public folder
            'folder' => 'vendor/tree/images/', // images folder
        ]

    ];