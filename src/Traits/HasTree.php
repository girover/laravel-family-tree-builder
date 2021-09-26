<?php

namespace Girover\Tree\Traits;

use Girover\Tree\Helpers\DBHelper;
use Girover\Tree\Exceptions\TreeException;
use Illuminate\Support\Facades\DB;

/**
 *  The model `User` has to use this trait
 */
trait HasTree
{
    /**
     * Get all trees that belongs this user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function trees()
    {
        return $this->hasMany(DBHelper::treeModel(), 'user_id', 'id');
    }
    /**
     * Get all trees that belongs this user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tree($id)
    {
        return DBHelper::treeModel()::find($id);
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
            DBHelper::treeModel()::where('user_id', $this->id)
                                     ->where('id', $id)
                                      ->delete();

            // deleting tree nodes from table `nodes`
            DBHelper::nodeModel()::where('tree_id', $id)
                                        ->delete();

            DB::commit();

            return 'The tree has been deleted successfully.';
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
        return DBHelper::treeModel()::where('user_id', $this->id)
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
}
