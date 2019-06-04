<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $timestamps = false;

    public function products()
    {
        return $this->belongsToMany(Product::class,'Order_has_Product')->withPivot('amount');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'User_id');
    }
}
