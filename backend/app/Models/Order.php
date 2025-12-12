<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'asset_id',
        'type',
        'quantity',
        'price',
        'status',
    ];
}
