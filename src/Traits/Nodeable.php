<?php

namespace Girover\Tree\Traits;

use Girover\Tree\Database\Eloquent\NodeCollection;
use Girover\Tree\Database\Eloquent\NodeEloquentBuilder;
use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\GlobalScopes\ImagesEagerRelationScope;
use Girover\Tree\GlobalScopes\OrderByLocationScope;
use Girover\Tree\GlobalScopes\WivesEagerRelationScope;
use Girover\Tree\Location;
use Girover\Tree\Models\Tree;
use Illuminate\Support\Facades\DB;

/**
 *
 */
trait Nodeable
{
    /**
     * massassignment fillable properties
     *
     * @var array
     */
    protected static $fillable_cols = [
        'tree_id',
        'location',
        'name',
        'f_name',
        'l_name',
        'm_name',
        'birth_date',
        'birth_place',
        'gender',
    ];

    /**
     * @var \Girover\Tree\Models\Tree the tree that contain this node
     */
    public $parent_tree;

    /**
     * Eager loading relationships
     *
     * @var array // from config file tree.php
     */
    // protected static $bound_relationships = [];

    /**
     * {@inheritdoc}
     *
     * @param \Illuminate\Databse\Query\Builder
     * @return \Girover\Tree\Database\TreeQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new NodeEloquentBuilder($query);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function newCollection(array $models = [])
    {
        return new NodeCollection($models);
    }

    public static function bootNodeable()
    {

        // Adding global scope o the model
        static::addGlobalScope(new OrderByLocationScope());
        static::addGlobalScope(new WivesEagerRelationScope());
        static::addGlobalScope(new ImagesEagerRelationScope());

        static::saving(function ($model) {
            // dd($model);
        });

        // When model is deleted
        static::deleted(function ($model) {

            // if the model has shift_children property is not setted
            // then delete children, otherwise delete just the node
            if ($model->shift_children === null) {
                return static::tree($model->tree_id)
                ->where('location', 'like', $model->location.'%')
                ->delete();
            }
        });
    }

    /**
     * This method is called when new instance of Node is initialized
     *
     * @return void
     */
    public function initializeNodeable()
    {
        // Adding mass assignment fields to fillable array
        $this->fillable = array_merge($this->fillable, static::$fillable_cols);
    }

    /**
     * Relationship for Getting wives og the node.
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function wives()
    {
        $model = (null !== config('tree.node_relationships.wives.model'))
                ? config('tree.node_relationships.wives.model')
                : 'Girover\Tree\Models\Marriage';

        return $this->belongsToMany(get_class(), $model, 'node_husband_id', 'node_wife_id');
    }

    /**
     * Relationship for Getting images og the node.
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function images()
    {
        $model = (null !== config('tree.node_relationships.images.model'))
                ? config('tree.node_relationships.images.model')
                : 'Girover\Tree\Models\images';

        return $this->hasMany($model, 'node_id', 'id');
    }

    /**
     * Determine if the node is Root in the tree
     *
     * @return bool
     */
    public function isRoot()
    {
        if (Location::isRoot($this->location)) {
            return true;
        }
        $father_location = Location::father($this->location);

        return (static::tree($this->tree_id)->location($father_location)->count())
                ? false
                : true;
    }

    /**
     * Determine if the node has parent in the tree
     *
     * @return \Girover\Tree\Models\Node | null
     */
    public function hasParent()
    {
        if (Location::isRoot($this->location)) {
            return false;
        }
        $father_location = Location::father($this->location);

        return static::tree($this->tree_id)
                     ->location($father_location)
                     ->first();
    }

    /**
     * Determine if the node has children
     *
     * @return \Girover\Tree\Models\Node | null
     */
    public function hasChildren()
    {
        return static::tree($this->tree_id)
                     ->locationREGEXP(Location::childrenREGEXP($this->location))
                     ->count()
                     ? true : false;
    }

    /**
     * Determine if the node has siblings
     *
     * @return bool
     */
    public function hasSiblings()
    {
        return static::tree($this->tree_id)
                     ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                     ->count() > 1
                     ? true : false;
    }

