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
        'category',
        'description',
        'user_manual',
        'thumbnail_path',
        'file_path',
        'file_url',
        'admin_id',
    ];

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

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    // URL Thumbnail (benar)
    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail_path 
            ? asset('storage/' . $this->thumbnail_path)
            : null;
    }

    // ❌ file_url TIDAK boleh diubah → biarkan nilai asli dari database
    // Jadi ACCESSOR INI DIHAPUS

    // URL File Lokal (storage)
    public function getFilePathUrlAttribute()
    {
        if (!$this->file_path) return null;

        return asset('storage/' . $this->file_path);
    }
}
