<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlutterwavePayment extends Model
{
    use HasFactory;

    public function patients(): BelongsTo
    {
        return $this->belongsTo('App\Models\Patients', 'patient_id' );
    }

    public function services(): BelongsTo
    {
        return $this->belongsTo('App\Models\Services', 'service_id' );
    }
}
