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
    
        static::deleting(function($pincode) {
            // Если это мягкое удаление, автоматически деактивируем пинкод
            if (!$pincode->isForceDeleting()) {
                // Сохраняем старый статус для проверки
                $oldStatus = $pincode->getOriginal('status');
                
                // Если пинкод не был уже деактивирован, деактивируем его
                if ($oldStatus !== 'used') {
                    // Используем updateQuietly чтобы избежать рекурсии
                    $pincode->updateQuietly(['status' => 'used']);
                
                    // Логируем действие деактивации при удалении
                    // Проверяем, есть ли аутентифицированный пользователь
                    // (при каскадном удалении из консоли или массовых операциях пользователь может быть не установлен)
                    $userId = auth()->check() ? auth()->id() : null;
                    
                    Action::create([
                        'pincode_id' => $pincode->id,
                        'user_id' => $userId,
                        'action_type' => 'дезактивирован',
                        'date' => now(),
                        'comment' => 'Пинкод автоматически деактивирован при удалении',
                    ]);
                }
            }
        });
    
        static::restoring(function($pincode) {
            // При восстановлении пинкод остаётся в статусе 'used'
            // Пользователь может вручную изменить статус при необходимости
        });
    }

    public function getLicenses()
    {
        return $this->hasMany(License::class)->withTrashed();
    }
}