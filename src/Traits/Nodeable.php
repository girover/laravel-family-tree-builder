<?php

namespace Girover\Tree\Traits;

use Girover\Tree\Database\Eloquent\NodeEloquentBuilder;
use Girover\Tree\Database\Sql\Update;
use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\GlobalScopes\OrderByLocationScope;
use Girover\Tree\GlobalScopes\WivesEagerRelationScope;
use Girover\Tree\Helpers\TreeHelpers;
use Girover\Tree\Location;
use Girover\Tree\Models\Node;
use Girover\Tree\NodeRelocator;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isNull;

/**
 *
 */
trait Nodeable
{
    /**
     * to control deleting children or not when deleting a node
     * false: delete node with its children
     * true: delete only the node, but move its children to be children for its father 
     *
     * @var bool
     */
    protected $move_children_on_deleting = false;

    /**
     * {@inheritdoc}
     *
     * @param \Illuminate\Database\Query\Builder
     * @return \Girover\Tree\Database\TreeQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return (new NodeEloquentBuilder($query))->leftJoin('nodes', 'nodes.nodeable_id', (new static)->getTable().'.'.$this->getKeyName());
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
        // static::addGlobalScope(new ImagesEagerRelationScope());

        static::saving(function ($model) {
            // dd($model);
        });

        // When model is deleted
        static::deleted(function ($node) {

            // if move_children_on_deleting property is false
            // then delete all children of the deleted node, otherwise delete only the node
            if ($node->move_children_on_deleting === false) {
                return $node->deleteChildren();
            }

            return $node->moveChildrenTo($node->father());
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
        //$this->fillable = array_merge($this->fillable, static::$fillable_cols);
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

    // NEW
    /**
     * To check if the nodeable model is connected with a node
     * 
     * @return bool
     */
    public function isNode()
    {
        return ($this->location && $this->treeable_id && $this->nodeable_id && $this->node_id) ? true : false;
        // return $this->node() ? true : false;
    }

    /**
     * Get partners of the node
     * if true is provided, get divorced partners with
     * if false is provided, don't get divorced partners with
     *
     * @param bool $with_divorced
     * @return \Illuminate\Database\Eloquent\Collection
     * 
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function partners()
    {
        // get wives
        if ($this->isMale()) {
            return  $this->wives()->get();
        }
        
        // get husbands
        return $this->husband()->get();
    }

    /**
     * Get the tree that this node belongs.
     *
     * @param Illuminate\Database\Eloquent\Model
     * @return \Girover\Tree\Models\Tree
     */
    public function getTree()
    {
        if (!$this->isNode()) {
            throw new TreeException("This model is not a node yet!", 1);
        }

        return (TreeHelpers::treeableModel())::find($this->treeable_id);
    }

    /**
     * Relationship for Getting wives og the node.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wives()
    {
        return  $this->belongsToMany(get_class(), 'marriages', 'nodeable_husband_id', 'nodeable_wife_id')
                     ->withPivot('nodeable_husband_id', 'nodeable_wife_id', 'divorced');
    }

    /**
     * Get husband of the node
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsToMany
     * 
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function husband()
    {
        return $this->belongsToMany(get_class(), 'marriages', 'nodeable_wife_id', 'nodeable_husband_id')
                    ->withPivot('nodeable_husband_id', 'nodeable_wife_id', 'divorced');
    }

    /**
     * assign a wife to this node
     *
     * @param \Girover\Tree\Models\Node $wife
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getMarriedWith(self $wife)
    {
        // only male nodes allowed to do this
        if ($this->isFemale()) {
            throw new TreeException("Only men are allowed to use this".__METHOD__, 1);
        }

        // Person cannot get married with himself
        if ($wife->isMale()) {
            throw new TreeException("Man is not allowed to get married with a man ".__METHOD__, 1);
        }

        // Person already married with the given woman
        $mar = $this->wives()->where('nodeable_wife_id', $wife->id)
                             ->where('nodeable_husband_id', $this->id)
                             ->first();
        if (! is_null($mar)) {            
            throw new TreeException('"'.$this->name.'" is already married with "'.$wife->name.'"', 1);    
        }

        // Person cannot get married with himself
        if ($this->id === $wife->getKey()) {            
            throw new TreeException('Person cannot get married with himself', 1);    
        }
        
        return $this->wives()->attach($wife->getKey());
    }

    // NEW
    /**
     * Check if nodeable is married with the given nodeable
     * @param Illuminate\Database\Eloquent\Model
     * 
     * @return bool 
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function isMarriedWith(self $wife)
    {
        if ($this->isMale()) {

            if ($wife->isMale()) {
                throw new TreeException("Man Can not be married with a man", 1);
            }

            return (bool)$this->wives()->where('marriages.nodeable_wife_id', $wife->getKey())
                                       ->count();
        }

        if ($wife->isFemale()) {
            throw new TreeException("Woman Can not be married with a woman", 1);
        }

        return (bool)$this->husband()->where('nodeable_husband_id', $wife->getKey())
                                   ->count();
    }

    /**
     * Divorce
     *
     * @param \Girover\Tree\Models\Node $node
     * @return int
     */
    public function divorce(self $wife)
    {
        // only women will be divorced
        if ($wife->isMale()) {
            throw new TreeException($wife->name." is not a woman to be divorced from a man", 1);
        }

        // only men are allowed to divorce
        if ($this->isFemale()) {
            throw new TreeException("This is a woman, however only men are allowed to divorce", 1);
        }
        return $this->wives()->where('nodeable_wife_id', $wife->getKey())->update(['divorced'=> true]);
    }

