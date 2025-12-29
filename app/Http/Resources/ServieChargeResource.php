<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServieChargeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        \Log::info("Loaded relationships:", array_keys($this->getRelations()));

        return [
            "name" => trim(optional($this->onlineOfflinePatients)->firstName . ' ' . optional($this->onlineOfflinePatients)->lastName),
            "patient_id" => optional($this->users)->reg_id,
            "status" => $this->status,
            "amount" => number_format($this->amount, 2)
        ];

    }
}
