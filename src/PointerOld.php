<?php
/**----------------------------------------------------------------------------------
| Pointer of Tree
| Aouth: Majed Girover
|        girover.mhf@gmail.com
| ---------------------------------------------------------------------------------
| when creating a FamilyTree instance, a pointer object is created and indicates
| to the root node in the Tree.
| the pointer only indicates to nodes in the database table and not to the loaded nodes
| inside the tree property $nodes.
| so every time Poiner moves through the tree it make request to databse table and return
| one record of tree nodes.
| therefor be aware when you draw the tree to show in the browser, because
| if the tree nodes are allready loaded and you move the pointer throgh the tree
| and you want to draw them again, so it will draw the old nodes those were loadded before.
| And to draw the tree from the pointer node, you have to call `load` method to change
| the allready loaded nodes.
|
| */

namespace Girover\Tree;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Models\Node;
use Girover\Tree\Models\Tree;

class PointerOld
{
    /**----------------------------------------------------------------------------------
     * Instance of Girover\Tree\FamilyTree
     * ---------------------------------------------------------------------------------
     * when creating a pointer it take an instance of FamilyTree as parameter
     */
    protected $tree;

    /**----------------------------------------------------------------------------------
     * This variable contains all data of the node that this poiter point to:
     * ---------------------------------------------------------------------------------
     *  it can be [Object, JSON, Model, Database Record ...]
     *  depending on which tree is using this node
     *----------------------------------------------------------------------------------*/
    protected $node;

    /**
     * Initialize the Pointer and indicate it to the root node
     * of the given tree
     *
     * @param Girover\Tree\FamilyTree $tree
     * @param Girover\Tree\Models\Node $indicated_node
     *
     * @return void
     */
    public function __construct(FamilyTree $family_tree, Node $indicated_node = null)
    {
        $this->tree = $family_tree; // This has to be the first init in Poiter, because next line depend on it
        // $this->node = $family_tree->node;
        $this->node = $indicated_node;
    }

    /**
     * Calling some methods when calling undefined methods
     *
     * @param String $name method name
     * @param Array $args parameters that passed to the called method
     * @return Void
     */
    public function __call($name, $args)
    {
        if ($this->node() instanceof Node) {
            if (method_exists($this->node(), $name)) {
                $this->node()->{$name}($args);
            }
        }
    }

    /**----------------------------------------------------------------------------------
    * Model of nodes
    * ----------------------------------------------------------------------------------
    *
    *
    * @return String Girove\Tree\Models\Node::class
    * ----------------------------------------------------------------------------------*/
    public function model()
    {
        return $this->tree->nodeModel();
    }

    /**
     * Make query start point to databse table [ model -> Nodes ]
     *
     * starts with select from `nodes` where tree_id = {current tree id}
     *
     * @return Illuminate\Database\Eloquent\QueryBuilder
     */
    public function query()
    {
        return $this->tree->nodesQuery();
    }

    /**
     * Get the node that the Pointer indicates to
     *
     * @return Girover\Tree\Models\Node
     */
    public function node($node = null)
    {
        return $this->node;
    }

    /**
     * Get the location of the node that the pointer indicates to.
     *
     * @return String
     */
    public function location()
    {
        return $this->node ? $this->node->location : null;
    }

    /**
     * Check if the given location found in this tree
     *
     * @param String location
     * @return Girover\Tree\Models\Node | null
     */
    public function find($location)
    {
        return  $this->query()
                     ->where('location', $location)
                     ->first();
    }

    /**
     * Move the Pointer to the given location
     *
     * @param String location
     * @return Girover\Tree\Pointer $this
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function to($location)
    {
        Location::validate($location);

        $node = $this->find($location);

        if ($node === null) {
            throw new TreeException("Error: Node with location '".$location."' Not Found in The tree: ".$this->tree->properties()->name, 1);
        }

        $this->node = $node;

        return $this;
    }

    /**
     * Move the Pointer to the given location or node, but not inside database table.
     * It means that no loading data from database is happed.
     * Only assign property $node to an allready loaded node
     *
     * @param Girover\Tree\Models\Node $node
     * @return Girover\Tree\Pointer $this
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function silentlyTo($node)
    {
        if (! is_a($node, $this->model())) {
            throw new TreeException("Error: Given node is not instance of: ".$this->model(), 1);
        }
        $this->node = $node;

        return $this;
    }

    /**
     * Move the Pointer to the Root location of this tree
     *
     * @return Girover\Tree\Pointer $this
     */
    public function toRoot()
    {
        return $this->to(Location::firstPossibleSegment());
    }