    /**
     * Relationship for Getting images of the node.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    // public function images()
    // {
    //     $model = DBHelper::nodeImageModel();

    //     return $this->hasMany($model, 'node_id', 'id');
    // }

    /**
     * add photo to for node
     * @param \Illuminate\Http\UploadedFile $photo
     * @param string $name
     * @return bool
     */
    // public function newPhoto($photo, $name = '')
    // {
    //     $new_name = Photos::newName($photo, $name);

    //     if (Photos::store($photo, $new_name)) {
    //         $this->photo = $new_name;
            
    //         return $this->save();
    //     }

    //     return false;
    // }
    // NEW
    public function setPhoto(string $photo_name)
    {
        $this->photo = $photo_name;

        return $this->save();
    }

    /**
     * Determine if the node is Root in the tree
     *
     * @return bool
     */
    public function isRoot()
    {
        $this->throwExceptionIfNotNode();

        if (Location::isRoot($this->location)) {
            return true;
        }
        $father_location = Location::father($this->location);

        return (static::tree($this->treeable_id)->location($father_location)
                                                ->count())
                                                ? false
                                                : true;
    }

    /**
     * Determine if the node has parent in the tree
     *
     * @return bool
     */
    public function hasParent()
    {
        if (Location::isRoot($this->location)) {
            return false;
        }
        $father_location = Location::father($this->location);

        return (bool)static::tree($this->treeable_id)
                     ->location($father_location)
                     ->count();
    }

    /**
     * Determine if the node has children
     *
     * @return 
     */
    public function hasChildren()
    {
        return (bool)static::tree($this->treeable_id)
                     ->locationREGEXP(Location::childrenREGEXP($this->location))
                     ->count();
    }

    /**
     * Determine if the node has siblings
     *
     * @return bool
     */
    public function hasSiblings()
    {
        return (bool)static::tree($this->treeable_id)
                     ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                     ->count();
    }

    /**
     * Indicates which generation this node is
     *
     * @return int | NULL
     */
    public function generation()
    {
        if (!$this->isNode()) {
            throw new TreeException("This model is not a node yet!!!", 1);
            
        }
        return Location::generation($this->location);
    }

    /**
     * Get the Root of this node
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function root()
    {
        return static::tree($this->treeable_id)
                     ->location(Location::firstPossibleSegment())
                     ->first();
    }

    /**
     * Get the father of this node
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function father()
    {
        // can not get father of the root
        if ($this->isRoot()) {
            throw new TreeException("Root of a tree has no father", 1);            
        }

        return static::tree($this->treeable_id)
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
        $this->throwExceptionIfNotNode();

        return static::tree($this->treeable_id)
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

        return static::tree($this->treeable_id)
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
        return static::tree($this->treeable_id)
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
        return static::tree($this->treeable_id)
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
        return static::tree($this->treeable_id)
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
        return static::tree($this->treeable_id)
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
        return static::tree($this->treeable_id)
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

        return static::tree($this->treeable_id)
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
        return static::tree($this->treeable_id)
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
        return  static::tree($this->treeable_id)
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
        return  static::tree($this->treeable_id)
                     ->locationREGEXP(Location::childrenREGEXP($this->location))
                     ->locationOrder('DESC')
                     ->first();
    }

    // NEW
    /**
     * To get child that has the given order in the family
     * 
     * @param int $order
     * 
     * @return \Illuminate\Database\Eloquent\Model|null 
     */
    public function child(int $order)
    {
        $children = $this->children();

        if ($order < 1 || $order > $children->count()) {
            return null;
        }

        return $children[$order - 1];
    }

    /**
     * make a query for getting siblings
     *
     * @param string $gender m|f
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    protected function siblingsQuery()
    {
        return static::tree($this->treeable_id)
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
     * getting all sibling of the node including the node itself
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function withSiblings()
    {
        return static::tree($this->treeable_id)
                     ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                     ->get();
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
        return static::tree($this->treeable_id)
                     ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                     ->get();
    }

    /**
     * Get the next sibling that is younger than this node
     *
     * @return \Girover\Tree\Models\Node
     */
    // public function nextSibling()
    public function youngerSibling()
    {
        return $this->siblingsQuery()
                    ->locationAfter($this->location)
                     ->first();
    }

