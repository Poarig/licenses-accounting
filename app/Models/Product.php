<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'description'];

    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function($product) {
            // Мягкое удаление всех связанных лицензий
            if ($product->isForceDeleting()) {
                $product->getLicenses()->withTrashed()->forceDelete();
            } else {
                $product->getLicenses()->delete();
            }
        });

        static::restoring(function($product) {
            // При восстановлении продукта НЕ восстанавливаем лицензии автоматически
        });
    }

    public function getLicenses()
    {
        return $this->hasMany(License::class)->withTrashed();
    }
}