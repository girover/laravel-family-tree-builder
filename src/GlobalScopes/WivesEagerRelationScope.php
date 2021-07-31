<?php

namespace Girover\Tree\GlobalScopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class WivesEagerRelationScope implements Scope{
    
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if(isset(config('tree.eager_relationships')['wives']['with'])){
            $builder->with(config('tree.eager_relationships')['wives']['with']);
        }
    }
}
