<?php

namespace Girover\Tree\GlobalScopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class EagerRelationshipsScope implements Scope{
    
    protected $eager_relations;

    public function __construct() {
        
        $this->eager_relations = config('tree.eager_relationships');
    }
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        foreach ($this->eager_relations as $relation) {
            if(isset($relation['with'])){
                $builder->with($relation['with']);
            }
        }
    }
}