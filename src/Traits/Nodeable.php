<?php

namespace Girover\Tree\Traits;

use Girover\Tree\GlobalScopes\EagerRelationshipsScope;
use Girover\Tree\GlobalScopes\ImagesEagerRelationScope;
use Girover\Tree\GlobalScopes\OrderByLocationScope;
use Girover\Tree\GlobalScopes\WivesEagerRelationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as Query;
use Girover\Tree\Database\Eloquent\TreeEloquentBuilder;
use Girover\Tree\Database\Eloquent\TreeCollection;
use Girover\Tree\EagerTree;
use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
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
     * Eager loading relationships
     * 
     * @var array // from config file tree.php
     */
    protected static $eager_relationships;

    /**
     * Eager loading relationships
     * 
     * @var array // from config file tree.php
     */
    // protected static $bound_relationships = [];

    /**
     * {@inheritdoc}
     * 
     * @param Illuminate\Databse\Query\Builder
     * @return App\Girover\Tree\TreeQueryBuilder
     */
    public function newEloquentBuilder($query)
    {        
        return new TreeEloquentBuilder($query);
    }

    /**
     * {@inheritdoc}
     * 
     * @return App\Girover\Tree\TreeCollection
     */
    public function newCollection(array $models = array())
    { 
        return new TreeCollection($models);
    }

    
    /**
     * Relationship for Getting wives og the node.
     * 
     * @return Girover\Tree\TreeCollection
     */
    public function wives()
    {  
        $model = isset(config('tree.eager_relationships')['wives']['model'])
                ? config('tree.eager_relationships')['wives']['model']
                : 'App\Models\Marriage';
        
        return $this->belongsToMany(get_class(), $model, 'node_husband_id','node_wife_id');
    }
    /**
     * Relationship for Getting images og the node.
     * 
     * @return Girover\Tree\TreeCollection
     */
    public function images()
    {  
        $model = isset(config('tree.eager_relationships')['images']['model'])
                ? config('tree.eager_relationships')['images']['model']
                : 'App\Models\images';
        
        return $this->hasMany($model, 'node_id','id');
    }

    public static function bootNodeable(){

        // Adding global scope o the model
        // static::addGlobalScope(new EagerRelationshipsScope);
        static::addGlobalScope(new OrderByLocationScope);
        static::addGlobalScope(new WivesEagerRelationScope);
        static::addGlobalScope(new ImagesEagerRelationScope);

        static::saving(function($model){
            // dd($model);
        });

        static::deleted(function($model){
            return static::where('location', 'like', $model->location.'%')
                         ->delete();
        });
    }

    /**
     * This method is called when new instance of Node is initialized
     * 
     * @return void
     */
    public function initializeNodeable(){

        // Adding mass assignment fields to fillable array
        $this->fillable = array_merge($this->fillable, static::$fillable_cols);
    }


    /**
     * Determine if the node is Root in the tree
     * 
     * @return boolean 
     */
    public function isRoot()
    { 
        if(Location::isRoot($this->location)){
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
     * @return App\Node | null
     */
    public function hasParent()
    {
        if(Location::isRoot($this->location)){
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
     * @return App\Node | null
     */
    public function hasChildren()
    {
        return static::tree($this->tree_id)
                     ->locationREGEXP(Location::childrenREGEXP($this->location))
                     ->count()
                     ?true:false;
    }


    /**
     * Get the Root og this node
     * 
     * @param App\Models\Node
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
     * @param App\Models\Node
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
     * @param App\Models\Node
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
     * @param App\Models\Node
     */
    public function ancestor($ancestor = null)
    {
        if($ancestor === null){
            return $this->father();
        }
        return static::tree($this->tree_id)
                     ->location(Location::ancestor($this->location, $ancestor))
                     ->first();
    }

    /**
     * Get all uncles and/or aunts of this node.
     * 
     * @return App\Girover\Tree\Database\Eloquent\TreeCollection
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
     * @param Illuminate\Support\TreeCollection
     * @return App\Girover\Tree\Database\Eloquent\TreeCollection
     */
    public function fatherSiblings($gender = null)
    {
        if(is_null($gender)){
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
     * @return App\Girover\Tree\Database\Eloquent\TreeCollection
     */
    public function uncles()
    {
        return $this->fatherSiblings('m');
    }

    /**
     * Get all aunts of this node
     * 
     * @return App\Girover\Tree\Database\Eloquent\TreeCollection
     */
    public function aunts()
    {
        return $this->fatherSiblings('f');
    }

    /**
     * Get all children of this node.
     * 
     * @return IApp\Girover\Tree\Database\Eloquent\TreeCollection
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
     * @param Illuminate\Support\TreeCollection
     */
    public function children($gender = null)
    {
        if(is_null($gender)){
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
     * @param Illuminate\Support\TreeCollection
     */
    public function sons()
    {
        return $this->children('m');
    }

    /**
     * Get all direct sons of this node
     * without descendants
     * 
     * @param Illuminate\Support\TreeCollection
     */
    public function daughters()
    {
        return $this->children('f');
    }

    /**
     * Get How many children this node has.
     * 
     * @return integer
     */
    protected function countAllChildren()
    {
        return static::tree($this->tree_id)
                    ->locationREGEXP(Location::childrenREGEXP($this->location))
                    ->count();
    }

    /**
     * Get How many children this node has.
     * 
     * @return integer
     */
    public function countChildren($gender = null)
    {
        if(is_null($gender)){
            return $this->countAllChildren();
        }

        return static::tree($this->tree_id)
                    ->locationREGEXP(Location::childrenREGEXP($this->location))
                    ->where('gender', $gender)
                    ->count();
    }

    /**
     * Get How many sons this node has.
     * 
     * @return integer
     */
    public function countSons()
    {
        return $this->countChildren('m');
    }

    /**
     * Get How many sons this node has.
     * 
     * @return integer
     */
    public function countDaughters()
    {
        return $this->countChildren('f');
    }

    /**
     * Get How many siblings this node does has.
     * 
     * @return integer
     */
    protected function countAllSiblings()
    {
        return static::tree($this->tree_id)
                    ->locationNot($this->location)
                    ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                    ->count();
    }

    /**
     * Get How many siblings this node does has.
     * 
     * @param '0'|'1'|null $gender the gender of sibling
     * @return integer
     */
    public function countSiblings($gender = null)
    {
        if(is_null($gender)){
            return $this->countAllSiblings();
        }

        return static::tree($this->tree_id)
                    ->locationNot($this->location)
                    ->locationREGEXP(Location::withSiblingsREGEXP($this->location))
                    ->where('gender', $gender)
                    ->count();
    }

    /**
     * Get How many brothers this node does has.
     * 
     * @return integer
     */
    public function countBrothers()
    {
        return $this->countSiblings('m');
    }

    /**
     * Get How many sisters this node does has.
     * 
     * @return integer
     */
    public function countSisters()
    {
        return $this->countSiblings('f');
    }

    /**
     * Get How many siblings this node does has.
     * 
     * @return integer
     */
    protected function countAllDescendants()
    {
        return static::tree($this->tree_id)
                        ->locationNot($this->location)
                        ->where('location', 'like', $this->location.'%')
                        ->count();
    }

    /**
     * Get How many Descendants this node does has.
     * 
     * @param '0'|'1'|null $gender the gender of sibling
     * @return integer
     */
    public function countDescendants($gender = null)
    {
        if(is_null($gender)){
            return $this->countAllDescendants();
        }

        return static::tree($this->tree_id)
                    ->locationNot($this->location)
                    ->where('location', 'like', $this->location.'%')
                    ->where('gender', $gender)
                    ->count();
    }

    /**
     * Get How many male descendants this node does has.
     * 
     * @return integer
     */
    public function countMaleDescendants()
    {
        return $this->countDescendants('m');
    }

    /**
     * Get How many female descendants this node does has.
     * 
     * @return integer
     */
    public function countFemaleDescendants()
    {
        return $this->countDescendants('f');
    }

    /**
     * Get the first child of this node
     * 
     * @param App\Models\Node
     */
    public function firstChild()
    {
        return (new EagerTree($this->tree_id))->pointer->silentlyTo($this)->firstChild();
    }

    /**
     * Get the last child of this node
     * 
     * @param App\Models\Node
     */
    public function lastChild()
    {
        return (new EagerTree($this->tree_id))->pointer->silentlyTo($this)->lastChild();
    }

    /**
     * Get all siblings of this node
     * without the node static
     * 
     * @param Illuminate\Support\TreeCollection
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
     * @param Illuminate\Support\TreeCollection
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
     * @param Illuminate\Support\TreeCollection
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
     * @param Illuminate\Support\TreeCollection
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
     * @param Illuminate\Support\TreeCollection
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
     * @param Illuminate\Support\TreeCollection
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
     * Create new node
     * 
     * @param array|Griover\Models\Node $data data for the new node
     * @param string $location the location of the new [child|sibling|...]
     */
    protected function createNewNode($data, $location, $gender = 'm')
    {
        Location::validate($location);
        if($data instanceof static){

            $data->tree_id  = $this->tree_id;
            $data->location = $location;
            $data->gender   = $gender;
            $data->save();

            return $data;
        }
        if(!is_array($data)){
            throw new TreeException("Bad argument type. The argument passed to ".__METHOD__." must be an array or an instance of [".static::class."]. ".gettype($data)." is given", 1);           
        }
        
        $data['tree_id']  = $this->tree_id;
        $data['location'] = $location;
        $data['gender']   = $gender;
        
        return static::create($data);
    }
    /**
     * Create new sibling for this node
     * by default the new node is son.
     * 
     * @param array|static data for the new sibling
     * @param string gender of the new sibling
     * @return App\Models\Node|null
     */
    public function newSibling($data, $gender = 'm')
    {
        $new_sibling_location = (new EagerTree($this->tree_id))->pointer->silentlyTo($this)->newSiblingLocation();
        Location::validate($new_sibling_location);

        return $this->createNewNode($data, $new_sibling_location, $gender);
    }
    /**
     * Create new brother for this node
     * 
     * @param array|static data for the new sibling
     * @return App\Models\Node|null
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
     * @return App\Models\Node|null
     */
    public function newSister($data)
    {
        return $this->newSibling($data, 'f');
    }

    /**
     * Create new child for this node
     * 
     * @param array|static data for the new child
     * @return App\Models\Node|null
     */
    public function newChild($data, $gender = 'm')
    {
        $new_child_location = (new EagerTree($this->tree_id))->pointer->silentlyTo($this)->newChildLocation();
        Location::validate($new_child_location);

        return $this->createNewNode($data, $new_child_location, $gender);
    }
    /**
     * Create new son for this node
     * 
     * @param array|static data for the new son
     * @return App\Models\Node|null
     */
    public function newSon($data)
    {
        return $this->newChild($data, 'm');
    }
    /**
     * Create new son for this node
     * 
     * @param array|static data for the new daughter
     * @return App\Models\Node|null
     */
    public function newDaughter($data)
    {
        return $this->newChild($data, 'f');
    }

    /**
     * Create father for the root node in the tree
     * 
     * @param array $data data for the new father
     * @return mixed
     */
    public function createFather($data)
    {
        if(!$this->isRoot()){
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
     * @param Illuminate\Support\TreeCollection
     */
    public function husband()
    {
        if($this->gender == 'm'){
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
     * Generate Tree Html code from this node
     * 
     * @return string html code for the tree from this node
     */
    public function toTree()
    {
        return (new EagerTree($this->tree_id))
                ->pointer
                ->silentlyTo($this)
                ->draw();
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