    /**
     * Indicates wich generation this node is
     *
     * @return Int | NULL
     */
    public function generation()
    {
        return Location::generation($this->location);
    }

    /**
     * Get the Root of this node
     *
     * @param \Girover\Tree\Models\Node
     */
    public function root()
    {
        return static::tree($this->tree_id)
                     ->location(Location::firstPossibleSegment())
                     ->first();
    }

    /**
     * Get the father of this node
     *
     * @param \Girover\Tree\Models\Node
     */
    public function father()
    {
        return static::tree($this->tree_id)
                     ->location(Location::father($this->location))
                     ->first();
    }

    /**
     * Get grandfather of this node
     *
     * @param \Girover\Tree\Models\Node
     */
    public function grandfather()
    {
        return static::tree($this->tree_id)
                     ->location(Location::grandfather($this->location))
                     ->first();
    }

    /**
     * Get the ancestor with given number of this node
     * if NULL given get father
     *
     * @param \Girover\Tree\Models\Node
     */
    public function ancestor($ancestor = null)
    {
        if ($ancestor === null) {
            return $this->father();
        }

        return static::tree($this->tree_id)
                     ->location(Location::ancestor($this->location, $ancestor))
                     ->first();
    }

    /**
     * Getting all ancestors nodes from the location where the Pointer indicates to
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function ancestors()
    {
        return static::tree($this->tree_id)
                     ->where('location', '!=', $this->location)
                     ->whereIn('location', Location::allLocationsToRoot($this->location))
                     ->get();
    }

    /**
     * Get all uncles and/or aunts of this node.
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    protected function allFatherSiblings()
    {
        return static::tree($this->tree_id)
                    ->locationNot(Location::father($this->location))
                    ->locationREGEXP(Location::withSiblingsREGEXP(Location::father($this->location)))
                    ->get();
    }

    /**
     * Get all uncles and/or aunts of this node
     * dependes on the given gender
     *
     * @param \Illuminate\Support\Collection
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function fatherSiblings($gender = null)
    {
        if (is_null($gender)) {
            return $this->allFatherSiblings();
        }

        return static::tree($this->tree_id)
                    ->locationNot(Location::father($this->location))
                    ->locationREGEXP(Location::withSiblingsREGEXP(Location::father($this->location)))
                    ->where('gender', $gender)
                    ->get();
    }

    /**
     * Get all uncles of this node
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function uncles()
    {
        return $this->fatherSiblings('m');
    }

    /**
     * Get all aunts of this node
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function aunts()
    {
        return $this->fatherSiblings('f');
    }

    /**
     * Get all children of this node.
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    protected function allChildren()
    {
        return static::tree($this->tree_id)
                    ->locationREGEXP(Location::childrenREGEXP($this->location))
                    ->get();
    }

    /**
     * Get all direct children of this node
     * without descendants
     *
     * @param \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function children($gender = null)
    {
        if (is_null($gender)) {
            return $this->allChildren();
        }

        return static::tree($this->tree_id)
                    ->locationREGEXP(Location::childrenREGEXP($this->location))
                    ->where('gender', $gender)
                    ->get();
    }

    /**
     * Get all direct sons of this node
     * without descendants
     *
     * @param \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function sons()
    {
        return $this->children('m');
    }

    /**
     * Get all direct sons of this node
     * without descendants
     *
     * @param \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function daughters()
    {
        return $this->children('f');
    }

    /**
     * Count children of this node.
     *
     * @return int
     */
    protected function countAllChildren()
    {
        return static::tree($this->tree_id)
                    ->locationREGEXP(Location::childrenREGEXP($this->location))
                    ->count();
    }

    /**
     * Count children of this node by gender.
     *
     * @return int
     */
    public function countChildren($gender = null)
    {
        if (is_null($gender)) {
            return $this->countAllChildren();
        }

        return static::tree($this->tree_id)
                    ->locationREGEXP(Location::childrenREGEXP($this->location))
                    ->where('gender', $gender)
                    ->count();
    }

