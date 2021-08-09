![tree-template-2](https://user-images.githubusercontent.com/53403538/128646183-8d716a2c-da22-473a-a06d-1563974afb76.png)
# Building family tree

[![Latest Version on Packagist](https://img.shields.io/packagist/v/girover/tree.svg?style=flat-square)](https://packagist.org/packages/girover/tree)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/girover/tree/run-tests?label=tests)](https://github.com/girover/tree/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/girover/tree/Check%20&%20fix%20styling?label=code%20style)](https://github.com/girover/tree/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/girover/tree.svg?style=flat-square)](https://packagist.org/packages/girover/tree)


---
## Content

  - [Introduction](#introduction)
  - [Installation](#installation)
  - [Assets](#assets)
  - [Usage](#usage)
  - [Testing](#testing)
  - [Changelog](#changelog)
  - [Contributing](#contributing)
  - [Security Vulnerabilities](#security-vulnerabilities)
  - [Credits](#credits)
  - [License](#license)


## Introduction

## Installation

You can install the package via **composer**:

```bash
composer require girover/tree
```

To publish **migrations** and migrate them, run this command in your terminal:

```bash
php artisan vendor:publish --provider="Girover\Tree\TreeServiceProvider" --tag="tree-migrations"
php artisan migrate
```

To publish **assets** you can run the following command:
```bash
php artisan vendor:publish --provider="Girover\Tree\TreeServiceProvider" --tag="tree-assets"
```

For publishing **translation** run the following command:
```bash
php artisan vendor:publish --provider="Girover\Tree\TreeServiceProvider" --tag="tree-translations"
```

You can publish the **config** files with command:
```bash
php artisan vendor:publish --provider="Girover\Tree\TreeServiceProvider" --tag="tree-config"
```

This is the contents of the published **config** file `config/tree.php`:

```php
    return [

        /*
        |--------------------------------------------------------------------------
        | Model That contains trees information in database
        |--------------------------------------------------------------------------
        |
        | string : Name of the model that holds trees information.
        | The database tabe that contains all trees.
        */
        'tree_model' => Girover\Tree\Models\Tree::class,
        /*
        |--------------------------------------------------------------------------
        | Model That contain nodes data in database
        |--------------------------------------------------------------------------
        |
        | string : Name of the model that tree gets data from.
        | The database tabe that contains all nodes.
        */
        'node_model' => Girover\Tree\Models\Node::class,
        
        /*
        |--------------------------------------------------------------------------
        | Model that contains images of the nodes
        |--------------------------------------------------------------------------
        |
        | string : Name of the model that tree's nodes' images has.
        | The database tabe that contains all nodes images.
        */
        'images_model'=> Girover\Tree\Models\NodeImage::class,
        
        
        /*
        |--------------------------------------------------------------------------
        | Database Table That contain nodes data
        |--------------------------------------------------------------------------
        |
        | string : Table Name that tree gets nodes data from
        |
        */
        'nodes_table' => [
            'name' => 'nodes',
            'location_field' => 'location',
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
                'model' => \Girover\Tree\Models\Marriage::class,
                'with'  => 'wives', // name of relationship that should be eager loaded
            ],
            'images' => [
                'model'=> \Girover\Tree\Models\NodeImage::class,
                'with' => 'images', // name of relationship that should be eager loaded
            ],
        ], 

        'tree_profiles'=>'images/tree-profiles/',

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
```

## Assets

After publishing assets, they will be stored in `public` folder 
of the project in a folder called `vendor/tree`.
If you want to move any of these assets to other directories,
you need to provide the new path.
You can change assets configs in `config/tree.php` file.

```php
'assets' => [
    'path_avatar' => 'vendor/tree/images/', // Path to images folder
    'path_css'    => 'vendor/tree/css/',  // Path to css folder
    'path_js'     => 'vendor/tree/js/',  // Path to js folder
]
```

## Usage

you can use both `Girover\Tree\Models\Tree` and `Girover\Tree\Models\Node` to build the family tree

```php
use Girover\Tree\Models\Tree;
use Girover\Tree\Models\Node;

$tree = Tree::find(1); 
$node = Node::find(1);
echo $tree->name;
return $tree->pointer()->to('aaa.aaa')->toHtml();
```
### Tree model

```php
    use Girover\Tree\Models\Tree;

    $tree = Tree::find(1);
```
| function | Description | Returns |
| --- | --- | --- |
| `$tree->addRoot($data)` | add root to the tree. ```$data``` is array of root info | Girover\Tree\Models\Node |
| `$tree->newRoot($data)` | makes new root for the tree. ```$data``` is array of the new root info | Girover\Tree\Models\Node |
| `$tree->toHtml()` | convert the tree to html | string html |
| `$tree->draw()` | convert the tree to html | string |
| `$tree->toTree()` | convert the tree to html | string |
## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Majed Girover](https://github.com/girover)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
