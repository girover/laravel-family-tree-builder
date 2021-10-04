<?php

namespace Girover\Tree\Traits;

use Girover\Tree\Database\Eloquent\NodeEloquentBuilder;
use Girover\Tree\Database\Sql\Update;
use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\GlobalScopes\ImagesEagerRelationScope;
use Girover\Tree\GlobalScopes\OrderByLocationScope;
use Girover\Tree\GlobalScopes\WivesEagerRelationScope;
use Girover\Tree\Helpers\DBHelper;
use Girover\Tree\Helpers\Photos;
use Girover\Tree\Location;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isNull;

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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    // public function newCollection(array $models = [])
    // {
    //     return new NodeCollection($models);
    // }

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

    public function scopeIgnoreDivorced($query)
    {
        return $query->where('divorced', false);
    }

    /**
     * Determine if the provider gender
     * is a valid gender
     * @param string $gender
     * @return void
     * @throws Girover\Tree\Exceptions\TreeException
     */
    protected function validateGender($gender)
    {
        if ($gender !== 'm' && $gender !== 'f') {
            throw new TreeException("Invalid gender is provided", 1);            
        }
    }

    /**
     * Get partners of the node
     * if true is provided, get divorced partners with
     * if false is provided, don't get divorced partners with
     *
     * @param bool $with_divorced
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * 
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    protected function partners()
    {
        $pivot = DBHelper::marriageTable();

        // get wives
        if ($this->gender == 'f') {
            return  $this->belongsToMany(get_class(), $pivot, 'wife_id', 'husband_id')
                              ->withPivot('husband_id', 'wife_id', 'date_of_marriage', 'marriage_desc');
        }
        
        // get husbands
        return $this->belongsToMany(get_class(), $pivot, 'husband_id', 'wife_id')
                         ->withPivot('husband_id', 'wife_id', 'date_of_marriage', 'marriage_desc');
    }

    /**
     * Get the tree that this node belongs.
     *
     * @return \Girover\Tree\Models\Tree
     */
    public function getTree()
    {
        return DBHelper::treeModel()::find($this->tree_id);
    }

    /**
     * Relationship for Getting wives og the node.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wives()
    {
        if ($this->gender == 'f') {
            throw new TreeException("Woman cannot have wives", 1);            
        }

        return $this->partners();
    }

    /**
     * Get husband of the node
     *
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function husband()
    {
        if ($this->gender == 'm') {
            throw new TreeException("Man cannot have husband", 1);            
        }

        return $this->partners();
    }

    /**
     * assign a wife to this node
     *
     * @param \Girover\Tree\Models\Node $wife
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getMarriedWith($wife, $data=[])
    {
        if (! $wife instanceof static) {
            throw new TreeException("Parameter passed to [".__METHOD__."] should be instance of [".get_class($this)."]", 1);
        }

        // only male nodes allowed to do this
        if ($this->gender == 'f') {
            throw new TreeException($this->name." is a woman, and only men allowed to use ".__METHOD__, 1);
        }

        // Person cannot get married with himself
        if ($wife->gender == 'm') {
            throw new TreeException("Man is not allowed to get married with a man ".__METHOD__, 1);
        }

        // the person is already married with given wife
        $mar = $this->wives()->where('wife_id', $wife->id)
                             ->where('husband_id', $this->id)
                             ->first();
        if (! is_null($mar)) {            
            throw new TreeException('"'.$this->name.'" is already married with "'.$wife->name.'"', 1);    
        }

        // Person cannot get married with himself
        if ($this->id === $wife->id) {            
            throw new TreeException('Person cannot get married with himself', 1);    
        }
        
        if (empty($data)) {
            return $this->wives()->attach($wife->id);
        }

        $marriage_info['date_of_marriage'] = isset($data['date_of_marriage']) ?$data['date_of_marriage']: null;
        $marriage_info['marriage_desc'] = isset($data['marriage_desc']) ?$data['marriage_desc']: null;

        return $this->wives()->attach($wife->id,$marriage_info);
    }

    /**
     * Divorce
     *
     * @param \Girover\Tree\Models\Node $node
     * @return int
     */
    public function divorce($wife)
    {
        if (! $wife instanceof static) {
            throw new TreeException("Parameter passed to [".__METHOD__."] should be instance of [".get_class($this)."]", 1);
        }

        // only women will be divorced
        if ($wife->gender == 'm') {
            throw new TreeException($wife->name." is not a woman to be divorced from a man", 1);
        }

        // only men are allowed to divorce
        if ($this->gender == 'f') {
            throw new TreeException($this->name." is a woman, but only men are allowed to divorce", 1);
        }
        return $this->wives()->where('wife_id', $wife->id)->update(['divorced'=> true]);
    }

    /**
     * Relationship for Getting images of the node.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function images()
    {
        $model = DBHelper::nodeImageModel();

        return $this->hasMany($model, 'node_id', 'id');
    }

    /**
     * add photo to for node
     * @param \Illuminate\Http\UploadedFile $photo
     * @param string $name
     * @return bool
     */
    public function newPhoto($photo, $name = '')
    {
        $new_name = Photos::newName($photo, $name);

        if (Photos::store($photo, $new_name)) {
            $this->photo = $new_name;
            
            return $this->save();
        }

        return false;
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
     * @return \Girover\Tree\Models\Node
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function father()
    {
        // can not get father of the root
        if ($this->isRoot()) {
            throw new TreeException("Root of a tree has no father", 1);
            
        }

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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function ancestors()
    {
        return static::tree($this->tree_id)
                     ->locationNot($this->location)
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
     * @return \Illuminate\Database\Eloquent\Collection
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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function uncles()
    {
        return $this->siblingsOfFatherQuery()->male()->get();
        // return $this->siblingsOfFatherQuery()->where('gender', 'm')->get();
    }

    /**
     * Get all aunts of this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function aunts()
    {
        return $this->siblingsOfFatherQuery()->female()->get();
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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function children()
    {
        return $this->childrenQuery()->get();
    }

    /**
     * Get all direct sons of this node
     * without descendants
     *
     * @param \Illuminate\Database\Eloquent\Collection
     */
    public function sons()
    {
        return $this->childrenQuery()->male()->get();
    }

    /**
     * Get all direct daughters of this node
     * without descendants
     *
     * @param \Illuminate\Database\Eloquent\Collection
     */
    public function daughters()
    {
        return $this->childrenQuery()->female()->get();
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
        return $this->childrenQuery()->male()->count();
    }

    /**
     * Count daughters of this node.
     *
     * @return int
     */
    public function countDaughters()
    {
        return $this->childrenQuery()->female()->count();
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
        $this->validateGender($gender);

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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function descendants()
    {
        return $this->descendantsQuery()->get();
    }

    /**
     * getting all male descendants fo the node
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function maleDescendants()
    {
        return $this->descendantsQuery()->male()->get();
    }

    /**
     * getting all female descendants fo the node
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function femaleDescendants()
    {
        return $this->descendantsQuery()->female()->get();
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
        $this->validateGender($gender);

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
                     ->locationREGEXP(Location::childrenREGEXP($this->location))
                     ->locationOrder('ASC')
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
                     ->locationREGEXP(Location::childrenREGEXP($this->location))
                     ->locationOrder('DESC')
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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function siblings()
    {
        return $this->siblingsQuery()->get();
    }

    /**
     * getting all brothers of the node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function brothers()
    {
        return $this->siblingsQuery()->male()->get();
    }

    /**
     * getting all brothers of the node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function sisters()
    {
        return $this->siblingsQuery()->female()->get();
    }

    /**
     * Get all siblings of this node
     * including the node
     *
     * @return \Illuminate\Database\Eloquent\Collection
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
                    ->locationAfter($this->location)
                     ->first();
    }

    /**
     * Get all siblings those are younger than this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nextSiblings()
    {
        return $this->siblingsQuery()
                    ->locationAfter($this->location)
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
                    ->locationAfter($this->location)
                    ->male()
                     ->first();
    }

    /**
     * Get the all next brother who are younger than this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nextBrothers()
    {
        return $this->siblingsQuery()
                    ->locationAfter($this->location)
                    ->male()
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
                    ->locationAfter($this->location)
                    ->female()
                     ->first();
    }

    /**
     * Get the all next sisters who are younger than this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nextSisters()
    {
        return $this->siblingsQuery()
                    ->locationAfter($this->location)
                    ->female()
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
                     ->locationBefore($this->location)
                     ->locationOrder('desc')
                     ->first();
    }

    /**
     * Get all siblings those are older than this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function prevSiblings()
    {
        return $this->siblingsQuery()
                    ->locationBefore($this->location)
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
                    ->locationBefore($this->location)
                    ->male()
                    ->locationOrder('desc')
                     ->first();
    }

    /**
     * Get the all previous brothers who are older than this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function prevBrothers()
    {
        return $this->siblingsQuery()
                    ->locationBefore($this->location)
                    ->male()
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
                    ->locationBefore($this->location)
                    ->female()
                    ->locationOrder('desc')
                     ->first();
    }

    /**
     * Get the all previous sisters who are older than this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function prevSisters()
    {
        return $this->siblingsQuery()
                    ->locationBefore($this->location)
                    ->female()
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
                     ->locationOrder('desc')
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
                     ->male()
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
                     ->male()
                     ->locationOrder('desc')
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
                     ->female()
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
                     ->female()
                     ->locationOrder('desc')
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
        $this->validateGender($gender);

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
     * Both nodes should belong to same tree
     *
     * @param string $location: location to move node to it
     * @return \Girover\Tree\Models\Node
     * @throws \Girover\Tree\Exceptions\TreeException
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
            // update all nodes locations in this tree.
            // Firstly prepend all locations with separator '.'
            // to prevent duplicated locations in same tree
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
     * Save new node as root in the tree
     *
     * @return \Girover\Tree\Models\Node|false
     */
    public function makeAsMainNode()
    {
        return $this->getTree()->setMainNode($this);
    }

    /**
     * Determine if the node is the father of the given node.
     *
     * @return bool
     */
    public function isFatherOf($node)
    {
        if ((! $node instanceof static) || ($this->tree_id !== $node->tree_id)) {
            return false;
        }

        return Location::areFatherAndSon($this->location, $node->location);
    }

    /**
     * Determine if the node is the child of the given node.
     *
     * @return bool
     */
    public function isChildOf($node)
    {
        if ((! $node instanceof static) || ($this->tree_id !== $node->tree_id)) {
            return false;
        }

        return Location::areFatherAndSon($node->location, $this->location);
    }

    /**
     * Determine if the node is the child of the given node.
     *
     * @return bool
     */
    public function isSiblingOf($node)
    {
        if ((! $node instanceof static) || ($this->tree_id !== $node->tree_id)) {
            return false;
        }

        return Location::areSiblings($this->location, $node->location);
    }

    /**
     * Determine if the node is the main node in its tree.
     *
     * @return bool
     */
    public function isMainNode()
    {
        $main_node = $this->getTree()->mainNode();

        return ($main_node) ? $this->location===$main_node->location : false;
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
        $tree = DBHelper::treeModel()::find($this->tree_id);
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
