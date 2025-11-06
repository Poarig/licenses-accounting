<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name'];

    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    protected static function boot()
    {
        parent::boot();
    
        static::deleting(function($organization) {
            // Мягкое удаление всех связанных лицензий
            if ($organization->isForceDeleting()) {
                $organization->licenses()->withTrashed()->forceDelete();
            } else {
                // Перебираем каждую лицензию для запуска событий
                $organization->licenses->each(function($license) {
                    $license->delete(); // Это вызовет каскадное удаление пинкодов через события License
                });
            }
        });
    
        static::restoring(function($organization) {
            // При восстановлении организации НЕ восстанавливаем лицензии автоматически
        });
    }

    public function getLicenses()
    {
        return $this->hasMany(License::class)->withTrashed();
    }
}
