<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public $timestamps = false;

    public function product()
    {
        return $this->hasMany(Product::class,'Category_id');
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class,'Category_has_Attribute');
    }

    public function children()
    {
        return $this->hasMany(Category::class,'parent_category');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class,'parent_category');
    }

}
