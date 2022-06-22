<?php

namespace Girover\Tree\TreeBuilder;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\TreeBuilder\TreeBuilderInterface;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\Model;

class HtmlTreeBuilder implements TreeBuilderInterface
{
    /**
     * @var \Illuminate\Database\Eloquent\Model|null
     */
    protected $tree = null;
 
    /**
     * @var \Illuminate\Database\Eloquent\Collection|null
     */
    protected $nodes = null;

    /**
     * @var int uses in converting tree to html
     */
    public $nodesCount = 1;

    /**
     * instantiate a tree generator
     * 
     * @param \Illuminate\Database\Eloquent\Model $tree
     * @return void
     */
    public function __construct(Model $tree) {
        $this->tree = $tree;
    }

    /**
     * Attributes which must be rendered to node element in html tree
     * 
     * @param \illuminate\Database\Eloquent\Model $nodeable
     * @return string
     */
    public function htmlNodeAttributes($nodeable)
    {
        $attributes = '';

        foreach (config('tree.html_node_attributes') as $attribute) {
            $attributes .= ' data-' . $attribute . '="' .$nodeable->{$attribute} . '" ';
        }

        return $attributes;
    }

    /**
     * Get Html for empty tree. when the user has no trees in database
     *
     * @return string
     */
    public function emptyTree()
    {
        return '<div id="tree" class="tree"><ul>'
                . '<li>'
                . '<a class="node" data-role="empty">
                        <div class="empty-node">
                        <div class="node-info-wrapper">
                            <div class="node-info">
                                <div class="node-img"><img src="'. maleAsset() .'"></div>
                                <div class="name">add new</div>                
                            </div>
                        </div>
                        </div>
                    </a>'
                . '</li>'
            . '</ul></div>';
    }

    /**
     * Get all Nodes in this Tree from database Table 'nodes'
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllNodes()
    {
        return $this->tree->nodesQuery()->get();
    }

    /**
     * Get all Nodes in this Tree from database Table 'nodes'
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllNodesFromPointer()
    {
        if ($this->tree->isPointerFree()) {
            return $this->getAllNodes();
        }
        
        return $this->tree->nodesQuery()
                    ->where('location', 'like', $this->tree->pointer()->location().'%')
                    ->get();
    }

    /**
     * Load specific number of generation of this tree
     *
     * @param int $number_of_generations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function loadGenerations($number_of_generations = 1)
    {
        return  $this->nodes = $this->tree->nodesQuery()
                                    ->locationREGEXP(Location::multiGenerationsREGEXP($this->tree->pointer()->location(), $number_of_generations))
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
     * get all loaded nodes in this tree
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function loadedNodes()
    {
        return $this->nodes;
    }

    /**
     * Get the node that is saved in nodes array and equal to the given location as argument
     *
     * @param string $location
     * @return \Girover\Tree\Models\Node
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
     * Determine if the nodes of this tree are already loaded.
     *
     * @return bool
     */
    public function isLoaded()
    {
        return $this->nodes !== null ? true : false;
    }

    /**
     * To count the nodes in this tree by querying database
     *
     * @return int
     */
    protected function countDatabaseNodes()
    {
        return $this->tree->nodesQuery()
                    ->where('location', 'like', $this->tree->pointer()->location().'%')
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
                      . '<a class="node empty" data-counter="'.$this->nodesCount++.'" data-husband-location="'.$item->location.'">
                         <div class="female-node wife empty">
                            <div class="node-info-wrapper">
                                <div class="node-info">
                                    <div class="node-img"><img src="'. femaleAsset() .'"></div>
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
            // $photo = public_path(Photos::assetFolder() . $wife->photo);
            // $photo = TreeHelpers::avatarPath() . $wife->photo;
            // $photo = (file_exists($photo) and ! is_dir($photo)) ? $wife->photo : $this->photoIcon($wife->gender);

            $hText .= '<a class="node " data-counter="' . $this->nodesCount++ . '" '. $this->htmlNodeAttributes($wife) .' data-role="wife">
                         <div class="female-node wife-'.$id.'">
                            <div class="node-info-wrapper">
                                <div class="node-info">
                                    <div class="node-img"><img src="'.$wife->photoURL().'"></div>
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

        $node_class = ($node->gender == 'm') ? 'male-node' : 'female-node';
        
        $active_class = ((Session::get('activeNode') == $node->id) and ($role != 'wife')) ? 'active-node' : '';
        
        $addFather = ((strpos($node->location, Location::SEPARATOR) === false) and ($role != 'wife')) ? '<div id="add-father" data-location="'.$node->location.'" data-toggle="modal" data-target="#addChildModal" alt="add Father"><i class="fa fa-plus"></i></div>' : '';
        $showFather = (($node->location == $this->tree->pointer()->location()) and ($role != 'wife') and (strpos($node->location, Location::SEPARATOR) > 0)) ? '<div id="show-father" data-location="'.$node->location.'"  title="show Father"><i class="fa fa-arrow-up"></i></div>' : '';
        $nodeCollapse = ($role === 'husband') ? '<div class="node-collapse down"><i class="fa fa-chevron-circle-up"></i></div>' : '';

        $html = ($role == 'wife') ? '<div class="wives-group">' : '';
        $html .= '<a class="node '.$active_class.'" data-counter="' . $this->nodesCount++ . '" data-role="'.$role.'" '.$this->htmlNodeAttributes($node).'>
                    '.$addFather.$showFather.$nodeCollapse.
                    '<div class="'.$node_class.' '.$role.'">	    
                        <div class="node-info-wrapper">
                            <div class="node-info">
                                <div class="node-img"><img src="'.$node->photoURL().'"></div>
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
     * Draw the current tree and return it as HTML
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
            $is_node_father = $node->isFatherOf($next_node);

            if ($close_status) {
                $father_loop_count = count($fathers);
                for ($j = $father_loop_count ; $j > 0; $j--) {
                    $node_father = array_pop($fathers);
                    if ($node->isSiblingOf($node_father)) {
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
                if ($node->isFatherOf($next_node)) {
                    $tree_html .= ' <ul> ' ;
                    $fathers[] = $node;
                } else {
                    if ($node->isSiblingOf($next_node)) {
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
