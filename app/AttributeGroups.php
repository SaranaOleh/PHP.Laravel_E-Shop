<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AttributeGroups extends Model
{
    protected $table = 'groups';
    public $timestamps = false;

    public function attribute()
    {
        return $this->hasMany(Attribute::class, 'Group_id');
    }
}
