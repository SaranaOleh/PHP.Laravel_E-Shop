<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    public $timestamps = false;

    public function group()
    {
        return $this->belongsTo(AttributeGroups::class, 'Group_id');
    }

    public function category()
    {
        return $this->belongsToMany(Category::class,'Category_has_Attribute');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class,'Product_has_Attribute');
    }

}
