<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Piece
 *
 * @property $id
 * @property $code
 * @property $name
 * @property $description
 * @property $category_id
 * @property $subcategory_id
 * @property $weight
 * @property $cost_price
 * @property $sale_price
 * @property $status
 * @property $created_at
 * @property $updated_at
 *
 * @property Category $category
 * @property Subcategory $subcategory
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Piece extends Model
{
    
    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['code', 'name', 'description', 'category_id', 'subcategory_id', 'weight', 'cost_price', 'sale_price', 'status'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class, 'category_id', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subcategory()
    {
        return $this->belongsTo(\App\Models\Subcategory::class, 'subcategory_id', 'id');
    }
    
}
