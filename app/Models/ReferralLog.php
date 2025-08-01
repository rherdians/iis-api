<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_title',
        'referral_code',
        'user_agent',
        'ip_address',
        'whatsapp_click_time',
        'status',
        'nama_pembeli',
        'alamat',
        'nomor_pembeli',
        'order_id',
        'harga',
        'harga_asli',
        'diskon_amount',
        'payment_type',
        'paid_at',
        'failure_reason',
    ];

    protected $casts = [
        'whatsapp_click_time' => 'datetime',
        'paid_at' => 'datetime',
        'harga' => 'decimal:2',
        'harga_asli' => 'decimal:2',
        'diskon_amount' => 'decimal:2',
    ];

    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class, 'referral_code', 'kode_referal');
    }
}