    /**
     * Count sons of this node.
     *
     * @return int
     */
    public function countSons()
    {
        return $this->countChildren('m');
    }

    /**
     * Count daughters of this node.
     *
     * @return int
     */
    public function countDaughters()
    {
        return $this->countChildren('f');
    }

    /**
     * Count siblings of this node.
     *
     * @return int
     */
    protected function countAllSiblings()
    {
        return static::tree($this->tree_id)
                    ->locationNot($this->location)
                    ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                    ->count();
    }

    /**
     * Count siblings of this node by gender.
     *
     * @param '0'|'1'|null $gender the gender of sibling
     * @return int
     */
    public function countSiblings($gender = null)
    {
        if (is_null($gender)) {
            return $this->countAllSiblings();
        }

        return static::tree($this->tree_id)
                    ->locationNot($this->location)
                    ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                    ->where('gender', $gender)
                    ->count();
    }

    /**
     * count brothers of this node.
     *
     * @return int
     */
    public function countBrothers()
    {
        return $this->countSiblings('m');
    }

    /**
     * count sisters of the node.
     *
     * @return int
     */
    public function countSisters()
    {
        return $this->countSiblings('f');
    }

    /**
     * Count all descendants of the node.
     *
     * @return int
     */
    protected function countAllDescendants()
    {
        return static::tree($this->tree_id)
                        ->locationNot($this->location)
                        ->where('location', 'like', $this->location.'%')
                        ->count();
    }

    /**
     * Count all descendants of the node by gender.
     *
     * @param '0'|'1'|null $gender the gender of sibling
     * @return int
     */
    public function countDescendants($gender = null)
    {
        if (is_null($gender)) {
            return $this->countAllDescendants();
        }

        return static::tree($this->tree_id)
                    ->locationNot($this->location)
                    ->where('location', 'like', $this->location.'%')
                    ->where('gender', $gender)
                    ->count();
    }

    /**
     * Count all male descendants of this node.
     *
     * @return int
     */
    public function countMaleDescendants()
    {
        return $this->countDescendants('m');
    }

    /**
     * Count all female descendants oc this node.
     *
     * @return int
     */
    public function countFemaleDescendants()
    {
        return $this->countDescendants('f');
    }

    /**
     * Get the first child of this node
     *
     * @param \Girover\Tree\Models\Node
     */
    public function firstChild()
    {
        return  static::where('location', 'REGEXP', Location::childrenREGEXP($this->location))
                     ->orderBy('location', 'ASC')
                     ->first();
        // return (new EagerTree($this->tree_id))->pointer->silentlyTo($this)->firstChild();
    }

    /**
     * Get the last child of this node
     *
     * @param \Girover\Tree\Models\Node
     */
    public function lastChild()
    {
        return  static::where('location', 'REGEXP', Location::childrenREGEXP($this->location))
                     ->orderBy('location', 'DESC')
                     ->first();
        // return (new EagerTree($this->tree_id))->pointer->silentlyTo($this)->lastChild();
    }

    /**
     * Get all siblings of this node
     * without the node static
     *
     * @param \Illuminate\Support\Collection
     */
    public function siblings()
    {
        return static::tree($this->tree_id)
                    ->locationNot($this->location)
                    ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                    ->get();
    }

    /**
     * Get all siblings of this node
     * including the node
     *
     * @param \Illuminate\Support\Collection
     */
    public function siblingsAndSelf()
    {
        return static::tree($this->tree_id)
                     ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                     ->get();
    }

    /**
     * Get the next sibling that is younger than this node
     *
     * @param \Illuminate\Support\Collection
     */
    public function nextSibling()
    {
        return static::tree($this->tree_id)
                     ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                     ->locationNot($this->location)
                     ->where('location', '>', $this->location)
                     ->first();
    }

    /**
     * Get all siblings those are younger than this node
     *
     * @param \Illuminate\Support\Collection
     */
    public function nextSiblings()
    {
        return static::tree($this->tree_id)
                     ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                     ->locationNot($this->location)
                     ->where('location', '>', $this->location)
                     ->get();
    }

