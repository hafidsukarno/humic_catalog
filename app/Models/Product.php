<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'subtitle',
        'category',
        'description',
        'thumbnail_path',
        'file_path',
        'admin_id',
    ];

    // ✅ otomatis buat slug dari title saat create / update
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->title);
            }
        });

        static::updating(function ($product) {
            if (empty($product->slug) || $product->isDirty('title')) {
                $product->slug = Str::slug($product->title);
            }
        });
    }

    // ✅ Relasi ke tabel admin
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    // ✅ Akses URL thumbnail
    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail_path 
            ? asset('storage/' . $this->thumbnail_path)
            : null;
    }

    // ✅ Akses URL file utama
    public function getFileUrlAttribute()
    {
        if (!$this->file_path) return null;

        // Jika file_path adalah URL eksternal
        if (Str::startsWith($this->file_path, ['http://', 'https://'])) {
            return $this->file_path;
        }

        // Jika file_path adalah path lokal (storage)
        return asset('storage/' . $this->file_path);
    }
}
