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
    - [Tree](#tree)
    - [Pointer](#pointer)
    - [Node](#node)
  - [Testing](#testing)
  - [Changelog](#changelog)
  - [Contributing](#contributing)
  - [Security Vulnerabilities](#security-vulnerabilities)
  - [Credits](#credits)
  - [License](#license)


## Introduction
This package allows you to build family trees.
    With this package it will be very simple to create trees and add nodes to trees. 

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

this will publish config file ```config\tree.php```
## Assets

After publishing assets, they will be placed in `public` folder 
of the project in a folder called `vendor/tree`.
If you want to move any of these assets to other directories,
you need to provide the new path.
You can change assets configs in `config/tree.php` file.

```php
'assets' => [
    'path_avatar' => 'vendor/tree/images/', // Path to images folder
    'path_css'    => 'vendor/tree/css/',    // Path to css folder
    'path_js'     => 'vendor/tree/js/',     // Path to js folder
]
```

Yous should add the CSS file ```public/vendor/tree/css/tree.css``` to your blade file to get the tree styled.

## Usage

### Tree

To start building a tree or creating a new tree, it is very simple and thanks to [Eloquent](https://laravel.com/docs/8.x/eloquent) Models from [laravel](http://laravel.com). 

```php
use Girover\Tree\Models\Tree;

$tree = Tree::create(
    [
        'name' => 'my first tree',
    ]
);
```
After creating the tree, you can start to add as many node as you like.    
Let's start adding the First node **```Root```** to the tree.
```php
    $data = ['name'=>'root', 'birth_date'=>'2000-01-01'];

    $tree->createRoot($data);
```
**What if you want to make a new Root?**   
In this case you can use the method **```newRoot```** to create new Root, and the previous Root will become a child of the new created Root.
```php
    $new_root_data = ['name'=>'new_root', 'birth_date'=>'2001-01-01'];

    $tree->newRoot($new_root_data);
```
After creating the Root in the tree, let's add first child for the Root.
```php
    use Girover\Tree\Models\Tree;

    $tree = Tree::find(1);

    $first_child_data = ['name'=>'first_child', 'birth_date'=>'2001-01-01'];

    $tree->movePointerToRoot()->newChild($first_child_data ,'m'); // m = male

    // Or you can do this instead
    $tree->movePointerToRoot()->newSon($first_child_data);
```
Now our tree consists of two nodes, Root node and first child of Root.   
To view this tree in the browser you can do this:
```php
    <?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Girover\Tree\Models\Tree;

    class TreeController extends Controller
    {
        public function index()
        {
            $tree     = Tree::find(1);
            $treeHTML = $tree->toHtml()

            return view('tree.index')->with('treeHTML', $treeHTML);
        }
    }
```
And now inside your blade file you can render the tree

```html
    // views/tree/index.blade.php
    <div>
        {!! $treeHTML !!}
    </div>
```

You can call the following method on the object of Tree 
| #   | function                   | Description                                           | Params                                        |
| --- | -------------------------- | ----------------------------------------------------- | --------------------------------------------- |
| 1   | `createRoot($data)`        | create root in the tree.                              | ```$data``` is array of root info             |
| 2   | `newRoot($data)`           | makes new root for the tree.                          | ```$data``` is array of info for the new root |
| 3   | `toHtml()`                 | convert the tree to html to view it                   |                                               |
| 4   | `draw()`                   | convert the tree to html to view it                   |                                               |
| 5   | `toTree()`                 | convert the tree to html to view it                   |                                               |
| 6   | `emptyTree()`              | return an empty tree to view it                       |                                               |
| 7   | `pointer()`                | To get the pointer inside the tree                    |                                               |
| 8   | `movePointerToRoot()`      | To move the pointer to indicate to the root           |                                               |
| 9   | `movePointerTo($location)` | To move the pointer to the given location             |                                               |
| 10  | `goTo($location)`          | To move the pointer to the given location             |                                               |
| 11  | `fatherOf($location)`      | To get the father of the node that has given location |                                               |
| 12  | `wivesOf($location)`       | To get all wives of the node that has given location  |                                               |
| 13  | `ancestorsOf($location)`   | To get all ancestors of the given location            |                                               |
| 14  | `countGenerations()`       | To get how many generations this tree has             |                                               |
| 15  | `nodesOnTop()`             | Get the newest generation members in the tree         |                                               |


### Pointer

Every tree has a Pointer inside it, and this Pointer indicates to one node.    
Pointer can move through all nodes in the tree.     
Because the Pointer indicates to a node inside the tree, so it can call all [methods of Model ```Node```](#node) .   
To get this pointer you can do the following:
```php
    use Girover\Tree\Models\Tree;

    $tree    = Tree::find(1);
    $pointer = $tree->pointer();
```
And now you can use this pointer to make a lot of actions inside the tree, for example moving through nodes, deleting and retrieving more information about nodes.   
Eg.
To move the pointer to location ```aa.aa.df.se```:
```php
    $pointer->to('aa.aa.df.se');
```
And now you can get the node data by calling the method ```node()```
```php
    $node = $pointer->node();
    echo $node->location;
    echo $node->name;
    echo $node->gender;
```

Note that we called method ```node()``` after we had called the method ```to($location)```.   
This is because when a tree instance created, its Pointer indicates to ```null```.
### Node
Node is a person in the tree. Nodes in tree are connected with other nodes by using **Location mechanism**, where every node has its own location, which is a set of characters separated by ```dot```.    
If the Root location in the tree is ```aa```, so the first child's location 
of this root will be ```aa.aa```, and the second will be ```aa.ab``` and so on.

To get the node you can do this:
```php
    use Girover\Tree\Models\Node;

    $node    = Node::find(1);
```
To get all wives of the node:
```php
    $node->wives;
    // to add constraints
    $node->wives()->where('name', $name)->get();
```
When trying to get wives of female node a ```Girover\Tree\Exceptions\TreeException``` will be thrown.   
Note that this will get divorced wives too.   
To get only wives who are not divorced you can do this:
```php
    $node->wives()->withoutDivorced()->get();
```
To get husbands of the node:
```php
    $node->husband;
    // to add constraints
    $node->husband()->where('name', $name)->get();
```
When trying to get husband of male node a ```Girover\Tree\Exceptions\TreeException``` will be thrown.   
Note that this will get divorced husbands too.   
To get only husbands who are not divorced you can do this:
```php
    $node->husband()->withoutDivorced()->get();
```
To assign wife to this node:
```php
    $wife = Node::find($female_node_id)
    return $node->getMarriedWith($wife);
```
To determine if the node is root in the tree
```php
    return $node->isRoot(); // returns true or false
```
Determine if the node has children
```php
    return $node->hasChildren(); // returns true or false
```
Determine if the node has siblings
```php
    return $node->hasSiblings(); // returns true or false
```
to get which generation number in the tree this node is:
```php
    return $node->generation(); // int or null
```
To get the father of the node:
```php
    return $node->father();
```
To get the grandfather of the node:
```php
    return $node->grandfather();
```
To get the ancestor that matches the given number as parameter. 
```php
    $node->ancestor(); // returns father
    $node->ancestor(2); // returns grandfather
    $node->ancestor(3); // returns the father of grandfather
```
To get all ancestors of this node
```php
    return $node->ancestors();
```
To get all uncles of the node:
```php
    return $node->uncles();
```
To get all aunts of the node:
```php
    return $node->aunts();
```
To get all children of the node:
```php
    return $node->children();
```
To get all sons of the node:
```php
    return $node->sons();
```
To get all daughters of the node:
```php
    return $node->daughters();
```
To count the children of the node:
```php
    return $node->countChildren();
```
To count all sons of the node:
```php
    return $node->countSons();
```
To count all daughters of the node:
```php
    return $node->countDaughters();
```
To count all siblings of the node:
```php
    return $node->countSiblings();
```
To count all brothers of the node:
```php
    return $node->countBrothers();
```
To count all sisters of the node:
```php
    return $node->countSisters();
```
To get all descendants of the node:
```php
    return $node->descendants();
```
To get all male descendants of the node:
```php
    return $node->maleDescendants();
```
To get all female descendants of the node:
```php
    return $node->femaleDescendants();
```
To count all descendants of the node:
```php
    return $node->countDescendants();
```
To count all male descendants of the node:
```php
    return $node->countMaleDescendants();
```
To count all female descendants of the node:
```php
    return $node->countFemaleDescendants();
```
To get the first child of the node:
```php
    return $node->firstChild();
```
To get the last child of the node:
```php
    return $node->lastChild();
```
To get all siblings of the node:
```php
    return $node->siblings();
```
To get all brothers of the node:
```php
    return $node->brothers();
```
To get all sisters of the node:
```php
    return $node->sisters();
```
To get the next sibling of the node. gets only one sibling.
```php
    return $node->nextSibling();
```
To get all the next siblings of the node. siblings who are younger.
```php
    return $node->nextSiblings();
```
To get the next brother of the node. gets only one brother.
```php
    return $node->nextBrother();
```
To get all the next brothers of the node. brothers who are younger.
```php
    return $node->nextBrothers();
```
To get the next sister of the node. gets only one sister.
```php
    return $node->nextSister();
```
To get all the next sisters of the node. sisters who are younger.
```php
    return $node->nextSisters();
```
To get the previous sibling of the node. only one sibling.
```php
    return $node->prevSibling();
```
To get all the previous siblings of the node. siblings who are older.
```php
    return $node->prevSiblings();
```
To get the previous brother of the node. only one brother.
```php
    return $node->prevBrother();
```
To get all the previous brothers of the node. brothers who are older.
```php
    return $node->prevBrothers();
```
To get the previous sister of the node. only one sister.
```php
    return $node->prevSister();
```
To get all the previous sisters of the node. sisters who are older.
```php
    return $node->prevSisters();
```
To get the first sibling of the node.
```php
    return $node->firstSibling();
```
To get the last sibling of the node.
```php
    return $node->lastSibling();
```
To get the first brother of the node.
```php
    return $node->firstBrother();
```
To get the last brother of the node.
```php
    return $node->lastBrother();
```
To get the first sister of the node.
```php
    return $node->firstSister();
```
To get the last sister of the node.
```php
    return $node->lastSister();
```
To create new sibling for the node:
```php
    $data = ['name'=>$name, 'birth_date'=>$birth_date];
    return $node->newSibling($data, 'm'); // m = male
```
To create new brother for the node:
```php
    $data = ['name'=>$name, 'birth_date'=>$birth_date];
    $node->newBrother($data);

    // or you can use the newSibling method
    $node->newSibling($data, 'm');
```
To create new sister for the node:
```php
    $data = ['name'=>$name, 'birth_date'=>$birth_date];
    $node->newSister($data);

    // or you can use the newSibling method
    $node->newSibling($data, 'f');
```
To create new son for the node:
```php
    $data = ['name'=>$name, 'birth_date'=>$birth_date];
    $node->newSon($data);

    // or you can use the newChild method
    $node->newChild($data, 'm');
```
To create new daughter for the node:
```php
    $data = ['name'=>$name, 'birth_date'=>$birth_date];
    $node->newDaughter($data);
    
    // or you can use the newChild method
    $node->newChild($data, 'f');
```
to make an existing node as child of another node. Note this will move the node with its children to be child of the given location
```php
    $location = 'aa.fd';
    $node->makeAsSonOf($location);
```
The next code will create father for this node, only if this node is a Root in the tree.
```php
    $data = ['name' => $name, 'birth_date' => $birth_date];
    $node->createFather($data);
```
To display the tree of this node starting from the node itself.
```php
    $node->toHtml();
    // or
    $node->draw();
    // or
    $node->toTree();
```

**Note**: You can use the Pointer of tree to access all methods of class Node.   
for example:
```php
    use Girover\Tree\Models\Tree;

    $tree = Tree::find(1);
    
    $tree->pointer()->to('aa.aa')->father();       // move Pointer to location 'aa.aa' and then get its father.
    $tree->pointer()->grandfather();       // get grandfather of node that Pointer indicates to
    $tree->pointer()->ancestor(3);            // get father of node that Pointer indicates to
    $tree->pointer()->children();          // get children of node that Pointer indicates to
    $tree->pointer()->sons();              // get sons of node that Pointer indicates to
    $tree->pointer()->newDaughter($data);  // create daughter for the node that Pointer indicates to
    $tree->pointer()->newSon($data);  // create son of node that Pointer indicates to
    $tree->pointer()->newSister($data);  // create sister for the node that Pointer indicates to
    $tree->pointer()->newChild($data, 'm');  // create son for the node that Pointer indicates to
    $tree->pointer()->firstChild();  // get the first child of node that Pointer indicates to
    .
    .
    .
    .
    .
    $tree->pointer()->toHtml();
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
