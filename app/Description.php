<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Description extends Model
{
    public $timestamps = false;

    public function product()
    {
        return $this->hasOne(Product::class,'Description_id');
    }
}
