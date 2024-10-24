<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Services extends Model
{
    use HasFactory;

    protected $guarded = [];
    public function services(): HasMany
    {
        return $this->hasMany(Payments::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Bookings::class, "service_id");
    }


    public function payments(): HasMany
    {
        return $this->hasMany('App\Models\FlutterwavePayment');
    }

}
