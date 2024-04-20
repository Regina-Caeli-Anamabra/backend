<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patients extends Model
{
    use HasFactory;

    protected $fillable = [
        "first_name"
    ];


    public function bookings(): BelongsTo
    {
        return $this->belongsTo(Bookings::class, "patient_id");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

}
