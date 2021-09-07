<?php

namespace Girover\Tree\Traits;

use Girover\Tree\Database\Eloquent\NodeCollection;
use Girover\Tree\Database\Eloquent\NodeEloquentBuilder;
use Girover\Tree\Database\Sql\Update;
use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\GlobalScopes\ImagesEagerRelationScope;
use Girover\Tree\GlobalScopes\OrderByLocationScope;
use Girover\Tree\GlobalScopes\WivesEagerRelationScope;
use Girover\Tree\Location;
use Girover\Tree\Models\Node;
use Girover\Tree\Models\Tree;
use Illuminate\Support\Facades\DB;

/**
 *
 */
trait Nodeable
{
    /**
     * mass assignment fillable properties
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
     * {@inheritdoc}
     *
     * @param \Illuminate\Database\Query\Builder
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

            // if the model has shift_children property is not sat
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wives()
    {
        $pivot = (config('node_relationships.wives.pivot'))??'marriages';

        return $this->belongsToMany(get_class(), $pivot, 'node_husband_id', 'node_wife_id')
                    ->withPivot('node_husband_id', 'node_wife_id', 'date_of_marriage', 'marriage_desc');
    }

    /**
     * assign a wife to this node
     *
     * @param \Girover\Tree\Models\Node $node
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getMarriedWith($node)
    {
        if (! $node instanceof static) {
            throw new TreeException("Parameter passed to [".__METHOD__."] should be instance of [".get_class($this)."]", 1);
        }
        
        $this->wives()->attach($node->id,['date_of_marriage'=>$this->date_of_marriage]
        );
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
     * Indicates which generation this node is
     *
     * @return int | NULL
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
     * make a query start for getting fathers siblings
     *
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    protected function siblingsOfFatherQuery()
    {
        return static::tree($this->tree_id)
                    ->locationNot(Location::father($this->location))
                    ->locationREGEXP(Location::withSiblingsREGEXP(Location::father($this->location)));
    }

    /**
     * Get all uncles and aunts of this node.
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    protected function siblingsOfFather()
    {
        return static::tree($this->tree_id)
                    ->locationNot(Location::father($this->location))
                    ->locationREGEXP(Location::withSiblingsREGEXP(Location::father($this->location)))
                    ->get();
    }

    /**
     * Get all uncles of this node
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function uncles()
    {
        return $this->siblingsOfFatherQuery()->where('gender', 'm')->get();
    }

    /**
     * Get all aunts of this node
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function aunts()
    {
        return $this->siblingsOfFatherQuery()->where('gender', 'f')->get();
    }

    /**
     * make a query start for getting children
     *
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    protected function childrenQuery()
    {
        return static::tree($this->tree_id)
                    ->locationREGEXP(Location::childrenREGEXP($this->location));
    }

    /**
     * Get all children of this node.
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function children()
    {
        return $this->childrenQuery()->get();
    }

    /**
     * Get all direct sons of this node
     * without descendants
     *
     * @param \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function sons()
    {
        return $this->childrenQuery()->where('gender', 'm')->get();
    }

    /**
     * Get all direct daughters of this node
     * without descendants
     *
     * @param \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function daughters()
    {
        return $this->childrenQuery()->where('gender', 'f')->get();
    }

    /**
     * Count children of this node by gender.
     *
     * @return int
     */
    public function countChildren()
    {
        return $this->childrenQuery()->count();
    }

    /**
     * Count sons of this node.
     *
     * @return int
     */
    public function countSons()
    {
        return $this->childrenQuery()->where('gender', 'm')->count();
    }