    /**
     * Move the Pointer to the given ancestor.
     *
     * @param int $ancestor
     * @return Girover\Tree\Pointer $this
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function toAncestor($ancestor)
    {
        $ancestor_location = Location::ancestor($this->location(), $ancestor);

        if ($ancestor_location) {
            return $this->to($ancestor_location);
        }

        throw new TreeException("Error: The node with location `".$this->location().'` has no ancestor number '.$ancestor, 1);
    }

    /**
     * Move the Pointer back two steps to indicate to its own grandfather.
     *
     * @return Girover\Tree\Pointer $this
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function toGrandfather()
    {
        $grandfather_location = Location::grandfather($this->location());

        if ($grandfather_location) {
            return $this->to($grandfather_location);
        }

        throw new TreeException("Error: The node with location `".$this->location().'` has no grandfather', 1);
    }

    /**
     * Move the Pointer back one step to indicate to its own father.
     *
     * @return Girover\Tree\Pointer $this
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function toFather()
    {
        if ($this->indicatesToRoot()) {
            throw new TreeException("Error: Root node has no father to move the pointer to.", 1);
        }

        return $this->to($this->fatherLocation());
    }

    /**
     * Run the Pointer to indicate to its own last child.
     *
     * @return Girover\Tree\Pointer $this
     */
    public function toFirstChild()
    {
        if (($first_child = $this->firstChild()) === null) {
            throw new TreeException("Error: Node has no children to move to the first one.", 1);
        }

        return $this->silentlyTo($first_child);
    }

    /**
     * Run the Pointer to indicate to last child of the current node.
     *
     * @return Girover\Tree\Pointer $this
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function toLastChild()
    {
        if (($last_child = $this->node()->lastChild()) === null) {
            throw new TreeException("Error: Node has no children to move to the last.", 1);
        }

        return $this->silentlyTo($last_child);
    }

    /**
     * Determine if the node that the pointer is indicating to is the Root node
     *
     * @return bool
     */
    public function indicatesToRoot()
    {
        if (strpos($this->location(), Location::SEPARATOR) === false) {
            return true;
        }

        return false;
    }

    /**
     * Determine if pointer is indicating to NULL.
     *
     * @return bool
     */
    public function indicatesToNull()
    {
        return $this->node === null ? true : false;
    }

    /**
     * To get all nodes from the pointer including the node that pointer indicates to
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function all()
    {
        return $this->tree->allFromPointer();
    }

    /**
     * Get the node that the pointer is indicating to if no location is given.
     * Or go to the given location and return the node
     *
     * @return Girover\Tree\Models\Node
     */
    public function get($location = null)
    {
        return is_null($location)
              ? $this->node
              : $this->to($location)->node;
    }

    /**----------------------------------------------------------------------------------
     * Get the node that the pointer is indicating to if no location is given.
     * Or go to the given location and return the node
     * ----------------------------------------------------------------------------------
     *
     * @return App\Node
     * ----------------------------------------------------------------------------------*/
    // public function wives()
    // {
    //     return $this->node->wives;
    // }

    /**
     * Get the logest location in the tree that starts from the pointer
     *
     * @return String | NULL
     */
    public function longestLocation()
    {
        return $this->query()
                    ->where('location', 'like', $this->location().Location::SEPARATOR.'%')
                    ->orderByRaw('LENGTH(location) DESC')
                    ->orderBy('location', 'desc')
                    ->first();
    }

    /**
     * Get the location of the father of the current node
     *
     * separate location of current node by last "."
     *
     * @return string | Null
     */
    public function fatherLocation()
    {
        if ($this->indicatesToRoot() || $this->node() === null) {
            return null;
        }
        // return father location of node that pointer is indicating to
        return Location::father($this->location());
    }

    /**
     * Get the grandfather node of the node that the Pointer indicates to
     *
     * @return Girover\Tree\Models\Node | Null
     */
    public function grandfather()
    {
        return $this->node()->grandfather();
    }

    /**
     * Get the father node of the node that the Pointer indicates to
     *
     * separate location of current node by last "."
     *
     * @return Girover\Tree\Models\Node | Null
     */
    public function father()
    {
        return $this->node()->father();
    }

    /**
     *  Get Uncles of current node
     *
     * @return Girover\Tree\Database\Eloquent\TreeCollection
     */
    public function uncles()
    {
        return $this->node()->uncles();
    }

    /**
     * Get all aunts of this node
     *
     * @return Girover\Tree\Database\Eloquent\TreeCollection
     */
    public function aunts()
    {
        return $this->node()->aunts();
    }

    /**
     * Get the last segment of node location: section that is after the last separator
     *
     * @return string
     */
    public function self()
    {
        if ($this->indicatesToRoot()) {
            return $this->node->location;
        }

        return substr($this->location(), strrpos($this->location(), Location::SEPARATOR) + 1);
    }

    /**
     * Convert the current location to REGEXP pattern
     * If the given parameter is true so wrap the pattern with begin and end flags
     *
     * @param Boolen to cover the pattern or not
     * example aaa.bbb = aaa\.bbb
     * if $covered==true aaa.bbb = (^aaa\.bbb$)
     * @return REGEXP pattern
     */
    public function getAsRegPattern($covered = false)
    {
        $location_pattern = str_replace(Location::SEPARATOR, '\\'.Location::SEPARATOR, $this->location());

        return $covered ? '(^'.$location_pattern.'$)' : $location_pattern;
    }

    /**
     * Get All siblings of node that the Pointer is indicating to
     *
     * @return Girover\Tree\Database\Eloquent\TreeCollection
     */
    public function siblings()
    {
        return  $this->node()->siblings();
    }

