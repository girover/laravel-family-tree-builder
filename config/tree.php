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
        'images_model'=> \Girover\Tree\Models\NodeImage::class,
        
        
        /*
        |--------------------------------------------------------------------------
        | Database Table That contain nodes data
        |--------------------------------------------------------------------------
        |
        | string : Table Name that tree gets nodes data from
        |
        */
        'nodes_table' => [
            'name'               => 'nodes',
            'location_field'     => 'location',
            'enum_gender_male'   => 'm',
            'enum_gender_female' => 'f',
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
        | Eager loading Relationships with Model 'Node'
        |--------------------------------------------------------------------------
        |
        | Ops: These should not be changed, but only 'model' may be changed 
        | if your model is in another folder than the default folder.
        */
        'tree_relationships'=> [
            'root' => [
                'model' => \Girover\Tree\Models\Node::class,
                'with'  => 'root', // name of relationship that should be eager loaded
            ],
            'nodes' => [
                'model' => \Girover\Tree\Models\Node::class,
                'with'  => 'nodes', // name of relationship that should be eager loaded
            ],
        ], 
        /*
        |--------------------------------------------------------------------------
        | Eager loading Relationships with Model 'Node'
        |--------------------------------------------------------------------------
        |
        | Ops: These should not be changed, but only 'model' may be changed 
        | if your model is in another folder than the default folder.
        */
        'node_relationships'=> [
            'wives' => [
                'model'  => null,
                'with'   => 'wives', // name of relationship that should be eager loaded
                'pivot'  => 'marriages', // name of pivot table that makes many-to-many relationship
            ],
            'images' => [
                'model'=> \Girover\Tree\Models\NodeImage::class,
                'with' => 'images', // name of relationship that should be eager loaded
            ],
        ], 

        /*
        |--------------------------------------------------------------------------
        | Assets: CSS, JS and IMAGES
        |--------------------------------------------------------------------------
        |
        |
        */
        'assets' => [
            'path_avatar' => 'vendor/tree/images/', // Path to image folder
            'path_css'    => 'vendor/tree/css/',  // Path to css files
            'path_js'     => 'vendor/tree/js/',  // Path to js files
        ]

    ];