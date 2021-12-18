<?php

namespace Girover\Tree\Traits;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\GlobalScopes\OrderByLocationScope;
use Girover\Tree\Helpers\DBHelper;
use Girover\Tree\Location;
use Girover\Tree\Pointer;
use Girover\Tree\TreeBuilder;

/**
 *  The model `Tree` has to use this trait
 */
trait Treeable
{
    /**
     * mass assignment fillable properties
     *
     * @var array
     */
    protected static $fillable_cols = [
        'user_id',
        'name',
    ];

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

    public static function bootTreeable()
    {
    }

    /**
     * Every time an instance of \Girover\Tree\Models\Tree creates
     * this method will be invoked
     * @return void
     */
    public function initializeTreeable()
    {
        $this->fillable = array_merge($this->fillable, static::$fillable_cols);
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
        if ($this->pointer === null) {
            $this->makePointer();
        }

        return $this->pointer;
    }

    /**
     * Determine if the pointer is not indicating to any node yet.
     *
     * @return bool
     */
    public function isPointerFree()
    {
        return $this->pointer()->location() === null ? true : false;
    }

    /**
     * Move the pointer to indicates to the root of this tree
     *
     * @return \Girover\Tree\Pointer|null
     */
    public function movePointerToRoot()
    {
        return $this->pointer()->to(DBHelper::nodeModel()::tree($this->id)->first());
    }

    /**
    * Make database query for The Node model to start other queries from this point
    *
    * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
    */
    public function nodesQuery()
    {
        return DBHelper::nodeModel()::where('tree_id', $this->id);
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
     * To unload this tree.
     *
     * @return void
     */
    public function unload()
    {
        $this->nodes = null;
        $this->pointer()->toRoot();
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
     * Get the main node in the current tree
     *
     * @return \Girover\Tree\Models\Node
     */
    public function mainNode()
    {
        return DBHelper::nodeModel()::where('location', $this->main_node)
                                    ->where('tree_id', $this->id)
                                    ->first();
    }

    /**
     * set the node that has the given location to be main node in this tree
     * @param \Girover\Tree\Models\Node|string $node
     * @return \Girover\Tree\Models\Node|false
     */
    public function setMainNode($node)
    {
        if ($node instanceof (DBHelper::nodeModel())) {
            $this->main_node = $node->location;
            $this->save();

            return $node;
        }
        $main_node = DBHelper::nodeModel()::where('location', $node)
                                     ->where('tree_id', $this->id)
                                     ->first();
        if ($main_node) {
            $this->main_node = $node;
            $this->save();

            return $node;
        }

        return false;
    }

    /**
     * Get all Nodes in this Tree from database Table 'nodes'
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllNodes()
    {
        return $this->nodesQuery()->get();
    }

    /**
     * Get All Nodes that are in specific generation depending on given number
     *
     * @param int $generation
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nodesOfGeneration($generation = 1)
    {
        return $this->nodesQuery()->locationREGEXP(Location::singleGenerationREGEXP($generation))
                    ->get();
    }

    /**
     * Get the root node of this tree from the database
     * @return \Girover\Tree\Models\Node
     */
    public function root()
    {
        return $this->hasOne(DBHelper::nodeModel(), 'tree_id')->first();
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
        if (! array_key_exists('name', $data)) {
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
     * Get all nodes in this tree from the database
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function nodes()
    {
        return $this->hasMany(DBHelper::nodeModel(), 'tree_id');
    }

    /**
     * To count the nodes in this tree by querying database
     *
     * @return int
     */
    protected function countDatabaseNodes()
    {
        return $this->nodesQuery()
                    ->where('location', 'like', $this->pointer()->location().'%')
                    ->count();
    }

    /**
     * Indicate How many generation this tree has {The entire tree}
     *
     * @return int | NULL
     */
    public function countGenerations()
    {
        $node = $this->nodesQuery()
                     ->withoutGlobalScope(OrderByLocationScope::class)
                     ->orderByRaw('LENGTH(location) DESC')->first();

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
        $this->pointer()->to($location);

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
        return $this->nodesQuery()
                    ->withoutGlobalScope(OrderByLocationScope::class)
                    ->orderByRaw('LENGTH(location) DESC')->get();
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
     *
     * @return string
     */
    public function draw()
    {
        $tree_generator = new TreeBuilder($this);

        return $tree_generator->draw();
    }
}
