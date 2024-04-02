<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bookings extends Model
{
    use HasFactory;

    public function bookings(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function bookingPayments(): HasOne
    {
        return $this->hasOne(Bookings::class);
    }
}
