<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calculate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'total_overtime',
        'overtime_details',
        'overtime_formulas'
    ];

    protected $casts = [
        'overtime_details' => 'array',
        'overtime_formulas' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
