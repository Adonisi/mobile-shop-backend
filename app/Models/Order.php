<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'order_number',
        'subtotal',
        'tax',
        'total',
        'shipping_address',
        'customer_name',
        'customer_email',
        'customer_phone',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items for the order.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the products for the order.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
                    ->withPivot('quantity', 'unit_price', 'discount', 'total_price')
                    ->withTimestamps();
    }

    /**
     * Generate a unique order number
     */
    public static function generateOrderNumber()
    {
        $prefix = 'FAC';
        
        // Get the last order number
        $lastOrder = self::orderBy('id', 'desc')->first();
        
        if ($lastOrder) {
            // Extract the number from the last order number
            $lastNumberStr = substr($lastOrder->order_number, strlen($prefix));
            
            // Check if the last number is in the old format (date format)
            if (strlen($lastNumberStr) === 8 && is_numeric($lastNumberStr)) {
                // This is likely the old date format, start from 100
                $nextNumber = 100;
            } else {
                // Try to convert to integer, if it fails start from 100
                $lastNumber = (int) $lastNumberStr;
                if ($lastNumber > 0) {
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 100;
                }
            }
        } else {
            // Start from 100 if no orders exist
            $nextNumber = 100;
        }
        
        return $prefix . $nextNumber;
    }
}