    /**
     * Get the previous sibling og this node.
     *
     * @param \Girover\Tree\Models\Node
     */
    public function prevSibling()
    {
        return static::tree($this->tree_id)
                     ->withoutGlobalScope(OrderByLocationScope::class)
                     ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                     ->locationNot($this->location)
                     ->where('location', '<', $this->location)
                     ->orderBy('location', 'desc')
                     ->first();
    }

    /**
     * Get all siblings those are older than this node
     *
     * @param \Illuminate\Support\Collection
     */
    public function prevSiblings()
    {
        return $this->tree($this->tree_id)
                    ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                    ->locationNot($this->location)
                    ->where('location', '<', $this->location)
                    ->get();
    }

    /**
     * Get the first sibling of node that the Pointer is indicating to
     *
     * @return \Girover\Tree\Models\Node
     */
    public function firstSibling()
    {
        return  static::tree($this->tree_id)
                     ->where('location', '!=', $this->location)
                     ->where('location', 'REGEXP', Location::withSiblingsREGEXP($this->location))
                     ->orderBy('location')
                     ->first();
    }

    /**
     * Get last sibling of node that the Pointer is indicating to
     *
     * @return \Girover\Tree\Models\Node
     */
    public function lastSibling()
    {
        return  static::tree($this->tree_id)
                     ->where('location', '!=', $this->location)
                     ->where('location', 'REGEXP', Location::withSiblingsREGEXP($this->location))
                     ->orderBy('location', 'desc')
                     ->first();
    }

    /**
     * Create new node
     *
     * @param \Girover\Tree\Models\Node|array $data data for the new node
     * @param string $location the location of the new [child|sibling|...]
     */
    protected function createNewNode($data, $location, $gender = 'm')
    {
        Location::validate($location);
        if ($data instanceof static) {
            $data->tree_id = $this->tree_id;
            $data->location = $location;
            $data->gender = $gender;
            $data->save();

            return $data;
        }
        if (! is_array($data)) {
            throw new TreeException("Bad argument type. The argument passed to ".__METHOD__." must be an array or an instance of [".static::class."]. ".gettype($data)." is given", 1);
        }

        $data['tree_id'] = $this->tree_id;
        $data['location'] = $location;
        $data['gender'] = $gender;

        return static::create($data);
    }

    /**
     * Create new sibling for this node
     * by default the new node is son.
     *
     * @param array|static data for the new sibling
     * @param string gender of the new sibling
     * @return \Girover\Tree\Models\Node|null
     */
    public function newSibling($data, $gender = 'm')
    {
        if ($this->isRoot()) {
            throw new TreeException("Cannot add sibling to the Root of the tree.", 1);
        }

        if ($this->hasSiblings()) {
            $new_sibling_location = Location::generateNextLocation($this->lastSibling()->location);
        } else {
            $new_sibling_location = Location::generateNextLocation($this->location);
        }

        Location::validate($new_sibling_location);

        return $this->createNewNode($data, $new_sibling_location, $gender);
    }

    /**
     * Create new brother for this node
     *
     * @param array|static data for the new sibling
     * @return \Girover\Tree\Models\Node|null
     */
    public function newBrother($data)
    {
        return $this->newSibling($data, 'm');
    }

    /**
     * Create new sister for this node
     * depending on the gender of 0 passed to `createNew` method
     *
     * @param array|static data for the new sister
     * @return \Girover\Tree\Models\Node|null
     */
    public function newSister($data)
    {
        return $this->newSibling($data, 'f');
    }

    /**
     * Create new child for this node
     *
     * @param array|static data for the new child
     * @return \Girover\Tree\Models\Node|null
     */
    public function newChild($data, $gender = 'm')
    {
        $last_child = $this->lastChild();

        if ($last_child == null) {
            $new_child_location = $this->location.Location::SEPARATOR.Location::firstPossibleSegment();
        } else {
            $new_child_location = Location::generateNextLocation($last_child->location);
        }

        Location::validate($new_child_location);

        return $this->createNewNode($data, $new_child_location, $gender);
    }