    /**
     * Count daughters of this node.
     *
     * @return int
     */
    public function countDaughters()
    {
        return $this->childrenQuery()->where('gender', 'f')->count();
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
     * to get siblings of the node that has the given gender
     * 
     * @param string $gender 'm'|'f'
     * @return int
     */
    protected function countSiblingsByGender($gender)
    {
        return static::tree($this->tree_id)
                    ->locationNot($this->location)
                    ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                    ->where('gender', $gender)
                    ->count();
    }

    /**
     * Count siblings of this node by gender.
     *
     * @return int
     */
    public function countSiblings()
    {
        return $this->countAllSiblings();
    }

    /**
     * count brothers of this node.
     *
     * @return int
     */
    public function countBrothers()
    {
        return $this->countSiblingsByGender('m');
    }

    /**
     * count sisters of the node.
     *
     * @return int
     */
    public function countSisters()
    {
        return $this->countSiblingsByGender('f');
    }

    /**
     * make a query start for getting descendants
     *
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    protected function descendantsQuery()
    {
        return static::tree($this->tree_id)
                     ->locationNot($this->location)
                     ->where('location', 'like', $this->location.'%');
    }

    /**
     * getting all descendants fo the node
     * 
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function descendants()
    {
        return $this->descendantsQuery()->get();
    }

    /**
     * getting all male descendants fo the node
     * 
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function maleDescendants()
    {
        return $this->descendantsQuery()->where('gender', 'm')->get();
    }

    /**
     * getting all female descendants fo the node
     * 
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function femaleDescendants()
    {
        return $this->descendantsQuery()->where('gender', 'f')->get();
    }

    /**
     * Count all descendants of the node.
     *
     * @return int
     */
    protected function countAllDescendants()
    {
        return $this->descendantsQuery()->count();
    }

    /**
     * Count all descendants of the node by gender.
     *
     * @param string $gender 'm'|'f' the gender of sibling
     * @return int
     */
    protected function countDescendantsByGender($gender)
    {
        return $this->descendantsQuery()
                    ->where('gender', $gender)
                    ->count();
    }

    /**
     * Count all male descendants of this node.
     *
     * @return int
     */
    public function countDescendants()
    {
        return $this->countAllDescendants();
    }

    /**
     * Count all male descendants of this node.
     *
     * @return int
     */
    public function countMaleDescendants()
    {
        return $this->countDescendantsByGender('m');
    }

    /**
     * Count all female descendants oc this node.
     *
     * @return int
     */
    public function countFemaleDescendants()
    {
        return $this->countDescendantsByGender('f');
    }

    /**
     * Get the first child of this node
     *
     * @param \Girover\Tree\Models\Node
     */
    public function firstChild()
    {
        return  static::tree($this->tree_id)
                     ->where('location', 'REGEXP', Location::childrenREGEXP($this->location))
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
        return  static::tree($this->tree_id)
                     ->where('location', 'REGEXP', Location::childrenREGEXP($this->location))
                     ->orderBy('location', 'DESC')
                     ->first();
    }

    /**
     * make a query for getting siblings
     *
     * @param string $gender m|f
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    protected function siblingsQuery()
    {
        return static::tree($this->tree_id)
                    ->locationNot($this->location)
                    ->locationREGEXP(Location::withSiblingsREGEXP($this->location));
    }

    /**
     * getting all sibling of the node
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function siblings()
    {
        return $this->siblingsQuery()->get();
    }

    /**
     * getting all brothers of the node
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function brothers()
    {
        return $this->siblingsQuery()->where('gender', 'm')->get();
    }

    /**
     * getting all brothers of the node
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function sisters()
    {
        return $this->siblingsQuery()->where('gender', 'f')->get();
    }

    /**
     * Get all siblings of this node
     * including the node
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
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
     * @return \Girover\Tree\Models\Node
     */
    public function nextSibling()
    {
        return $this->siblingsQuery()
                    ->where('location', '>', $this->location)
                     ->first();
    }

    /**
     * Get all siblings those are younger than this node
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function nextSiblings()
    {
        return $this->siblingsQuery()
                    ->where('location', '>', $this->location)
                     ->get();
    }

    /**
     * Get the next brother that is younger than this node
     *
     * @return \Girover\Tree\Models\Node
     */
    public function nextBrother()
    {
        return $this->siblingsQuery()
                    ->where('location', '>', $this->location)
                    ->where('gender', 'm')
                     ->first();
    }

    /**
     * Get the all next brother who are younger than this node
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function nextBrothers()
    {
        return $this->siblingsQuery()
                    ->where('location', '>', $this->location)
                    ->where('gender', 'm')
                     ->get();
    }

    /**
     * Get the next sister that is younger than this node
     *
     * @return \Girover\Tree\Models\Node
     */
    public function nextSister()
    {
        return $this->siblingsQuery()
                    ->where('location', '>', $this->location)
                    ->where('gender', 'f')
                     ->first();
    }

    /**
     * Get the all next sisters who are younger than this node
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function nextSisters()
    {
        return $this->siblingsQuery()
                    ->where('location', '>', $this->location)
                    ->where('gender', 'f')
                     ->get();
    }

    /**
     * Get the previous sibling og this node.
     *
     * @return \Girover\Tree\Models\Node
     */
    public function prevSibling()
    {
        return $this->siblingsQuery()
                     ->withoutGlobalScope(OrderByLocationScope::class)
                     ->orderBy('location', 'desc')
                     ->where('location', '<', $this->location)
                     ->first();
    }

    /**
     * Get all siblings those are older than this node
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function prevSiblings()
    {
        return $this->siblingsQuery()
                    ->where('location', '<', $this->location)
                    ->get();
    }

    /**
     * Get the previous brother that is older than this node
     *
     * @return \Girover\Tree\Models\Node
     */
    public function prevBrother()
    {
        return $this->siblingsQuery()
                    ->withoutGlobalScope(OrderByLocationScope::class)
                    ->orderBy('location', 'desc')
                    ->where('location', '<', $this->location)
                    ->where('gender', 'm')
                     ->first();
    }

    /**
     * Get the all previous brothers who are older than this node
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function prevBrothers()
    {
        return $this->siblingsQuery()
                    ->where('location', '<', $this->location)
                    ->where('gender', 'm')
                     ->get();
    }

    /**
     * Get the previous sister that is older than this node
     *
     * @return \Girover\Tree\Models\Node 
     */
    public function prevSister()
    {
        return $this->siblingsQuery()
                    ->withoutGlobalScope(OrderByLocationScope::class)
                    ->orderBy('location', 'desc')
                    ->where('location', '<', $this->location)
                    ->where('gender', 'f')
                     ->first();
    }

    /**
     * Get the all previous sisters who are older than this node
     *
     * @return \Girover\Tree\Database\Eloquent\NodeCollection
     */
    public function prevSisters()
    {
        return $this->siblingsQuery()
                    ->where('location', '<', $this->location)
                    ->where('gender', 'f')
                     ->get();
    }

    /**
     * Get the first sibling of node that the Pointer is indicating to
     *
     * @return \Girover\Tree\Models\Node
     */
    public function firstSibling()
    {
        return  $this->siblingsQuery()
                     ->first();
    }

    /**
     * Get last sibling of node that the Pointer is indicating to
     *
     * @return \Girover\Tree\Models\Node
     */
    public function lastSibling()
    {
        return  $this->siblingsQuery()
                     ->withoutGlobalScope(OrderByLocationScope::class)
                     ->orderBy('location', 'desc')
                     ->first();
    }

    /**
     * Get the first brother of node that the Pointer is indicating to
     *
     * @return \Girover\Tree\Models\Node
     */
    public function firstBrother()
    {
        return  $this->siblingsQuery()
                     ->where('gender', 'm')
                     ->first();
    }

    /**
     * Get last brother of node that the Pointer is indicating to
     *
     * @return \Girover\Tree\Models\Node
     */
    public function lastBrother()
    {
        return  $this->siblingsQuery()
                     ->withoutGlobalScope(OrderByLocationScope::class)
                     ->orderBy('location', 'desc')
                     ->where('gender', 'm')
                     ->first();
    }

    /**
     * Get the first sister of node that the Pointer is indicating to
     *
     * @return \Girover\Tree\Models\Node
     */
    public function firstSister()
    {
        return  $this->siblingsQuery()
                     ->where('gender', 'f')
                     ->first();
    }

    /**
     * Get last sister of node that the Pointer is indicating to
     *
     * @return \Girover\Tree\Models\Node
     */
    public function lastSister()
    {
        return  $this->siblingsQuery()
                     ->withoutGlobalScope(OrderByLocationScope::class)
                     ->orderBy('location', 'desc')
                     ->where('gender', 'f')
                     ->first();
    }

    /**
     * Create new node
     *
     * @param \Girover\Tree\Models\Node|array $data data for the new node
     * @param string $location the location of the new [child|sibling|...]
     * @param string $gender the gender of the person
     * 
     * @throws \Girover\Tree\Exceptions\TreeException
     * @return \Girover\Tree\Models\Node
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

        if (empty($data)) {
            throw new TreeException("No data for the new node are provided ", 1);
        }

        if (! key_exists('name', $data)) {
            throw new TreeException("The name of person is not provided", 1);
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
     * @param string $gender 'm'|'f'
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
    public function makeAsSonOf($location)
    {
        // get the node that should be father of this node
        if (! ($node = static::tree($this->tree_id)->location($location)->first())) {
            throw new TreeException("Error: The location `".$location."` not found in this tree.", 1);
        }
        // Generate new location for this node
        $new_location = (! $last_child = $node->lastChild())
                      ? $node->location.Location::SEPARATOR.Location::firstPossibleSegment()
                      : Location::nextSibling($last_child->location);

        // Update locations of this node and its children in database
        DB::update(Update::changeLocations($this->tree_id, $this->location, $new_location));

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
            // update all nodes locations in this tree
            // first prepend all locations with separator '.'
            // tp prevent duplicated locations in same tree
            DB::update(Update::prependLocationsWithSeparator(), [$this->tree_id]);
            // then prepend all locations with 'aaa'
            DB::update(Update::prependLocationsWithFirstPossibleSegmetn(), [$this->tree_id]);

            // create th new root node with given data
            $new_root = $this->createNewNode($data, Location::firstPossibleSegment());

            DB::commit();

            return $new_root;
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
        // to prevent the model observer from deleting children
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
