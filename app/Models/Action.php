<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    use HasFactory;

    protected $fillable = [
        'pincode_id', 'user_id', 'action_type', 'device_information', 
        'comment', 'file_data', 'file_name'
    ];

    public function pincode()
    {
        return $this->belongsTo(Pincode::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActionTypeLabelAttribute()
    {
        $labels = [
            'добавлен' => 'Добавлен',
            'активирован' => 'Активирован',
            'дезактивирован' => 'Дезактивирован',
        ];

        return $labels[$this->action_type] ?? $this->action_type;
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('action_type', $type);
    }

    // Проверка наличия файла
    public function hasFile()
    {
        return !empty($this->file_data);
    }
}