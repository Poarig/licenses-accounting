<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class License extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id', 'max_count', 'archive_data', 'archive_name', 
        'number', 'organization_id'
    ];

    protected $casts = [
        'max_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
    
        static::deleting(function($license) {
            // Мягкое удаление всех связанных пинкодов
            if ($license->isForceDeleting()) {
                $license->pincodes()->withTrashed()->forceDelete();
            } else {
                // Вместо массового удаления, перебираем каждый пинкод
                // чтобы сработали события модели Pincode
                $license->pincodes->each(function($pincode) {
                    $pincode->delete(); // Это вызовет событие deleting для каждого пинкода
                });
            }
        });
    
        static::restoring(function($license) {
            // При восстановлении лицензии НЕ восстанавливаем пинкоды автоматически
        });
    }

    // Получение активных однопользовательских пинкодов
    public function getActiveSinglePincodes()
    {
        return $this->pincodes()
            ->where('type', 'single')
            ->where('status', 'active')
            ->get();
    }

    // Получение активных многопользовательских пинкодов
    public function getActiveMultiPincodes()
    {
        return $this->pincodes()
            ->where('type', 'multi')
            ->where('status', 'active')
            ->get();
    }

    // Проверка, можно ли активировать новые пинкоды
    public function canActivatePincodes()
    {
        $activeMulti = $this->getActiveMultiPincodes();

        // Если есть активные многопользовательские пинкоды - нельзя активировать
        if ($activeMulti->count() > 0) {
            return false;
        }

        $activeSingle = $this->getActiveSinglePincodes();

        // Если есть активные однопользовательские и пытаемся активировать многопользовательский - нельзя
        if ($activeSingle->count() > 0) {
            return 'single_only'; // Можно активировать только однопользовательские
        }

        // Проверка максимального количества для однопользовательских
        if ($this->max_count !== null && $activeSingle->count() >= $this->max_count) {
            return false;
        }

        return true;
    }

    // Получение оставшегося количества активаций
    public function getRemainingActivations()
    {
        if ($this->max_count === null) {
            return 'не ограничено';
        }

        $activeSingleCount = $this->getActiveSinglePincodes()->count();
        return max(0, $this->max_count - $activeSingleCount);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class)->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function pincodes()
    {
        return $this->hasMany(Pincode::class)->withTrashed();
    }

    public function singleUserPincodes()
    {
        return $this->hasMany(Pincode::class)->where('type', 'single');
    }

    public function multiUserPincodes()
    {
        return $this->hasMany(Pincode::class)->where('type', 'multi');
    }

    // Проверка наличия файла
    public function hasFile()
    {
        return !empty($this->archive_data);
    }

    // Получение расширения файла
    public function getFileExtension()
    {
        if ($this->archive_name) {
            return pathinfo($this->archive_name, PATHINFO_EXTENSION);
        }
        return null;
    }

    // Сохранение файла как base64
    public function saveFileData($filePath)
    {
        $fileContent = file_get_contents($filePath);
        $this->archive_data = base64_encode($fileContent);
        return $this;
    }

    // Получение бинарных данных файла
    public function getFileBinaryData()
    {
        if ($this->archive_data) {
            return base64_decode($this->archive_data);
        }
        return null;
    }

    // Получение размера файла
    public function getFileSize()
    {
        if ($this->archive_data && is_string($this->archive_data)) {
            return strlen($this->archive_data);
        }
        return 0;
    }

    // Получение размера файла в читаемом формате
    public function getFileSizeFormatted()
    {
        $size = $this->getFileSize();
        if ($size == 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($size, 1024));
        return round($size / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}