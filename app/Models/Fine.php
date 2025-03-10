<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fine extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrow_id',
        'user_id',
        'amount',
        'reason',
        'is_paid',
        'paid_date',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'paid_date' => 'date',
    ];

    public function borrow()
    {
        return $this->belongsTo(Borrow::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
