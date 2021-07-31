<?php
/**----------------------------------------------------------------------------------
| Pointer of Tree
| Aouth: Majed Girover
|        girover.mhf@gmail.com
| ---------------------------------------------------------------------------------
| when creating a Tree instance, a pointer object creates and indicates
| to the root node in the Tree.
| the pointer only indicates to nodes in the database table and not to the loaded nodes 
| inside the tree property $nodes.
| so every time Poiner moves through the tree it make request to databse table and return
| one record of tree nodes.
| therefor be awared when you draw the tree to show in the browser, because 
| if the tree nodes are allready loaded and you move the pointer throgh the tree
| and you want to draw them again, so it will draw the old nodes those were loadded before.
| And to draw the tree from the pointer node, you have to call `load` method to change
| the allready loaded nodes. 
| 
| */
namespace Girover\Tree;

use Girover\Tree\Exceptions\TreeException;
use BadMethodCallException;
use Illuminate\Support\Facades\DB;

class Pointer
{
    /**----------------------------------------------------------------------------------
     * Instance of App\MHF\tree\Tree
     * ---------------------------------------------------------------------------------
     * when creating a pointer it take an instance of Tree as parameter
     */
    protected $tree;


    /**----------------------------------------------------------------------------------
     * This variable contain the value of node location:
     * ---------------------------------------------------------------------------------
     *  Default value is the root location of the tree of of this pointer
     * it get value by construct when creating Location object
     *----------------------------------------------------------------------------------*/
    // protected $node_location;

    /**----------------------------------------------------------------------------------
     * This variable contains all data of the node that this poiter point to:
     * ---------------------------------------------------------------------------------
     *  it can be [Object, JSON, Model, Database Record ...]
     *  depending on which tree is using this node
     *----------------------------------------------------------------------------------*/
    protected $node;

    /**----------------------------------------------------------------------------------
     * Methods that are not defined. They depend on a method called generations($generations)
     * ---------------------------------------------------------------------------------
     *  it can be [Object, JSON, Model, Database Record ...]
     *  depending on which tree is using this node
     *----------------------------------------------------------------------------------*/
    protected $methods = [
        'generations'=>[
            'getOneGeneration',
            'getTwoGenerations',
            'getTreeGenerations',
            'getFourGenerations',
            'getFifeGenerations',
        ]
    ];


    /**
     * Initialize the Pointer and indicate it to the root node 
     * of the given tree
     * 
     * @param App\MHF\tree\Tree $tree
     * @param boolean $free_pointer if false load root node from database 
     * otherwise don't load anything from databse
     * @return void
     */
    public function __construct(Tree $tree, bool $free_pointer = false) {
        $this->tree = $tree; // This has to be the first init in Poiter, because next line depend on it
        // Determine if the pointer is free. 
        // free pointer means no loading from database, when intializing.
        if(!$free_pointer){
            $this->node = $this->query()->where('location', 'REGEXP', Location::rootREGEXP())->first();
        }
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
        if(!in_array($name, $this->methods['generations'])){
            throw new BadMethodCallException("Trying to call undefined metode $name ", 1);
        }
        if(in_array($name, $this->methods['generations'])){
            $this->getGenerations(1);
        }
        return $this->anyMethod(...$args);
    }

    /**----------------------------------------------------------------------------------
    * Model of nodes 
    * ----------------------------------------------------------------------------------
    * 
    * 
    * @return String App\Node::class
    * ----------------------------------------------------------------------------------*/
    public function model()
    {
        return $this->tree->nodeModel();
    }

    /**----------------------------------------------------------------------------------
    * Make query start point to databse table model Nodes
    * ----------------------------------------------------------------------------------
    * starts with select from `nodes` where tree_id = {current tree id}
    * 
    * @return Illuminate\Database\Eloquent\QueryBuilder
    * ----------------------------------------------------------------------------------*/
    public function query()
    {
        return $this->tree->nodesQuery();
    }