    /**
     * Get the first sibling of node that the Pointer is indicating to
     *
     * @return Girover\Tree\Models\Node
     */
    public function firstSibling()
    {
        return  $this->node()->firstSibling();
    }

    /**
     * Get last sibling of node that the Pointer is indicating to
     *
     * @return Girover\Tree\Models\Node
     */
    public function lastSibling()
    {
        return  $this->node()->lastSibling();
    }

    /**
     * Get next sibling of node that the Pointer is indicating to
     *
     * @return Girover\Tree\Models\Node
     */
    public function nextSibling()
    {
        return  $this->node()->nextSibling();
    }

    /**
     * Get previous sibling of node that the Pointer is indicating to
     *
     * @return Girover\Tree\Models\Node
     */
    public function prevSibling()
    {
        return  $this->node()->prevSibling();
    }

    /**
     *  Get children of the node that the pointer indicates to
     *
     * @param string|null $gender
     * @return Girover\Tree\Database\Eloquent\TreeCollection
     */
    public function children($gender = null)
    {
        return  $this->node()->children($gender);
    }

    /**
     *  Get The First child of the node that the pointer indicates to
     *
     * @return Girover\Tree\Models\Node
     */
    public function firstChild()
    {
        return  $this->node()->firstChild();
    }

    /**
     *  Get The last child of the node that the pointer indicates to
     *
     * @return Girover\Tree\Models\Node
     */
    public function lastChild()
    {
        return  $this->node()->lastChild();
    }

    /**
     * Determine if the node, that the Pointer indicates to, has children
     *
     * @return bool
     */
    public function hasChildren()
    {
        return $this->node()->hasChildren();
    }

    /**
     * Generate new child location for the current location if no param is given
     * Generate new child location for the given location as parameter
     *
     * @param String $location
     * @return string
     */
    // public function newChildLocation(){

    //     $last_child = $this->lastChild();
    //     if($last_child == null){
    //         return $this->location().Location::SEPARATOR.Location::firstPossibleSegment();
    //     }
    //     return Location::nextSibling($last_child->location);
    // }

    /**
     * Generate new child location for the current location if no param is given
     * Generate new child location for the given location as parameter
     *
     * @param String $location
     * @return string
     */
    public function newSiblingLocation()
    {
        $last_sibling = $this->lastSibling();
        if ($last_sibling == null) {
            return $this->location().Location::SEPARATOR.Location::firstPossibleSegment();
        }

        return Location::nextSibling($last_sibling->location);
    }

    /**
     * Getting all ancestors nodes from the location where the Pointer indicates to
     *
     *
     * @return Girover\Tree\Database\Eloquent\TreeCollection
     */
    public function ancestors()
    {
        return $this->node()->ancestors();
    }

    /**
     * How many generation does this tree has {The sub tree from the pointer}
     * Depends on the counting separator in the location
     *
     * @return Int | NULL
     */
    public function countGenerationsFromHere()
    {
        $current = Location::generation($this->location());
        $longest = Location::generation($this->longestLocation());

        return $longest - $current + 1;
    }

    /**
     * Indicate which generation the node that the pointer indicates to, has.
     *
     * @return Int | NULL
     */
    public function generationNumber()
    {
        return Location::generation($this->location());
    }

    /**
     * How many generation does this tree has {The sub tree from the pointer including pointer`s node}
     *
     * @return Int | NULL
     */
    public function totalGenerations()
    {
        // generation of longest location in tree
        $total_generations = $this->tree->totalGenerations();
        // generation of the pointer location
        $sub_tree_generations = $this->generationNumber();
        // find the deference between the both. Add 1 to include the pointer`s node
        return $total_generations - $sub_tree_generations + 1;
    }

    public function getGenerations($generations_count)
    {
        return $generations_count;
    }

    /**
     * to make the tree instanse loads its own nodes from Pointer's node location
     *
     * @param Integer|null $generations
     * @return App\MHF\tree\Pointer
     */
    public function load($generations = null)
    {
        $this->tree->load($generations);

        return $this;
    }

    /**
     * to make the tree instanse draw its own nodes from Pointer's node location
     *
     *
     * @return String html text
     */
    public function draw()
    {
        return $this->tree->draw();
    }

    /**
     * ---------------------------------------------
     * Draw the current tree and return it as HTML.
     * ---------------------------------------------
     * @return String
     */
    public function toHtml()
    {
        return $this->tree->draw();
    }

    /**
     * cut current node with its children
     * and move it to be child of the given location
     *
     * @param String $location: location to move node to it
     * @return Girover\Tree\Pointer
     */
    public function makeSonTo($location)
    {
        return $this->node()->makeSonTo($location);
    }

    /**
     * Delete the node, that the pointer indicates to, with its children.
     *
     * @return Integre
     */
    // public function deleteWithChildren()
    // {
    //     return $this->query()->where('location', 'like', $this->location().'%')->delete();
    // }

    /**
     * Delete the node, that the pointer indicates to, and move
     * all its children to be children for the its father.
     *
     * @return Integre
     */
    public function deleteAndShiftChildren()
    {
    }
}
