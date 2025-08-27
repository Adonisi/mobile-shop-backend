<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'service_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'device_type',
        'device_brand',
        'device_model',
        'service_type',
        'problem_description',
        'estimated_cost',
        'status',
        'priority',
        'estimated_completion_date',
        'notes',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'estimated_completion_date' => 'date',
    ];

    /**
     * Get the user that owns the service.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a unique service number
     */
    public static function generateServiceNumber()
    {
        $prefix = 'SER';
        
        // Get the last service number
        $lastService = self::orderBy('id', 'desc')->first();
        
        if ($lastService) {
            // Extract the number from the last service number
            $lastNumberStr = substr($lastService->service_number, strlen($prefix) + 1); // +1 for the dash
            
            // Try to convert to integer, if it fails start from 100
            $lastNumber = (int) $lastNumberStr;
            if ($lastNumber > 0) {
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 100;
            }
        } else {
            // Start from 100 if no services exist
            $nextNumber = 100;
        }
        
        return $prefix . '-' . $nextNumber;
    }
}
