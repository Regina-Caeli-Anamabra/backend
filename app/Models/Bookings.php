<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bookings extends Model
{
    use HasFactory;

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patients::class, 'patient_id');
    }
    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function bookingPayments(): HasOne
    {
        return $this->hasOne(Bookings::class);
    }
}
