<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class); 
    }
}