    /**
     * Get all siblings who are younger than this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    // public function nextSiblings()
    public function youngerSiblings()
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
    // public function nextBrother()
    public function youngerBrother()
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
    // public function nextBrothers()
    public function youngerBrothers()
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
    // public function nextSister()
    public function youngerSister()
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
    // public function nextSisters()
    public function youngerSisters()
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
    // public function prevSibling()
    public function olderSibling()
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
    // public function prevSiblings()
    public function olderSiblings()
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
    // public function prevBrother()
    public function olderBrother()
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
    // public function prevBrothers()
    public function olderBrothers()
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
    // public function prevSister()
    public function olderSister()
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
    // public function prevSisters()
    public function olderSisters()
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
    public function createNewNode($data, $location, $gender = 'm')
    {
        Location::validate($location);

        $this->validateGender($gender);

        try {
            DB::beginTransaction();

            $nodeable = $this->createNodeable($data) ?? throw new TreeException("Failed to insert data", 1);
            
            $node     = $this->createNode(['nodeable_id'=>$nodeable->getKey(), 'treeable_id'=>$this->treeable_id, 'location'=>$location, 'gender'=>$gender]);

            // if(!$nodeable || !$node){
            if(!$node){
                throw new TreeException("Failed to create the node", 1);               
            }

            DB::commit();

            return true;

        } catch (\Throwable $th) {            
            DB::rollBack();
            throw $th;            
        }
    }

    // NEW
    public function createNodeable($data)
    {
        if ($data instanceof static) {

            // $data is a model and exists in database
            if ($data->exists){
                return $data;
            }

            $data->save();

            return $data;
        }

        if (! is_array($data)) {
            throw new TreeException("Bad argument type. The argument passed to ".__METHOD__." must be an array or an instance of [".static::class."]. ".gettype($data)." is given", 1);
        }

        return static::create($data);
    }

    // NEW
    public function createNode($data)
    {
        return Node::create($data);
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
     * Generating location for new child for this node.
     * 
     * @return string
     */
    protected function newChildLocation()
    {
        $last_child = $this->lastChild();

        if ($last_child == null) {
            return Location::firstChild($this->location);
        } 

        return Location::generateNextLocation($last_child->location);        
    }

