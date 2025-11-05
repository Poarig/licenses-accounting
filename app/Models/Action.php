<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    use HasFactory;

    protected $fillable = [
        'pincode_id', 'user_id', 'action_type', 'device_information', 'comment'
    ];

    public function pincode()
    {
        return $this->belongsTo(Pincode::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessor для удобного отображения типа действия
    public function getActionTypeLabelAttribute()
    {
        $labels = [
            'adding' => 'Добавление',
            'activation' => 'Активация',
            'deactivation' => 'Дезактивация',
        ];

        return $labels[$this->action_type] ?? $this->action_type;
    }

    // Scope для фильтрации по типу действия
    public function scopeOfType($query, $type)
    {
        return $query->where('action_type', $type);
    }
}