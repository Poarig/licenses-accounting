<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'max_count', 'license_file', 'number', 'organization_id'
    ];

    // Делаем max_count nullable
    protected $casts = [
        'max_count' => 'integer'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function pincodes()
    {
        return $this->hasMany(Pincode::class);
    }

    public function singleUserPincodes()
    {
        return $this->hasMany(Pincode::class)->where('type', 'single');
    }

    public function multiUserPincodes()
    {
        return $this->hasMany(Pincode::class)->where('type', 'multi');
    }
}