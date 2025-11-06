<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pincode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['license_id', 'value', 'status', 'type'];

    protected $casts = [
        'status' => 'string',
        'type' => 'string'
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function($pincode) {
            // Если это мягкое удаление, автоматически деактивируем пинкод
            if (!$pincode->isForceDeleting()) {
                // Сохраняем старый статус для проверки
                $oldStatus = $pincode->status;
                
                // Если пинкод не был уже деактивирован, деактивируем его
                if ($oldStatus !== 'used') {
                    $pincode->status = 'used';
                    
                    // Сохраняем без вызова событий, чтобы избежать рекурсии
                    $pincode->saveQuietly();

                    // Логируем действие деактивации при удалении
                    if (auth()->check()) {
                        Action::create([
                            'pincode_id' => $pincode->id,
                            'user_id' => auth()->id(),
                            'action_type' => 'дезактивирован',
                            'date' => now(),
                            'comment' => 'Пинкод автоматически деактивирован при удалении',
                        ]);
                    }
                }
            }
        });

        static::restoring(function($pincode) {
            // При восстановлении пинкод остаётся в статусе 'used'
            // Пользователь может вручную изменить статус при необходимости
        });
    }

    public function license()
    {
        return $this->belongsTo(License::class)->withTrashed();
    }

    public function actions()
    {
        return $this->hasMany(Action::class);
    }

    // Получаем информацию об устройстве из последней активации
    public function getDeviceInformationAttribute()
    {
        $activationAction = $this->actions()
            ->where('action_type', 'активирован')
            ->whereNotNull('device_information')
            ->where('device_information', '!=', '')
            ->latest()
            ->first();
    
        return $activationAction ? $activationAction->device_information : '';
    }

    // Получаем последнее действие активации
    public function getLastActivationActionAttribute()
    {
        return $this->actions()
            ->where('action_type', 'активирован')
            ->latest()
            ->first();
    }

    // Helper methods for type
    public function isSingleUser()
    {
        return $this->type === 'single';
    }

    public function isMultiUser()
    {
        return $this->type === 'multi';
    }

    public function getTypeLabel()
    {
        return $this->isSingleUser() ? 'Однопользовательский' : 'Многопользовательский';
    }
}