    /**
     * Generating location for new child for this node.
     * 
     * @param \Girover\Tree\Models\Node
     * @return string
     */
    protected function newChildLocationFor($node)
    {
        $last_child = $node->lastChild();

        if ($last_child == null) {
            return Location::firstChild($node->location);
        } 

        return Location::generateNextLocation($last_child->location);        
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
        $new_child_location = $this->newChildLocation();

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
     * Updating the location of the node
     * this will update location for all the node's descendants too.
     * 
     * @return int
     */
    public function updateLocation($new_location)
    {
        return DB::update(Update::updateLocations($this->treeable_id, $this->location, $new_location));
    }

    /**
     * Move the node with its children
     * to be child of the given location
     * Both nodes should belong to same tree
     *
     * @param \Girover\Tree\Models\Node $node: location or node to move node to it
     * @return \Girover\Tree\Models\Node
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    protected function makeAsChildOf($node)
    {
        // Generate new location for this node
        $new_location = $this->newChildLocationFor($node);

        // Update locations of this node and its children in database
        $this->updateLocation($new_location);

        $this->location = $new_location;

        return $this;
    }
    /**
     * Move the node with its children
     * to be child of the given location
     * Both nodes should belong to same tree
     *
     * @param \Girover\Tree\Models\Node|string $location: location or node to move node to it
     * @return \Girover\Tree\Models\Node
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function moveTo($location)
    { 
        $node = $this->getNodeOrThrowException($location);
        // Not allowed to add children to female nodes.
        if ($node->isFemale()) {
            throw new TreeException("Error: Not allowed to add children to female nodes.", 1);
        }
        // Not allowed to move children from an ancestor to a descendant.
        if ($this->isAncestorOf($node)) {
            throw new TreeException("Error: Not allowed to move children from an ancestor to a descendant.", 1);
        }
        // Not allowed to move a node to its father.
        if ($node->isFatherOf($this)) {
            throw new TreeException("Error: Not allowed to move a node to its father.", 1);
        }

        return $this->makeAsChildOf($node);
    }

    /**
     * Move all children of the node to be children of
     * the given node or the node that has the given location.
     *
     * @param \Girover\Tree\Models\Node|string $location
     * @return \Girover\Tree\Models\Node
     */
    public function moveChildrenTo($location = null)
    {
        $node = $this->getNodeOrThrowException($location);
        // Not allowed to add children to female nodes.
        if ($node->isFemale()) {
            throw new TreeException("Error: Not allowed to add children to female nodes.", 1);
        }
        // Not allowed to move children from an ancestor to a descendant.
        if ($this->isAncestorOf($node)) {
            throw new TreeException("Error: Not allowed to move children from an ancestor to a descendant.", 1);
        }
        
        $children = $this->children();
        
        DB::beginTransaction();
        try {
            foreach ($children as $child) {
                $child->moveTo($location);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();            
            throw new TreeException("An error occurred during moving children", 1);            
        }

        return $this;
    }

    /**
     * To get a node from a location
     * Or to check if the given parameter is a node then return it
     * 
     * @param \Girover\Tree\Models\Node|string $location
     * @return \Girover\Tree\Models\Node
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    protected function getNodeOrThrowException($location)
    {
        if ($location instanceof static) {
            return $location;
        }

        $node = static::tree($this->treeable_id)->location($location)->first();

        if ($node === null) {
            throw new TreeException("Error: The location `".$location."` not found in this tree.", 1);
        }

        return $node;
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
            DB::update(Update::prependLocationsWithSeparator(), [$this->treeable_id]);
            // then prepend all locations with 'aaa'
            DB::update(Update::prependLocationsWithFirstPossibleSegment(), [$this->treeable_id]);

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
     * Determine if the node is an ancestor of the given node.
     *
     * @param \Girover\Tree\Models\Node
     * @return bool
     */
    public function isAncestorOf($node)
    {
        return Location::areAncestorAndChild($this->location, $node->location);
    }

    /**
     * Determine if the node is the father of the given node.
     *
     * @param \Girover\Tree\Models\Node
     * @return bool
     */
    public function isFatherOf($node)
    {
        if ((! $node instanceof static) || ($this->treeable_id !== $node->treeable_id)) {
            return false;
        }

        return Location::areFatherAndChild($this->location, $node->location);
    }

    /**
     * Determine if the node is the child of the given node.
     *
     * @param \Girover\Tree\Models\Node
     * @return bool
     */
    public function isChildOf($node)
    {
        if ((! $node instanceof static) || ($this->treeable_id !== $node->treeable_id)) {
            return false;
        }

        return Location::areFatherAndChild($node->location, $this->location);
    }

    /**
     * Determine if the node is the child of the given node.
     *
     * @return bool
     */
    public function isSiblingOf($node)
    {
        if ((! $node instanceof static) || ($this->treeable_id !== $node->treeable_id)) {
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
     * Determine if the node is a male node.
     *
     * @return bool
     */
    public function isMale()
    {
        return ($this->gender === 'm') ? true : false;
    }

    /**
     * Determine if the node is a female node.
     *
     * @return bool
     */
    public function isFemale()
    {
        return ($this->gender === 'f') ? true : false;
    }

    /**
     * Delete all children of this node
     *
     * @return int
     */
    public function deleteChildren()
    {
        return static::tree($this->treeable_id)
                     ->locationNot($this->location)
                     ->where('location', 'like', $this->location.'%')
                     ->delete();
    }

    /**
     * To make sure that when a node will be deleted
     * so its children will not
     * but they will move to be children of the father
     * of the deleted node
     * 
     * @return \Girover\Tree\Models\Node
     */
    public function moveChildren()
    {
        $this->move_children_on_deleting = true;

        return $this;
    }

    /**
     * To change the location of a node
     * to be after the given node
     * 
     * @param \Girover\Tree\Models\Node $node
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function moveAfter($node)
    {
        // Move the node after its sibling
        NodeRelocator::moveAfter($this, $node);
    }

    /**
     * To change the location of a node
     * to be after the given node
     * 
     * @param \Girover\Tree\Models\Node $node
     */
    public function moveBefore($node)
    {
        // Move the node after its sibling
        NodeRelocator::moveBefore($this, $node);  
    }

    /**
     * Generate Tree Html code from this node
     *
     * @return string html code for the tree from this node
     */
    public function toTree()
    {
        $this->throwExceptionIfNotNode();

        $tree = (TreeHelpers::treeableModel())::find($this->treeable_id);
        
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

    // NEW
    /**
     * Getting the node that this nodeable is connected with
     * 
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function node()
    {
        return $this->hasOne(Node::class, 'nodeable_id', $this->getKeyName())->first();
    }
    // NEW
    /**
     * Throw an exception when trying to get som of these
     * methods if the nodeable model is not connected with a node yet
     * 
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function throwExceptionIfNotNode()
    {
        if (!$this->isNode()) {
            throw new TreeException("This model is not a node yet!!!", 1);            
        }
    }
}
