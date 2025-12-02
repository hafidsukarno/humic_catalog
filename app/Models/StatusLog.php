<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusLog extends Model
{
    use HasFactory;

    protected $table = 'status_logs';

    protected $fillable = [
        'admin_id',
        'product_id',
        'partner_id',
        'action',
        'status_message',
        'product_title',
        'partner_name',
    ];

    /**
     * RELATIONS
     */

    // Relasi ke Admin
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    // Relasi ke Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Relasi ke Partner
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
}
