<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payments extends Model
{
    use HasFactory;


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }


    public function paymentBookings(): BelongsTo
    {
        return $this->belongsTo(Bookings::class, "booking_id");
    }

    public function patients(): BelongsTo
    {
        return $this->belongsTo(Patients::class, "patient_id");
    }
    public function services(): BelongsTo
    {
        return $this->belongsTo(Services::class, "service_id");
    }
}
