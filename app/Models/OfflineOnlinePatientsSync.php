<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfflineOnlinePatientsSync extends Model
{
    use HasFactory;

    protected $fillable = [
        'firstName',
        'lastName',
        'phone',
        'religion',
        'next_of_kin',
        'next_of_kin_phone',
        'address_of_next_of_kin',
        'state_of_residence',
        'address_of_residence',
    ];

    public function booking(): HasMany
    {
        return $this->hasMany(Bookings::class, 'id');
    }

    public function serviceCharge(): HasMany
    {
        return $this->hasMany(Bookings::class, 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }


}
