<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'deskripsi',
        'file_link',
        'admin_id',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
