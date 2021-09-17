<?php

namespace Girover\Tree\Traits;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Models\Node;
use Girover\Tree\Models\Tree;
use Girover\Tree\Pointer;
use Illuminate\Support\Facades\Session;

/**
 *  The model `Tree` has to use this trait
 */
trait Treeable
{
    /**
     * represent a node in the tree [current node]
     *
     * @var \Girover\Tree\Pointer|null
     */
    private $pointer = null;

    /**
     * @var \Illuminate\Database\Eloquent\Collection|null
     */
    protected $nodes = null;

    /**
     * @var bool
     */
    public $treeExists = false;

    /**
     * @var int uses in converting tree to html
     */
    public $nodesCount = 1;

    /**
     * @var array
     */
    public $ancestors = []; // array holding ancestries of location

    /**
     * @var string
     */
    public $tree_photos_path = 'images/photos/';

    public static function bootTreeable()
    {
    }

    public function initializeTreeable()
    {
        // $this->pointer = Node::find(1);
        // $this->makePointer();
        $this->makePointer();
    }

    public function nodeModel()
    {
        return Node::class;
    }

    public function treeModel()
    {
        return Tree::class;
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
     * return photo name for node [male, female]
     *
     * @param int|string $gender
     * @return string
     */
    public static function photoIcon($gender = 'm')
    {
        return ($gender === 'm') ? 'icon_male.png' : 'icon_female.png';
    }

    /**
     * Tree has a pointer. create and make it free
     *
     * @return void
     */
    protected function makePointer()
    {
        $this->pointer = new Pointer($this);
    }

    /**
     * get the pointer of the tree
     * @return \Girover\Tree\Pointer
     */
    public function pointer()
    {
        return $this->pointer;
    }

    /**
     * Move the pointer to indicates to the root of this tree
     *
     * @return \Girover\Tree\Pointer|null
     */
    public function movePointerToRoot()
    {
        return $this->pointer->to(Node::tree($this->id)->first());
    }

    /**
    * Make database query for The Node model to start other queries from this point
    *
    * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
    */
    public function nodesQuery()
    {
        return Node::where('tree_id', $this->id);
    }

    /**
     * Check if this tree is active
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool)$this->active;
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
     * Determine if the nodes of this tree are already loaded.
     *
     * @return bool
     */
    public function isLoaded()
    {
        return $this->nodes !== null ? true : false;
    }

    /**
     * Determine if the tow given nodes are father and son
     *
     * @param \Girover\Tree\Models\Node $father
     * @param \Girover\Tree\Models\Node $son
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
     * Determine if the given nodes are Siblings [ node and the next_node in nodes array ]
     *
     * @param \Girover\Tree\Models\Node $node
     * @param \Girover\Tree\Models\Node $next_node
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
    * Load specific number of generation of this tree
    *
    * @param int $number_of_generations
    * @return \Illuminate\Database\Eloquent\Collection
    */
    public function loadGenerations($number_of_generations)
    {
        return  $this->nodes = $this->nodesQuery()
                                    ->where('location', 'REGEXP', Location::multiGenerationsREGEXP($this->pointer->location(), $number_of_generations))
                                    ->get();
    }

    /**
    * Load tree nodes
    *
    * Get all Nodes of this tree from database
    * and set them in the variable $nodes
    *
    * @param int|null $number_of_generations
    * @return \Girover\Tree\Models\Tree
    */
    public function load($number_of_generations = null)
    {
        if (! is_numeric($number_of_generations) && ! is_null($number_of_generations)) {
            throw new TreeException("Error: The given generation '".$number_of_generations."' should be number", 1);
        }
        $this->nodes = $number_of_generations
                       ? $this->loadGenerations($number_of_generations)
                       : $this->getAllNodesFromPointer();

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
     * Get Information about the current tree
     *
     * @return array
     */
    public function getTreeInfo()
    {
        return [
            'userName' => $this->user()[0]->name,
            'treeName' => $this->name,
            'members' => $this->countNodes(),
            'wives' => $this->wivesCount(),
            'males' => $this->genderCount(1),
            'females' => $this->genderCount(2),
            'generations' => $this->countGenerations(),
            'settings' => $this->getSettingsAsArray(),
        ];
    }

    /**
     * Get the location of basic node in the current tree
     *
     * @return string
     */
    public function getBasicNode()
    {
        return $this->basic_node;
    }

    /**
     * Get all Nodes in this Tree from database Table 'nodes'
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllNodes()
    {
        return $this->nodesQuery()
                    ->get();
    }

    /**
     * Get all Nodes in this Tree from database Table 'nodes'
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllNodesFromPointer()
    {
        return $this->nodesQuery()
                    ->where('location', 'like', $this->pointer->location().'%')
                    ->get();
    }

    /**
     * Get the node that is saved in nodes array and equal to the given location as argument
     *
     * @param string $location
     * @return Node
     */
    public function getLoadedNode($location)
    {
        foreach ($this->nodes as $node) {
            if ($node->location == $location) {
                return $node;
            }
        }
    }

    /**
     * Get All Nodes that are in specific generation depending on given number
     *
     * @param int $generation
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nodesOfGeneration($generation = 1)
    {
        return $this->nodesQuery()->where('location', 'REGEXP', Location::singleGenerationREGEXP($generation))
                    ->get();
    }

    /**
     * Get the root node of this tree from the database
     * @return \Girover\Tree\Models\Node
     */
    public function root()
    {
        return $this->hasOne(Node::class)->first();
    }

    /**
     * Create the root for this tree.
     *
     * @param array $data data of the root node
     * @return \Girover\Tree\Models\Node
     *
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function createRoot($data = [])
    {
        if (empty($data)) {
            throw new TreeException("Error: no data are provided to create Root for the tree [ ".$this->name." ]", 1);
        }
        if (!array_key_exists('name', $data)) {
            throw new TreeException("Error: the field 'name' should be provided", 1);
        }
        if ($this->isEmptyTree()) {
            return $this->nodes()->create(array_merge($data, ['tree_id' => $this->id, 'location' => Location::generateRootLocation()]));
        }
        // The tree is not empty, e.i. There is already a Root for this tree.
        throw new TreeException("Root for tree {$this->name} is already exists", 1);
    }

    /**
     * Create new Root node in this tree and make
     * the current root child of the new root
     *
     * @param array $data
     * @return \Girover\Tree\Models\Node|null
     */
    public function newRoot($data = [])
    {
        if (empty($data)) {
            return null;
        }

        if ($this->isEmptyTree()) {
            return $this->createRoot($data);
        }

        return $this->pointer()->toRoot()->createFather($data);
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
     * @return \Illuminate\Database\Eloquent\Collection collection of wives
     */
    public function wivesOf($location)
    {
        return $this->pointer->to($location)->wives();
    }

    /**
     * Get all ancestors nodes of the given location in this tree.
     *
     * @param string $location
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function ancestorsOf($location)
    {
        return $this->pointer->to($location)->ancestors();
    }

    /**
     * Get all nodes in this tree from the database
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function nodes()
    {
        return $this->hasMany(Node::class);
    }

    /**
     * get all loaded nodes in this tree
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function loadedNodes()
    {
        return $this->nodes;
    }

    /**
     * To count the nodes in this tree by querying database
     *
     * @return int
     */
    protected function countDatabaseNodes()
    {
        return $this->nodesQuery()
                    ->where('location', 'like', $this->pointer->location().'%')
                    ->count();
    }

    /**
     * To count the tree nodes that are already loaded
     *
     * @return int
     */
    protected function countLoadedNodes()
    {
        if (null === $this->nodes) {
            return 0;
        }

        return $this->nodes->count();
    }

    /**
     * Count the nodes
     *
     * Count the number of nodes in the tree of current node
     * First check if there are nodes assigned to variable $node:
     * If there are no nodes then query the database
     * if there are nodes count them from $nodes
     *
     * @return int
     */
    public function countNodes()
    {
        return $this->nodes === null
               ? $this->countDatabaseNodes()
               : $this->countLoadedNodes();
    }

    /**
     * Indicate How many generation this tree has {The entire tree}
     *
     * @return int | NULL
     */
    public function countGenerations()
    {
        $node = $this->nodesQuery()->orderByRaw('LENGTH(location) DESC')->first();

        if (null !== $node) {
            return Location::generation($node->location);
        }

        return 0;
    }

    /**
     * Move the pointer of the tree to a given node or location
     *
     * @param \Girover\Tree\Models\Node|string $location
     * @return $this
     */
    public function movePointerTo($location)
    {
        $this->pointer->to($location);

        return $this;
    }
    /**
     * Move the pointer of the tree to a given node or location
     *
     * @param \Girover\Tree\Models\Node|string $location
     * @return $this
     */
    public function goTo($location)
    {
        return $this->movePointerTo($location);
    }

    /**
     * Get nodes who have the longest location in the tree
     * Get the newest generation members in the tree
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nodesOnTop()
    {
        return $this->nodesQuery()->orderByRaw('LENGTH(location) DESC')->get();
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
                . '<a class="node" data-id=""  data-location="0" data-tree="" data-id="" data-name="" data-gender="" data-role="empty">
                        <div class="empty-node">
                        <div class="node-info-wrapper">
                            <div class="node-info">
                                <div class="node-img"><img src="'. asset(config('tree.assets.path_avatar').'icon_male.png').'"></div>
                                <div class="name">add new</div>                
                            </div>
                        </div>
                        </div>
                    </a>'
                . '</li>'
            . '</ul></div>';
    }

    /**
     * Get item styled as Html code to print it in the tree
     *
     * @param Node $node
     * @param Node $is_node_father
     * @return string [ Html code ]
     */
    protected function getNodeStyled($node, $is_node_father)
    {
        $node_html = '';
        // $wives = (isset($this->wives[$item->location]))?$this->wives[$item->location]:[];
        $wives = $node->wives->all();
        if ($is_node_father) {
            // Get wives of this father/node
            $node_html .= '<div class="parent">';
            $node_html .= $this->getHusbandHtml($node);
            $node_html .= $this->getWivesHtml($node, $wives);
            $node_html .= '</div>';
        // }else if(isset($this->wives[$node->location])){
        } elseif (! empty($wives)) {
            $node_html .= '<div class="parent">';
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
     * @param \Girover\Tree\Models\Node $node
     * @param string $classes
     *
     * @return string
     */
    protected function getHusbandHtml($node, $classes = '')
    {
        return $this->getNodeHtml($node, 'husband'.$classes);
    }

    /**
     * Get Html Code for the given node as Child node
     *
     * @param \Girover\Tree\Models\Node $node
     * @return string
     */
    protected function getChildHtml($node)
    {
        return $this->getNodeHtml($node, 'child');
    }

    /**
     * Get Html Code for items that given as wives
     *
     * @param array $wives
     * @return string
     */
    protected function getWivesHtml($item, $wives)
    {
        $wivesCount = count($wives);
        if ($wivesCount > 0) {
            $firstWife = array_shift($wives);

            return $this->getNodeHtml($firstWife, 'wife', $wives);
        } else {
            // Return Html For Undefined Wife
            return  '<div class="wives-group">'
                      . '<a class="node empty" data-counter="'.$this->nodesCount++.'"  data-tree="" data-location="0" data-husband_location="'.$item->location.'" data-id="0" data-name="wife" data-f_name="wife" data-l_name="wife" data-m_name="wife" data-gender="2">
                         <div class="female-node wife empty">
                            <div class="node-info-wrapper">
                                <div class="node-info">
                                    <div class="node-img"><img src="'.asset(config('tree.assets.path_avatar').'icon_female.png').'"></div>
                                    <div class="name">'. __('add wife') .'</div>
                                    <div class="wife-number">0</div>
                                </div>
                            </div>
                         </div>
                         </a>
                     </div>';
        }
        //            return $this->getNodeHtml($firstWife, 'wife', $wives);
    }

    /**
     * Get Wives Html Code from second wife to the last
     *
     * @param array $wives
     * @return string
     */
    protected function getOtherWivesStyled($wives)
    {
        $id = 2;
        $hText = '';
        foreach ($wives as $wife) {
            $photo = public_path($this->tree_photos_path . $wife->photo);
            $photo = (file_exists($photo) and ! is_dir($photo)) ? $wife->photo : $this->photoIcon($wife->gender);

            $hText .= '<a class="node " data-id="'.$wife->id.'" data-counter="' . $this->nodesCount++ . '"
                            data-tree="'.$wife->tree_id.'" data-location="'.$wife->location.'" data-id="'.$wife->id.'" data-name="'.$wife->name.'" data-f_name="'.$wife->f_name.'" data-l_name="'.$wife->l_name.'" data-m_name="'.$wife->m_name.'" data-birthdate="'.$wife->birth_date.'" data-gender="'.$wife->gender.'" data-role="wife">
                         <div class="female-node wife-'.$id.'">
                            <div class="node-info-wrapper">
                                <div class="node-info">
                                    <div class="node-img"><img src="'.asset(config('tree.assets.path_avatar').$photo).'"></div>
                                    <div class="name">'.$wife->name.'</div>
                                    <div class="wife-number">'.$id.'</div>
                                </div>
                            </div>
                         </div>
                       </a>';
            $id++;
        }

        return $hText;
    }

    /**
     * return html for one node
     *
     * @param \Girover\Tree\ModelsNode $item
     * @param string $role
     * @param array  $wives
     * @return string
     */
    protected function getNodeHtml($node = null, $role = 'husband no-children', $wives = [])
    {
        if ($node === null) {
            return '';
        }

        // $photo = public_path($this->tree_photos_path.$node->photo);
        $photo = config('tree.assets.path_avatar').$node->photo;
        
        $photo = (file_exists($photo) && ! is_dir($photo))
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
                        <div class="node-info-wrapper">
                            <div class="node-info">
                                <div class="node-img"><img src="'.asset(config('tree.assets.path_avatar').$photo).'"></div>
                                <div class="name">'.$node->name.'</div>
                                '.(($role === 'wife') ? '<div class="wife-number">1</div>' : '').'
                            </div>
                        </div>
                    </div> 
                </a>'.($this->getOtherWivesStyled($wives));
        $html = ($role == 'wife') ? $html.'</div>' : $html;

        return $html;
    }

    /**
     * Draw the current tree and return it as HTML.
     *
     * @return string
     */
    public function toTree()
    {
        return $this->draw();
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
            $this->load();
            if ($this->nodes === null) {
                return $this->emptyTree();
            }
        }

        if (($nodes_count = $this->countNodes()) == 0) {
            return $this->emptyTree();
        }

        $fathers = [];
        $close_status = false;
        $is_node_father = false;
        $tree_html = '' ;

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
}
