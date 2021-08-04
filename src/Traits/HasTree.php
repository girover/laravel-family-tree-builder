<?php

namespace Girover\Tree\Traits;

use Girover\Tree\EagerTree;
use Girover\Tree\Exceptions\TreeException;
use Illuminate\Support\Facades\DB;

/**
 *  The model `User` has to use this trait
 */
trait HasTree
{
    /**
     * Create new tree for this user
     *
     * @param array $data data of the new tree
     * @param int|null $id the id of this user
     *
     * @return
     */
    public function createTree($data, $id = null)
    {
        $data['user_id'] = $id;
        if ($id === null) {
            $data['user_id'] = $this->id;
        }

        return config('tree.tree_model')::create($data);
    }

    /**
     * Get all trees that belongs this user
     *
     * @return Illuminate\Database\Eloquent\Relationship\HasMany
     */
    public function trees()
    {
        return $this->hasMany(config('tree.tree_model'), 'user_id', 'id');
    }

    /**
     * Delete a specific tree that belongs this user from table `trees`
     * with all its nodes from table `nodes`
     *
     * @return mixed
     */
    public function deleteTree($id)
    {
        DB::beginTransaction();

        try {
            // deleting tree from table `trees`
            config('tree.tree_model')::where('user_id', $this->id)
                                     ->where('id', $id)
                                      ->delete();

            // deleting tree nodes from table `nodes`
            config('tree.node_model')::where('tree_id', $id)
                                        ->delete();

            DB::commit();

            return 'The tree has been deleted successfuly.';
        } catch (\Throwable $th) {
            DB::rollBack();

            throw new TreeException($th->getMessage(), 1);
        }
    }

    /**
     * Delete a specific tree that belongs this user
     *
     * @return mixed
     */
    public function deleteAllTrees()
    {
        return config('tree.tree_model')::where('user_id', $this->id)
                                        ->delete();
    }

    /**
     * determine if this user has tree with the given id as parameter
     *
     * @param int id of the tree
     * @return bool
     */
    public function hasTree($id)
    {
        return $this->trees()->where('id', $id)->count() ? true : false;
    }

    /**
     * Get instance of tree
     *
     * @return App\Girover\Tree\EagerTree
     */
    public function tree($id)
    {
        if ($this->hasTree($id)) {
            return new EagerTree($id);
        }

        return null;
    }
}