    /**----------------------------------------------------------------------------------
     * Get the node 
     * ----------------------------------------------------------------------------------
     * 
     * @return App\Node
     * ----------------------------------------------------------------------------------*/
    public function node($node = null)
    {
        return $this->node;
    }

    /**----------------------------------------------------------------------------------
    * Get the location of this node that the pointer poit to.
    * ----------------------------------------------------------------------------------
    * @return String
    * ----------------------------------------------------------------------------------*/
    public function location()
    {
        return $this->node ? $this->node->location: null;
    }
    
    /**
    *------------------------------------------------
    * Check if the given location found in this tree
    *------------------------------------------------
    *
    * @param String location
    * @return App\Node|null 
    */
    public function find($location)
    {
        return  $this->query()
                     ->where('location', $location)
                     ->first();
    }
    /**
     *----------------------------------------------------------------------------------
    * Run the Pointer to the given location or node
    * ----------------------------------------------------------------------------------
    * @param String location
    * @return App\MHF\tree\Pointer $this
    * ----------------------------------------------------------------------------------*/
    public function to($location)
    {
        $node =  $this->find($location);

        if($node === null){
            throw new TreeException("Error: Node with location '".$location."' Not Found in The tree: ".$this->tree->properties()->name, 1);
        }

        $this->node = $node;

        return $this;
    }
    /**
     *----------------------------------------------------------------------------------
    * Run the Pointer to the given location or node, but not inside database table.
    * It means that no loading data from database is happed.
    * Only assign property $node to an allready loaded node
    * ----------------------------------------------------------------------------------
    * @param App\Models\Node $node
    * @return App\MHF\tree\Pointer $this
    * ----------------------------------------------------------------------------------*/
    public function silentlyTo($node)
    {
        if(!is_a($node, $this->model())){
            throw new TreeException("Error: Given node is not instance of: ".$this->model(), 1);
        }
        $this->node = $node;

        return $this;
    }

    /**
     * ----------------------------------------------------------------------------------
     * Run the Pointer to the Root location of this tree
     * ----------------------------------------------------------------------------------
     * 
     * @return App\MHF\tree\Pointer $this
     * ----------------------------------------------------------------------------------*/
    public function toRoot()
    {
        return $this->to(Location::firstPossibleSegment());
    }
    
    /**
    * ----------------------------------------------------------------------------------
    * Run the Pointer to the given ancestor.
    * ----------------------------------------------------------------------------------
    * 
    * @return App\MHF\tree\Pointer $this
    * ----------------------------------------------------------------------------------*/
    public function toAncestor($ancestor)
    {
        if($this->ancestorLocation($ancestor))
            return $this->to($this->ancestorLocation($ancestor));

        throw new TreeException("Error: The node with location `".$this->location().'` has no ancestor number '.$ancestor, 1);
        
    }

    /**
    * ----------------------------------------------------------------------------------
    * Run the Pointer back one step to indicate to its own father.
    * ----------------------------------------------------------------------------------
    * 
    * @return App\MHF\tree\Pointer $this
    * ----------------------------------------------------------------------------------*/
    public function toGrandfather()
    {
        if($this->grandfatherLocation())
            return $this->to($this->grandfatherLocation());

        throw new TreeException("Error: The node with location `".$this->location().'` has no grandfather', 1);
        
    }
    /**
     * ----------------------------------------------------------------------------------
     * Run the Pointer back one step to indicate to its own father.
     * ----------------------------------------------------------------------------------
     * 
     * @return App\MHF\tree\Pointer $this
     * ----------------------------------------------------------------------------------*/
    public function toFather()
    {
        if($this->indicatesToRoot()){
            throw new TreeException("Error: Root node has no father to move the pointer to.", 1);            
        }
        
        return $this->to($this->fatherLocation());
    }
    /**
     * ----------------------------------------------------------------------------------
     * Run the Pointer to indicate to its own last child.
     * ----------------------------------------------------------------------------------
     * 
     * @return App\MHF\tree\Pointer $this
     * ----------------------------------------------------------------------------------*/
    public function toFirstChild()
    {
        if(($first_child = $this->firstChild())=== null){
            throw new TreeException("Error: Node has no children to move to the first one.", 1);            
        }

        return $this->silentlyTo($first_child);
    }