    /**
     * Create new son for this node
     *
     * @param array|static data for the new son
     * @return \Girover\Tree\Models\Node|null
     */
    public function newSon($data)
    {
        return $this->newChild($data, 'm');
    }

    /**
     * Create new son for this node
     *
     * @param array|static data for the new daughter
     * @return \Girover\Tree\Models\Node|null
     */
    public function newDaughter($data)
    {
        return $this->newChild($data, 'f');
    }

    /**
     * Move the node with its children
     * to be child of the given location
     *
     * @param string $location: location to move node to it
     * @return \Girover\Tree\Models\Node
     */
    public function makeSonTo($location)
    {
        // check if the location is exists and load it if exsits
        if (! ($node = static::find($location))) {
            throw new TreeException("Error: The location `".$location."` not found in this tree.", 1);
        }
        // make new child location
        $current_location = $this->location;
        // move the pointer to the loaded node
        $new_location = (! $last_child = $this->lastChild())
                      ? $current_location.Location::SEPARATOR.Location::firstPossibleSegment()
                      : Location::nextSibling($last_child->location);

        $table = config('tree.nodes_table.name');
        $statement =
            "UPDATE `".$table."`
            SET `location` = CONCAT('".$new_location."' , SUBSTRING(location FROM ".(strlen($current_location) + 1)."))
            WHERE `tree_id` = ".$this->tree_id."
            AND   `location` like '".$current_location."%'";

        DB::update($statement);

        return $this;
    }

    /**
     * Create father for the root node in the tree
     *
     * @param array $data data for the new father
     * @return mixed
     */
    public function createFather($data)
    {
        if (! $this->isRoot()) {
            throw new TreeException("Error: Can't make father for node:[ ".$this->location." ]. node should be root to make father for it.", 1);
        }

        DB::beginTransaction();

        try {
            // update all nodes location in this tree
            // first prepend all locations with sepataror '.'
            $affected_rows = static::tree($this->tree_id)
            ->update(['location' => DB::raw(' CONCAT("'.Location::SEPARATOR.'", `location`) ')]);
            // then prepend all locations with 'aaa'
            $affected_rows = static::tree($this->tree_id)
            ->update(['location' => DB::raw(' CONCAT("'.Location::firstPossibleSegment().'", `location`) ')]);

            // create th new root node with given data
            $this->createNewNode($data, Location::firstPossibleSegment());

            DB::commit();

            return $affected_rows;
        } catch (\Throwable $th) {
            // Rollback the database changes
            DB::rollBack();

            throw new TreeException("Error: ".$th->getMessage(), 1);
        }
    }

    /**
     * Get all siblings those are older than this node
     *
     */
    public function husband()
    {
        if ($this->gender == 'm') {
            return null;
        }
        // return $this->tree($this->tree_id)
        //             ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
        //             ->locationNot($this->location)
        //             ->where('location', '<', $this->location)
        //             ->get();
    }

    /**
     * Save new node as root in the tree
     *
     * @param
     */
    public function saveAsRoot()
    {
        $this->location = Location::firstPossibleSegment();

        return $this->save();
    }

    /**
     * Delete the node, and move
     * all its children to be children for the its father.
     *
     * @return int
     */
    public function deleteAndShiftChildren()
    {
        // to prevente the model observer from deleting children
        $this->shift_children = true;
        //
        //
        //
        //
    }

    /**
     * Generate Tree Html code from this node
     *
     * @return string html code for the tree from this node
     */
    public function toTree()
    {
        $tree = Tree::find($this->tree_id);
        $tree->pointer()->to($this);

        return $tree->draw();
    }

    /**
     * Generate Tree Html code from this node
     *
     * @return string html code for the tree from this node
     */
    public function toHtml()
    {
        return $this->toTree();
    }

    /**
     * Generate Tree Html code from this node
     *
     * @return string html code for the tree from this node
     */
    public function draw()
    {
        return $this->toTree();
    }
}
