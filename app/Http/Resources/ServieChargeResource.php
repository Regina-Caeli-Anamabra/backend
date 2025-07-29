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
    public function toArray(Request $request): array
    {
        return [
            "name" => ($this->patients)->firstName . " " . ($this->patients)->lastName,
            "patient_id" => ($this->patients)->patient_id,
            "amount" => number_format($this->amount_settled, 2)
        ];
    }
}
