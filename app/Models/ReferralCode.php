<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_referal',
        'username',
        'usage',
    ];

    protected $casts = [
        'usage' => 'integer',
    ];

    public function logs()
    {
        return $this->hasMany(ReferralLog::class, 'referral_code', 'kode_referal');
    }
}