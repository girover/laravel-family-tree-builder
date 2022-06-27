<?php

namespace Girover\Tree\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreeNode extends Model
{
    use HasFactory;

    protected $primaryKey = 'node_id';

    protected $fillable = ['nodeable_id','treeable_id','location','gender','photo'];
}
