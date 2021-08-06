# Building family tree

[![Latest Version on Packagist](https://img.shields.io/packagist/v/girover/tree.svg?style=flat-square)](https://packagist.org/packages/girover/tree)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/girover/tree/run-tests?label=tests)](https://github.com/girover/tree/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/girover/tree/Check%20&%20fix%20styling?label=code%20style)](https://github.com/girover/tree/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/girover/tree.svg?style=flat-square)](https://packagist.org/packages/girover/tree)

---
This repo can be used to scaffold a Laravel package. Follow these steps to get started:

1. Press the "Use template" button at the top of this repo to create a new repo with the contents of this tree
2. Run "./configure-tree.sh" to run a script that will replace all placeholders throughout all the files
3. Remove this block of text.
4. Have fun creating your package.
5. If you need help creating a package, consider picking up our <a href="https://laravelpackage.training">Laravel Package Training</a> video course.
---

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/tree.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/tree)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require girover/tree
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --provider="Girover\Tree\TreeServiceProvider" --tag="tree-migrations"
php artisan migrate
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Girover\Tree\TreeServiceProvider" --tag="tree-config"
```

This is the contents of the published config file:

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


    ];
```

## Usage

```php
use Girover\Tree\Models\Tree;
use Girover\Tree\Facades\FamilyTree;

$t = Tree::find(1); 
$tree = FamilyTree::make($tree);
echo $tree->name;
return $tree->pointer()->to('aaa.aaa')->getNode();
```

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
