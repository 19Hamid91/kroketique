<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_products')
                    ->withPivot('quantity', 'price', 'total_price')
                    ->withTimestamps();
    }
}
