<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'stock',
        'image',
        'category_id',
        'is_active',
        'sku',
        'brand',
        'storage',
        'color',
        'condition',
        'model_number',
        'buy_price',
        'sell_price'
    ];

    protected $casts = [
        'stock' => 'integer',
        'is_active' => 'boolean',
        'image' => 'array', // Cast image field as array for JSON storage
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the full URL for images
     */
    public function getImageUrlsAttribute()
    {
        if (!$this->image) {
            return [];
        }

        $urls = [];
        foreach ($this->image as $imagePath) {
            $urls[] = asset('storage/' . $imagePath);
        }

        return $urls;
    }

    /**
     * Get the first image URL
     */
    public function getFirstImageUrlAttribute()
    {
        if (!$this->image || empty($this->image)) {
            return null;
        }

        return asset('storage/' . $this->image[0]);
    }

    /**
     * Get the orders for the product.
     */
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items')
                    ->withPivot('quantity', 'unit_price', 'discount', 'total_price')
                    ->withTimestamps();
    }
}