    /**
     * ----------------------------------------------------------------------------------
     * Run the Pointer to indicate to its own last child.
     * ----------------------------------------------------------------------------------
     * 
     * @return App\MHF\tree\Pointer $this
     * ----------------------------------------------------------------------------------*/
    public function toLastChild()
    {
        if(($last_child = $this->lastChild())=== null){
            throw new TreeException("Error: Node has no children to move to the last.", 1);            
        }

        return $this->silentlyTo($last_child);
    }

    /**----------------------------------------------------------------------------------
     * Determine if the node that the pinter is poiting to is the Root node
     * ----------------------------------------------------------------------------------
     *
     * @return Boolean
     * ----------------------------------------------------------------------------------*/
    public function indicatesToRoot()
    {
        if(strpos($this->node->location, Location::SEPARATOR) === false){
            return true;
        }
        return false;
    }

    /**----------------------------------------------------------------------------------
     * Determine if pointer is indicating to NULL. is the tree is empety
     * ----------------------------------------------------------------------------------
     * 
     *
     * @return Boolean
     * ----------------------------------------------------------------------------------*/
    public function indicatesToNull()
    {
        return $this->node === null ? true : false;
    }

    /**----------------------------------------------------------------------------------
     * To get all nodes from the pointer including the node that pointer indicates to
     * ----------------------------------------------------------------------------------
     * 
     * @return Illuminate\Database\Eloquent\Collection
     * ----------------------------------------------------------------------------------*/
    public function all()
    {
        return $this->tree->allFromPointer();
    }

    /**----------------------------------------------------------------------------------
     * Get the node that the pointer is indicating to if no location is given.
     * Or go to the given location and return the node
     * ----------------------------------------------------------------------------------
     * 
     * @return App\Node
     * ----------------------------------------------------------------------------------*/
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
    public function wives()
    {
        return $this->node->wives;
    }

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

    /**----------------------------------------------------------------------------------
     * Get the location of the father of the current node
     * ----------------------------------------------------------------------------------
     * separate location of current node by last "."
     *
     * @return string | Null
     * ----------------------------------------------------------------------------------*/
    public function fatherLocation()
    {
        if($this->indicatesToRoot() || $this->node()===null){
            return Null;
        }
        // return father location of node that pointer is indicating to
        return Location::father($this->location());
    }
    /**----------------------------------------------------------------------------------
     * Get the location of the Grandfather of the current node
     * ----------------------------------------------------------------------------------
     * 
     *
     * @return string | Null
     * ----------------------------------------------------------------------------------*/
    public function grandfatherLocation()
    {
        return Location::grandfather($this->location());
    }
    /**----------------------------------------------------------------------------------
     * Get the location of the given ancestor of the current node
     * ---------------------------------------------------------------------------------- 
     *
     * @param ancestor number like: father=1, grandfather=2, grandgrandfather=3 etc...
     * @return string | Null
     * ----------------------------------------------------------------------------------*/
    public function ancestorLocation($ancestor)
    {
        return Location::ancestor($this->location(), $ancestor);
    }
    
    /**----------------------------------------------------------------------------------
     * Get the grandfather node of the node that the Pointer indicates to
     * ----------------------------------------------------------------------------------
     * 
     *
     * @return App\Node | Null
     * ----------------------------------------------------------------------------------*/
    public function grandfather()
    {
        if(($grandfather_location = $this->grandfatherLocation()) === null){
            return Null;
        }
        return $this->find($grandfather_location);
    }
    
