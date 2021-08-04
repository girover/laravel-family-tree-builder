<?php

/**
* tree is a class to make family tree
* the tree consists of nodes which are connected to make a tree.
* and the nodes has defferent locations
*
* Location is a set of segments which are
*
* The tree has Pointer that indicates to nodes in database. This Pointer does not indicates
* to loaded nodes in the tree propery `$nodes`, so when you load tree nodes
* and after that you move the Pointer to another location and try to draw the tree,
* it will draw the loaded nodes. Therefor to draw the tree from location that the pointer
* indicates to, you have to load the tree nodes again.
*
* @author  Majed Girover girover.mhf@gmail.com
* @license MIT
* */

namespace Girover\Tree;

use BadMethodCallException;
use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Exceptions\TreeNotActiveException;
use Girover\Tree\Models\Node;
use Girover\Tree\Models\Tree;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class FamilyTree
{
    /**
    * The Tree model to bulid the familyTree from
    *
    * @var Girover\Tree\Models\Tree
    */
    protected $tree;



    /**
    * The pointer that runs between the nodes of the this tree
    *
    * @var  App\MHF\tree\Pointer
    */
    public $pointer;



    /*
    |--------------------------------------------------------------------------
    | Node
    |--------------------------------------------------------------------------
    |
    | App\Node instance : current node with all data such as: location, name, etc.
    |
    */
    // public $node = null;  // {stdClass} current node;


    public $treeExists = false;


    protected $nodes;

    public $nodesCount = 1;

    public $ancestors = []; // array holding ancestries of location




    public $tree_photos_path = 'images/photos/';



    /**----------------------------------------------------------------------------------
     * Methods that are not defined and will be called by magic method __call()
     * ---------------------------------------------------------------------------------
     *
     *----------------------------------------------------------------------------------*/
    protected $methods = [
        'draw' => [ // Indicates how many generations to draw from the pointer
            'drawEntire' => null,
            'drawOneGeneration' => 1,
            'drawTwoGenerations' => 2,
            'drawThreeGenerations' => 3,
            'drawFourGenerations' => 4,
            'drawFifeGenerations' => 5,
            'drawSixGenerations' => 6,
            'drawSevenGenerations' => 7,
            'drawEightGenerations' => 8,
            'drawNineGenerations' => 9,
            'drawTenGenerations' => 10,
        ],
    ];


    /**
     * Initializing the object instance of class Tree
     *
     * @param int|null tree_id
     *
     * @return void
     */
    // function __construct($tree_id){
    //     if(is_null($tree_id)){
    //         return;
    //     }
    //     $this->id    = $tree_id;

    //     $this->initConfigs();
    //     $this->configs = config('tree');

    //     // Load properties of this tree
    //     $this->makeProperties();

    //     // Make tree pointer and set it on the root node
    //     $this->makePointer();
    // }
    public function __construct()
    {

        // // Make tree pointer and set it on the root node
    }

    /**
     * Make family tree from Tree model or Node model
     */
    public function make($tree)
    {
        $node = null;

        if ($tree instanceof Tree) {
            $this->tree = $tree;
            // $this->node = $tree->root()->first();
            $node = $tree->root()->first();
        }

        if ($tree instanceof Node) {
            $this->tree = config('tree.tree_model')::find($tree->tree_id);
            // $this->node = $tree;
            $node = $tree;
        }

        $this->makePointer($node);

        return $this;
    }

    /**
    * Create pointer for this tree and put it on root node of this tree
    * and then pass the tree instance to the pointer
    *
    * if the tree is not active or blocked, then throw an TreeNotActiveException
    * @return void
    */
    public function makeProperties()
    {
        if (! $this->properties = config('tree.tree_model')::find($this->tree->id)) {
            throw new TreeException("Tree with id : ".$this->tree->id.' not found', 1);
        }

        if (! $this->isActive()) {
            throw new TreeNotActiveException("Error: The tree with name `".$this->properties->name."` is not active", 1);
        }
    }

    /**
    *    Initializing the tree Pointer
    *
    * Create pointer for this tree and put it on root node of this tree
    * and then pass the tree instance to the pointer
    *
    * @param Girover\Tree\Models\Node $indicated_node
    * @return void
    */
    public function makePointer(Node $indicated_node = null)
    {
        $this->pointer = new Pointer($this, $indicated_node);
    }

    /**
    * Get this tree properties like: id, name, base location, etc....
    *
    * @return App\Node
    */
    public function properties()
    {
        return $this->tree;
    }

    public function pointer()
    {
        if (null === $this->pointer) {
            throw new TreeException('This tree has no pointer, or the tree is empty');
        }

        return $this->pointer;
    }

    /**
     * Check if this tree is active
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->tree->active;
    }

    /**
    * Tree Model
    *
    * Get The Tree model
    *
    * @return string calss name of tree model
    */
    public function treeModel()
    {
        return config('tree.tree_model');
    }

    /**
    * Node Model
    *
    * Get The node model
    *
    * @return string calss name of node model
    */
    public function nodeModel()
    {
        return config('tree.node_model');
    }

    /**
    * Database nodes Table
    *
    * Get The database table name that the nodes are stored in
    *
    * @return string database table name of nodes
    */
    public function nodesTable()
    {
        return config('tree.nodes_table.name');
    }

    /**
     * To set nodes smothly without querying database.
     *
     * @param Illuminate\Support\Collection | Girover\Tree\TreeCollection
     * @return void
     */
    public function setNodesSilently($collection)
    {
        $this->nodes = $collection;
    }

    /**
     * Determine if there are nodes in this tree
     *
     * @return bool
     */
    public function isEmptyTree()
    {
        return ($this->nodesQuery()->count() == 0) ? true : false;
    }

    /**
     * Determine if the nodes of this tree are allready loaded.
     *
     * @return bool
     */
    public function isLoaded()
    {
        return $this->nodes !== null ? true : false;
    }

    /**
    * Load specific number of generation of this tree
    *
    * @param int number of generations to load.
    * @return Illuminate\Database\Eloquent\Collection
    */
    protected function loadGenerations($number_of_generations)
    {
        return  $this->nodes = $this->nodesQuery()
                                    ->orderBy('location')
                                    ->where('location', 'REGEXP', Location::multiGenerationsREGEXP($this->pointer->location(), $number_of_generations))
                                    ->get();
    }

    /**
    * Make database query for The Tree model to start other queries from this point
    *
    * @return Illuminate\Databse\Eloquent\Builder
    */
    public function treeQuery()
    {
        return config('tree.tree_model')::where('id', $this->tree->id);
    }

    /**
    * Make database query for The Node model to start other queries from this point
    *
    * @return App\Girover\Database\Eloquent\TreeEloquentBuilder
    */
    public function nodesQuery()
    {
        return config('tree.node_model')::where('tree_id', $this->tree->id);
    }

    /**
    * Validating the node location
    *
    * determines if the node location is valide and has the correct style
    * of three chars or digits followed by separator and so on.
    *
    * @param string $location ocation to validate
    * @return bool | TreeException
    */
    public function validateLocation($location)
    {
        return Location::validate($location);
    }

    /**
     * Get all Nodes in this Tree from database Table 'nodes'
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function all()
    {
        return $this->nodesQuery()
                           ->orderBy('location')
                           ->get();
    }

    /**
     * Get all Nodes in this Tree from database Table 'nodes'
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function allFromPointer()
    {
        return $this->nodesQuery()
                    ->where('location', 'like', $this->pointer->location().'%')
                    ->orderBy('location')
                    ->get();
    }

    /**
     * Get all loaded nodes in this tree
     *
     * @param App\Girover\Database\Eloquent\TreeCollection
     */
    public function nodes()
    {
        return $this->nodes;
    }

    /**
     * To count the tree nodes that are allready loaded
     *
     * @return integer;
     */
    protected function countLoadedNodes()
    {
        return $this->nodes->count();
    }

    /**
     * To count the tree nodes that are allready loaded
     *
     * @return integer;
     */
    protected function countDatabaseNodes()
    {
        return $this->nodesQuery()
                    ->where('location', 'like', $this->pointer->location().'%')
                    ->count();
    }

    /**
     * Count the nodes
     *
     * Count the number of nodes in the tree of current node
     * First check if there are nodes asigned to variable $node:
     * If there are no nodes then query the database
     * if there are nodes count them from $nodes
     *
     * @return int
     */
    public function count()
    {
        return $this->nodes === null
               ? $this->countDatabaseNodes()
               : $this->countLoadedNodes();
    }

    /**
    * Load tree nodes
    *
    * Get all Nodes of this tree from database
    * and set them in the variable $nodes
    *
    * @return App\Girover\Database\Eloquent\TreeCollection
    */
    public function load($number_of_generations = null)
    {
        if (! is_numeric($number_of_generations) && ! is_null($number_of_generations)) {
            throw new TreeException("Error: The given generation '".$number_of_generations."' is not a number", 1);
        }
        $this->nodes = $number_of_generations
                       ? $this->loadGenerations($number_of_generations)
                       : $this->allFromPointer();

        return $this;
    }

    /**
     * Load the tree nodes from preloaded collection
     * no need to querying database.
     *
     * @param Illuminate\Support\Collection | Girover\Tree\TreeCollection
     * @return Girover\Tree\Tree
     */
    public function silentLoad($collection)
    {
        $this->nodes = $collection;
        $this->pointer->silentlyTo($collection->first());

        return $this;
    }

    /**
     * To unload this tree.
     *
     * @return void
     */
    public function unload()
    {
        $this->nodes = null;
        $this->pointer->toRoot();
    }

    /**
     * Draw the current tree and return it as HTML.
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->draw();
    }

    /**
     * Draw the current tree and return it as HTML
     * If there are nodes but are not loaded yet, an exception will be thrown.
     * so it's better to load nodes using ->load() before calling this method.
     *
     * @return string
     */
    public function draw()
    {

        // Determine if nodes of this tree are loaded before starting to draw it.
        // if no nodes are loaded And there are nodes in database, so load them.
        if ($this->nodes == null) {
            if (! $this->isEmptyTree()) {
                $this->load();
            // throw new TreeException("Error: Load The tree '".$this->properties()->name."' has nodes but they are not loaded. \n You Can call the method load() before draw() method to load the tree nodes", 1);
            } else {
                return $this->emptyTree();
            }
        }

        if (($nodes_count = $this->count()) == 0) {
            return $this->emptyTree();
        }
        $fathers = [];
        $close_status = false;
        $is_node_father = false;
        $tree_html = ' ' ;

        //=============================
        // Start drawing the tree from the pointer
        $tree_html .= ' <div id="tree" class="tree"><ul>' ;
        //=============================
        for ($i = 0; $i < $nodes_count; $i++) {
            $node = $this->nodes[ $i ] ;
            $next_node = isset($this->nodes[$i + 1]) ? $this->nodes[ $i + 1 ] : null;
            $is_node_father = $this->areFatherAndSon($node, $next_node);

            if ($close_status) {
                $father_loop_count = count($fathers);
                for ($j = $father_loop_count ; $j > 0; $j--) {
                    $node_father = array_pop($fathers);
                    if ($this->areSiblings($node, $node_father)) {
                        $i--;
                        $close_status = false ;

                        break;
                    } else {
                        $tree_html .= ' </ul></li> ' ;
                    }
                }
                $close_status = false ;
            } else {
                $tree_html .= ' <li> '.$this->getNodeStyled($node, $is_node_father) ;
                if ($this->areFatherAndSon($node, $next_node)) {
                    $tree_html .= ' <ul> ' ;
                    $fathers[] = $node;
                } else {
                    if ($this->areSiblings($node, $next_node)) {
                        $tree_html .= ' </li> ' ;
                    } else {
                        $tree_html .= ' </li></ul></li> ' ;
                        $close_status = true;
                    }
                }
            }
        }
        $tree_html .= '</ul></div>';

        return $tree_html;
    }

    /**
     * Get Html for empty tree. when the user has no any tree in database
     *
     * @return string
     */
    public function emptyTree()
    {
        return '<div id="tree" class="tree"><ul>'
                . '<li>'
                . '<a class="node" data-id=""  data-location="0" data-tree="" data-id="" data-name="" data-geder="" data-role="empty">
                        <div class="empty-node">
                            <div class="node-img"><img src="'. asset('images/photos/icon_male.png').'"></div>
                            <div class="node-info">add new</div>
                        </div>
                    </a>'
                . '</li>'
            . '</ul></div>';
    }

    public function getRootLocation()
    {
        return Location::firstPossibleSegment();
    }

    /**
     * Move the pointer of the tree to a given location
     *
     * @param string $location
     * @return $this
     */
    public function goTo($location)
    {
        $this->pointer->to($location);

        return $this;
    }

    /**
     * Determine if the Tree has node with this given location
     *
     * @param string $location : location of node
     * @return int [1, 0]
     *
     */
    public function has($location)
    {
        return $this->nodesQuery()
                    ->where('location', $location)->count();
    }

    /**
     * Determine if the tow given nodes are father and son
     *
     * @param App\Models\Node $father
     * @param App\Models\Node $son
     * @return bool
     * ----------------------------------------------------------
     */
    public function areFatherAndSon($father, $son)
    {
        if ($father === null || $son === null) {
            return false;
        }

        return Location::areFatherAndSon($father->location, $son->location);
    }

    /**
     * Get the node that is saved in nodes array and eqcual to the given location as argument
     *
     * @param string $locaction
     * @return Node
     */
    public function getNode($locaction)
    {
        foreach ($this->nodes as $node) {
            if ($node->location == $locaction) {
                return $node;
            }
        }
    }

    /**
     * Get the father node of the given location
     *
     * @param string $location [ location of node to get father's location ]
     * @return string [ location of the father of given location]
     */
    public function fatherOf($location)
    {
        return $this->pointer->to($location)->father();
    }

    /**
     *---------------------------------------------------
     *      Get the Wives node of the given location
     *---------------------------------------------------
     * @param string $location
     * @return Illuminate\Support\Collection colection of wives
     */
    public function wivesOf($location)
    {
        return $this->pointer->to($location)->wives();
    }

    /**
     * Get the Mother depending on the given location
     *
     * @param string $location [ location of node to get father's location ]
     * @return Node
     */
    public function getMother($location)
    {
        return Wife::where('tree_id', '=', Session::get('tree_id'))->where('location', '=', $location)->get()->first();
    }

    /**
     * Get all ancestors nodes of the given location in this tree.
     *
     * @param String $location
     * @return Illuminate\Support\Collection
     */
    public function ancestorsOf($location)
    {
        return $this->pointer->to($location)->ancestors();
    }

    public function parseNumber($location)
    {
        return substr($location, strrpos($location, Location::SEPARATOR) + 1);
    }

    /**
     * Add new [ child | wife | father ] depending on given role to current tree under current node
     *
     * @param Request $request
     * @param String $role
     * @return Node
     */
    public function add(Request $request, $role = '')
    {
        $newNode = ($role === 'wife') ? new Wife() : new Concurrent();

        $newNode->tree_id = $this->tree->id;
        $newNode->location = $this->getNewChildLocation();
        $newNode->name = $request->firstName;
        $newNode->f_name = $request->fatherName;
        $newNode->l_name = $request->lastName;
        $newNode->m_name = $request->motherName;
        $newNode->birth_date = $request->birthdate;
        $newNode->gender = $request->gender;
        $photo = $request->file('photo');
        $fileName = ($photo) ? basename($photo->store('images/photos')) : '';
        $newNode->photo = $fileName;

        if ($role === 'father') {
            $newNode->location = '1';
            Concurrent::where('tree_id', $this->tree->id)->update(['location' => DB::raw('CONCAT("1.", location)')]);
            Wife::where('tree_id', $this->tree->id)->update(['location' => DB::raw('CONCAT("1.", location)')]);
        }
        if ($role === 'wife') {
            $newNode->location = $request->location;
        }
        $newNode->save();

        return $newNode;
    }

    /**
     * delete nodes form tree with its children begining from given location
     * and delet wives
     *
     * @return Array
     */
    public function delete()
    {
//        $deletedNodes = Node::where('tree_id', $this->id)->where('location', '=', $this->location)->orWhere('location', 'REGEXP' , '(^'.$this->location.'[0-9\.]+)')->delete();
        $regexpLocation = str_replace(Location::SEPARATOR, '\\'.Location::SEPARATOR, $this->location);
        $deletedNodes = Concurrent::where('tree_id', $this->tree->id)
                ->where(function ($query) use ($regexpLocation) {
                    $query->where('location', '=', $this->location)
                            ->orWhere('location', 'REGEXP', '(^'.$regexpLocation.'\\'.Location::SEPARATOR.'[^\\'.Location::SEPARATOR.']+$)');
                })->delete();
//        $deletedWives = Wife::where('tree_id', $this->id)->where('location', '=', $this->location)->orWhere('location', 'REGEXP' , '(^'.$this->location.'[0-9\.]+)')->delete();
        $deletedWives = Wife::where('tree_id', $this->tree->id)
                ->where(function ($query) use ($regexpLocation) {
                    $query->where('location', '=', $this->location)
                            ->orWhere('location', 'REGEXP', '(^'.$this->location.'\.[^\.]+$)');
                })->delete();

        return [
            'deleted_nodes' => $deletedNodes,
            'deleted_wives' => $deletedWives,
        ];
    }

    /**
     * ----------------------------------------------------------------------
     * Delete the node by the given location
     * note: just this node and not its children
     * ----------------------------------------------------------------------
     * @return Void
     * ----------------------------------------------------------------------
     */
    public function destroy($location)
    {
        $this->nodesQuery()
                    ->where('location', $location)
                    ->delete();
    }

    /**
     * -------------------------------------------------------------------------
     * Change the locations of nodes that starts with the given oldLocation
     * and replace them with the given newLocation as part of location
     * -------------------------------------------------------------------------
     * @return Void
     * -------------------------------------------------------------------------
     */
    public function replaceAll($oldLocation, $newLocation)
    {
        $length = strlen($oldLocation);
        $statement =
        "UPDATE `".$this->nodes_table."`
          SET `location` =
             CONCAT('".$newLocation."' , SUBSTRING(location FROM ".($length + 1)."))
         WHERE `tree_id` = ".$this->tree->id."
         AND   `location` like '".$oldLocation."%'";
        // dd($statement);
        $updated = DB::update($statement);
    }

    /**
     * --------------------------------------------------------------------------------
     * update all related nodes with currunt node
     * set encouraging number of them as card number of current node
     * --------------------------------------------------------------------------------
     * @return Void
     * --------------------------------------------------------------------------------
     */
    public function updateChildrenEncouraging()
    {
        $regexpLocation = str_replace(Location::SEPARATOR, '\\'.Location::SEPARATOR, $this->node->getLocation());

        $this->nodesQuery()
                    // ->where('location', 'like', $this->node->getLocation().'%')
                    ->where('location', 'REGEXP', '(^'.$regexpLocation.'\.[^\.]+$)')
                    ->where('location', '!=', $this->node->getLocation())
                    ->update(['encouraging_number' => $this->node->data()->card_number]);
    }

    /**
     * --------------------------------------------------------------------------------
     * Transfer data from deleted node to the another table
     * for deleted nodes
     * --------------------------------------------------------------------------------
     * @param Concurrent $node
     * @return Void
     * --------------------------------------------------------------------------------
     */
    public function transferData($node)
    {
        $transferNode = new $this->transferModel();
        $transferNode->game_id = $node->game_id;
        $transferNode->user_id = $node->user_id;
        $transferNode->card_number = $node->card_number;
        $transferNode->encouraging_number = $node->encouraging_number;
        $transferNode->subscription_date = $node->subscription_date;
        $transferNode->full_name = $node->full_name;
        $transferNode->birth_place = $node->birth_place;
        $transferNode->address = $node->address;
        $transferNode->phone = $node->phone;
        $transferNode->email = $node->email;

        $transferNode->save();
    }

    /**
     * --------------------------------------------------------------
     * Delete old keys for this node from database table
     *  "concurrent_old_keys"
     * --------------------------------------------------------------
     * @param Concurrent $node
     * @return Void
     * --------------------------------------------------------------
     */
    public function deletOldKeys($node)
    {
        Concurrent_old_key::where('con_id', $node->id)
                          ->delete();
    }

    /**
     * Delete tree from database with all nodes in this tree and all wives
     *
     * @return array
     */
    public function deleteTree()
    {
        $deletedNodes = Concurrent::where('tree_id', $this->tree->id)->delete();

        $deletedWives = Wife::where('tree_id', $this->tree->id)->delete();

        $deletedTree = TreeRecord::where('id', $this->tree->id)->delete();

        return [
            'deleted_nodes' => $deletedNodes,
            'deleted_wives' => $deletedWives,
            'deleted_tree' => $deletedTree,
        ];
    }

    /**
     * Determine if the given nodes are Siblings [ node and the next_node in nodes array ]
     *
     * @param App\Node $node
     * @param App\Node $next_node
     * @return bool
     */
    public function areSiblings($node, $next_node)
    {
        if ($node == null || $next_node === null) {
            return false;
        }

        return Location::areSiblings($node->location, $next_node->location);
    }

    /**
     * Get item styled as Html code to print it in the tree
     *
     * @param Node $node
     * @param Node $is_node_father
     * @return string [ Html code ]
     */
    public function getNodeStyled($node, $is_node_father)
    {
        $node_html = '';
        // $wives = (isset($this->wives[$item->location]))?$this->wives[$item->location]:[];
        $wives = $node->wives->all();
        if ($is_node_father) {
            // Get wives of this father/node
            $node_html .= '<div class="h">';
            $node_html .= $this->getHusbandHtml($node);
            $node_html .= $this->getWivesHtml($node, $wives);
            $node_html .= '</div>';
        // }else if(isset($this->wives[$node->location])){
        } elseif (! empty($wives)) {
            $node_html .= '<div class="h">';
            $node_html .= $this->getHusbandHtml($node, ' no-children');
            $node_html .= $this->getWivesHtml($node, $wives);
            $node_html .= '</div>';
        } else {
            $node_html .= $this->getChildHtml($node);
        }

        return $node_html;
    }

    /**
     * Get Html Code for the given node as husband node
     *
     * @param Node $node
     * @param String $classes
     *
     * @return String
     */
    public function getHusbandHtml($node, $classes = '')
    {
        return $this->getNodeHtml($node, 'husband'.$classes);
    }

    /**
     * Get Html Code for the given node as Child node
     *
     * @param Node $node
     * @return String
     */
    public function getChildHtml($node)
    {
        return $this->getNodeHtml($node, 'child');
    }

    /**
     * Get Html Code for items that given as wives
     *
     * @param Array $wives
     * @return String
     */
    public function getWivesHtml($item, $wives)
    {
        $wivesCount = count($wives);
        if ($wivesCount > 0) {
            $firstWife = array_shift($wives);

            return $this->getNodeHtml($firstWife, 'wife', $wives);
        } else {
            // Return Html For Undefiend Wife
            return  '<div class="wives-group">'
                         . '<a class="node empty" data-counter="'.$this->nodesCount++.'"  data-tree="" data-location="0" data-husband_location="'.$item->location.'" data-id="0" data-name="wife" data-f_name="wife" data-l_name="wife" data-m_name="wife" data-gender="2">
                            <div class="female-node wife">
                                <div class="node-img"><img src="'.url(config('tree.tree_profiles').'icon_female.png').'"></div>
                                <div class="node-info">اضافة زوجة</div>
                                <div class="wife-number">0</div>
                            </div>
                            </a>
                        </div>';
        }
        //            return $this->getNodeHtml($firstWife, 'wife', $wives);
    }

    /**
     * Get Wives Html Code from secound wife to the last
     *
     * @param Array $wives
     * @return string
     */
    public function getOtherWivesStyled($wives)
    {
        $id = 2;
        $hText = '';
        foreach ($wives as $wife) {
            $photo = public_path($this->tree_photos_path . $wife->photo);
            $photo = (file_exists($photo) and ! is_dir($photo)) ? $wife->photo : $this->photoIcon($wife->gender);

            $hText .= '<a class="node " data-id="'.$wife->id.'" data-counter="' . $this->nodesCount++ . '"
                     data-tree="'.$wife->tree_id.'" data-location="'.$wife->location.'" data-id="'.$wife->id.'" data-name="'.$wife->name.'" data-f_name="'.$wife->f_name.'" data-l_name="'.$wife->l_name.'" data-m_name="'.$wife->m_name.'" data-birthdate="'.$wife->birth_date.'" data-gender="'.$wife->gender.'" data-role="wife">
                    <div class="female-node wife-'.$id.'">
                        <div class="node-img"><img src="'.asset($this->tree_photos_path.$photo).'"></div>
                        <div class="node-info">'.$wife->name.'</div>
                        <div class="wife-number">'.$id.'</div>
                    </div>
                </a>';
            $id++;
        }

        return $hText;
    }

    /**
     * make asocieted array as ['location'=>Wife]
     *
     * @param int $tree_id
     * @param string $location_start
     * @return array of arrays
     */
//     public function getWifesAsArray(){
    // //        $wives = Wife::where('tree_id',$this->id)->where('location','=', $this->location)->orWhere('location', 'REGEXP' , '(^'.$this->location.'[0-9\.]+)')->orderBy('location')->get();
    // //        $wives = Wife::where('tree_id',$tree_id)->where('location','>=',$location_start)->orderBy('location')->get();
//         $regexpLocation = str_replace('.', '\.', $this->location);
//         $wives = Wife::where('tree_id', $this->id)
//                 ->where(function($query){
//                     $query->where('location', '=', $this->location)
//                             ->orWhere('location', 'REGEXP' , '(^'.$regexpLocation.'\.[^\.]+$)');
//                 })->orderBy('location')->get();
//         $l_wives = [];
//         foreach($wives as $wife){
//             $l_wives[$wife->location][] = $wife;
//         }
//         return $l_wives;
//     }

//    public function getWivesOfNode($location){
//        $l_wives = [];
//        foreach($this->wives as $wife){
//            if($wife->location == $location){
//                $l_wives[] = $wife;
//            }
//        }
//        return $l_wives;
//    }

    /**
     * return html for one node
     *
     * @param Node $item
     * @param String $role
     * @param Array  $wives
     * @return string
     */
    public function getNodeHtml($node = null, $role = 'husband no-cildren', $wives = [])
    {
        if ($node === null) {
            return '';
        }

        $photo = public_path($this->tree_photos_path.$node->photo);
        $photo = (file_exists($photo) and ! is_dir($photo))
                  ? $node->photo
                  : $this->photoIcon($node->gender);
        $node_class = ($node->gender == 'm') ? 'male-node' : 'female-node';
//        $active_class = ($item->location == '1')?'active-node':'';
        $active_class = ((Session::get('activeNode') == $node->id) and ($role != 'wife')) ? 'active-node' : '';
//        $location = ($role =='wife')?$item->location:$item->location;

        $addFather = ((strpos($node->location, Location::SEPARATOR) === false) and ($role != 'wife')) ? '<div id="add-father" data-location="'.$node->location.'" data-toggle="modal" data-target="#addChildModal" alt="add Father"><i class="fa fa-plus"></i></div>' : '';
        $showFather = (($node->location == $this->pointer->location()) and ($role != 'wife') and (strpos($node->location, Location::SEPARATOR) > 0)) ? '<div id="show-father" data-location="'.$node->location.'"  title="show Father"><i class="fa fa-arrow-up"></i></div>' : '';
        $nodeCollapse = ($role === 'husband') ? '<div class="node-collapse down"><i class="fa fa-chevron-circle-up"></i></div>' : '';

        $html = ($role == 'wife') ? '<div class="wives-group">' : '';
        $html .= '<a class="node '.$active_class.'" data-id="'.$node->id.'" data-counter="' . $this->nodesCount++ . '"
                     data-tree="'.$node->tree_id.'" data-location="'.$node->location.'" data-id="'.$node->id.'" data-name="'.$node->name.'" data-f_name="'.$node->f_name.'" data-l_name="'.$node->l_name.'" data-m_name="'.$node->m_name.'" data-birthdate="'.$node->birth_date.'" data-gender="'.$node->gender.'" data-role="'.$role.'">
                    '.$addFather.$showFather.$nodeCollapse.
                    '<div class="'.$node_class.' '.$role.'">	    
                        <div class="node-img"><img src="'.asset(config('tree.tree_profiles').$photo).'"></div>
                        <div class="node-info">'.$node->name.'</div>
                        '.(($role === 'wife') ? '<div class="wife-number">1</div>' : '').'
                    </div> 
                </a>'.($this->getOtherWivesStyled($wives));
        $html = ($role == 'wife') ? $html.'</div>' : $html;

        return $html;
    }

    /**
     * return photo name for node [male, female]
     *
     * @param int $gender
     * @return string
     */
    public static function photoIcon($gender = 'm')
    {
        return ($gender === 'm') ? 'icon_male.png' : 'icon_female.png';
    }

    /**
     * Get the logest location in this tree
     *
     * @return String | NULL
     */
    public function longestLocation()
    {
        if ($row = $this->nodesQuery()->orderByRaw('LENGTH(location) DESC')->first()) {
            return $row->location;
        }

        return null;
    }

    /**
     * Indicates that wich generation the given node location is
     *
     * @return Int | NULL
     */
    public function whichGenerationIsThis($location)
    {
        // $this->validateLocation($location);
        return substr_count($location, Location::SEPARATOR) + 1;
    }

    /**
     * Indicates that wich generation the given node location is
     *
     * @return Int | NULL
     */
    public function generationOf($location)
    {
        return $this->whichGenerationIsThis($location);
    }

    /**
     * Indicate How many generation this tree has {The entire tree}
     *
     * @return Int | NULL
     */
    public function totalGenerations()
    {
        if ($location = $this->longestLocation()) {
            return Location::generation($location);
        }

        return 0;
    }

    /**
     * Get All Nodes that are in specific generation depending on given number
     *
     * @param int $generation
     * @return Array of Node
     */
    public function generation($generation = 1)
    {
        return $this->nodesQuery()->where('location', 'REGEXP', Location::singleGenerationREGEXP($generation))
                           ->get();
    }

    /**
     * Get Information about the current tree
     *
     * @return Array
     */
    public function getTreeInfo()
    {
        return [
            'userName' => $this->treeModel->user()[0]->name,
            'treeName' => $this->treeModel->name,
            'members' => $this->treeModel->nodesCount(),
            'wives' => $this->treeModel->wivesCount(),
            'males' => $this->treeModel->genderCount(1),
            'females' => $this->treeModel->genderCount(2),
            'generations' => $this->totalGenerations(),
            'settings' => $this->treeModel->getSettingsAsArray(),
        ];
    }

    /**
     * Get the location of basic node in the current tree
     *
     * @return String
     */
    public function getBasicNode()
    {
        return $this->properties()->basic_node;
    }

    /**
     * Change the location of basic node in the current tree
     *
     * @return String
     */
    public function changeBasicNode($location)
    {
        if (config('tree.tree_model')::where('id', $this->properties()->id)->update(['basic_node' => $location])) {
            $this->properties->basic_node = $location;

            return $this->properties();
        }
    }

    /**
     * Get deleted Trees
     *
     * @return Array of Tree
     */
    public function getDeletedTrees()
    {
        return TreeRecord::onlyTrashed()->get();
    }

    public function getDeletedNodes()
    {
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
        if (array_key_exists($name, $this->methods['draw'])) {
            return $this->load($this->methods['draw'][$name])->draw();
        }

        throw new BadMethodCallException("Trying to call undefined metode $name ", 1);
    }
}
