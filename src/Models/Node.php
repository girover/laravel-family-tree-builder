<?php

namespace Girover\Tree\Models;

use Girover\Tree\Services\NodeService;
use Girover\Tree\Traits\Nodeable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    use HasFactory;

    protected $primaryKey = 'node_id';

    protected $fillable = ['nodeable_id','treeable_id','location','gender','photo'];
}
