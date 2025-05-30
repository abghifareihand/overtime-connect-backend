<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordOtp extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'otp',
        'expires_at'
    ];

    public $timestamps = true;
}