    /**----------------------------------------------------------------------------------
     * Get the father node of the node that the Pointer indicates to
     * ----------------------------------------------------------------------------------
     * separate location of current node by last "."
     *
     * @return App\Node | Null
     * ----------------------------------------------------------------------------------*/
    public function father()
    {
        if(($father_location = $this->fatherLocation()) === null){
            return Null;
        }
        return $this->find($father_location);
    }

    /**
     *  Get Uncles of current node
     *
     * @return Illuminate\Support\Collection
     */
    public function uncles(){
        $father_location      = $this->fatherLocation();
        $regex_location       = Location::withSiblingsREGEXP($father_location);
        return  $this->query()
                    ->where('location', '!=', $father_location)
                    ->where('location', 'REGEXP', $regex_location)
                    ->orderBy('location')
                    ->get();
    }
    /**----------------------------------------------------------------------------------
     * Get the last segment of node location: section that is after the last separator
     * ----------------------------------------------------------------------------------
     *
     * @return string
     * ----------------------------------------------------------------------------------*/
    public function self()
    {
        if($this->indicatesToRoot()){
            return $this->node->location;
        }
        return substr($this->location(), strrpos($this->location(), Location::SEPARATOR)+1);
    }
    /**
     * ----------------------------------------------------------
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
     * ----------------------------------------------------------------------------------
     * Get All siblings of node that the Pointer is indicating to
     * ----------------------------------------------------------------------------------
     *
     * @return Illuminate\Support\Collection
     * ----------------------------------------------------------------------------------*/
    public function getSiblings()
    {
        return  $this->query()
                     ->where('location', '!=', $this->location())
                     ->where('location', 'REGEXP', Location::withSiblingsREGEXP($this->location()))
                     ->orderBy('location')
                     ->get();
    }

    /**
     * ----------------------------------------------------------------------------------
     * Get the first sibling of node that the Pointer is indicating to
     * ----------------------------------------------------------------------------------
     *
     * @return App\Models\Node
     * ----------------------------------------------------------------------------------*/
    public function firstSibling()
    {
        return  $this->query()
                     ->where('location', '!=', $this->location())
                     ->where('location', 'REGEXP', Location::withSiblingsREGEXP($this->location()))
                     ->orderBy('location')
                     ->first();
    }

    /**
     * ----------------------------------------------------------------------------------
     * Get last sibling of node that the Pointer is indicating to
     * ----------------------------------------------------------------------------------
     *
     * @return App\Models\Node
     * ----------------------------------------------------------------------------------*/
    public function lastSibling()
    {
        return  $this->query()
                     ->where('location', '!=', $this->location())
                     ->where('location', 'REGEXP', Location::withSiblingsREGEXP($this->location()))
                     ->orderBy('location', 'desc')
                     ->first();
    }

    /**
     * ----------------------------------------------------------------------------------
     * Get next sibling of node that the Pointer is indicating to
     * ----------------------------------------------------------------------------------
     *
     * @return App\Models\Node
     * ----------------------------------------------------------------------------------*/
    public function nextSibling()
    {
        return  $this->query()
                     ->where('location', '!=', $this->location())
                     ->where('location', 'REGEXP', Location::withSiblingsREGEXP($this->location()))
                     ->where('location', '>', $this->location())
                     ->orderBy('location')
                     ->first();
    }

    /**
     * ----------------------------------------------------------------------------------
     * Get previous sibling of node that the Pointer is indicating to
     * ----------------------------------------------------------------------------------
     *
     * @return App\Models\Node
     * ----------------------------------------------------------------------------------*/
    public function prevSibling()
    {
        return  $this->query()
                     ->where('location', '!=', $this->location())
                     ->where('location', 'REGEXP', Location::withSiblingsREGEXP($this->location()))
                     ->where('location', '<', $this->location())
                     ->orderBy('location', 'desc')
                     ->first();
    }


