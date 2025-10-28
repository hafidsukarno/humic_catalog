<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image_path',
        'admin_id',
    ];

    // Relasi ke Admin
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    // Otomatis buat slug dari name
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($partner) {
            if (empty($partner->slug)) {
                $partner->slug = Str::slug($partner->name);
            }
        });

        static::updating(function ($partner) {
            if ($partner->isDirty('name')) {
                $partner->slug = Str::slug($partner->name);
            }
        });
    }
}
