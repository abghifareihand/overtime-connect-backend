<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'overtime_hours',
        'total_overtime',
        'status',
        'day_type',
        'overtime_details'
    ];

    protected $casts = [
        'overtime_details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