    /**
     *  Get children of the node that the pointer indicates to
     *
     * @param Boolean $trashed
     * @return Illuminate\Support\Collection
     */
    public function getChildren(){

        return  $this->query()
                     ->where('location', 'REGEXP', Location::childrenREGEXP($this->location()))
                     ->orderBy('location')
                     ->get();
    }

    /**
     *  Get The First child of the node that the pointer indicates to
     *
     * 
     * @return App\Node
     */
    public function firstChild()
    {
        return  $this->query()
                     ->where('location', 'REGEXP', Location::childrenREGEXP($this->location()))
                     ->orderBy('location', 'ASC')
                     ->first();
    }

    /**
     *  Get The last child of the node that the pointer indicates to
     *
     * 
     * @return App\Models\Node
     */
    public function lastChild()
    {
        return  $this->query()
                     ->where('location', 'REGEXP', Location::childrenREGEXP($this->location()))
                     ->orderBy('location', 'DESC')
                     ->first();
    }

    /**
     * Determine if the node, that the Pointer indicates to, has children
     * 
     * @return Boolean
     */
    function hasChildren()
    {
        return $this->query()->where('location', 'like', $this->location().'%')->count()
                ? true : false;
    }

    /**
     * Generate new child location for the current location if no param is given
     * Generate new child location for the given location as parameter
     * 
     * @param String $location
     * @return string
     */
    public function newChildLocation(){

        $last_child = $this->lastChild();
        if($last_child == null){
            return $this->location().Location::SEPARATOR.Location::firstPossibleSegment();
        }
        return Location::nextSibling($last_child->location);
    }

    /**
     * Generate new child location for the current location if no param is given
     * Generate new child location for the given location as parameter
     * 
     * @param String $location
     * @return string
     */
    public function newSiblingLocation(){

        $last_sibling = $this->lastSibling();
        if($last_sibling == null){
            return $this->location().Location::SEPARATOR.Location::firstPossibleSegment();
        }
        return Location::nextSibling($last_sibling->location);
    }

    /**
     * Getting all ancestors nodes from the location where the Pointer indicates to
     *
     * 
     * @return Illuminate\Support\Collection
     */
    public function ancestors()
    {
        return $this->query()
                    ->where('location', '!=', $this->location())
                    ->whereIn('location', Location::allLocationsToRoot($this->location()))->get();
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
        $sub_tree_generations =$this->generationNumber();
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
    public function toHtml(){
        return $this->tree->draw();
    }

    /**
     * -----------------------------------------------
     * cut current node with its children
     * and move it to be child of the given location
     *
     * @param String $location: location to move node to it
     * @return App\MHF\Tree\Tree
     */
    public function makeSonTo($location){
        // check if the location is exists and load it if exsits
        if(!($node = $this->find($location))){
            throw new TreeException("Error: The location `".$location."` not found in this tree.", 1);
            
        }
        // make new child location
        $current_location = $this->location();
        // move the pointer to the loaded node
        $new_location = $this->silentlyTo($node)->newChildLocation();
        $table = $this->tree->nodesTable();
        $statement =
            "UPDATE `".$table."`
            SET `location` = CONCAT('".$new_location."' , SUBSTRING(location FROM ".(strlen($current_location) + 1)."))
            WHERE `tree_id` = ".$this->tree->properties()->id."
            AND   `location` like '".$current_location."%'";
        
        DB::update($statement);

        return $this;
    }


    /**
     * Delete the node, that the pointer indicates to, with its children.
     * 
     * @return Integre 
     */
    public function deleteWithChildren()
    {
        return $this->query()->where('location', 'like', $this->location().'%')->delete();
    }

    /**
     * Delete the node, that the pointer indicates to, and move
     * all its children to be children for the its father.
     * 
     * @return Integre 
     */
    public function deleteButShiftChildren()
    {
        
    }

}
