<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "name" => $this->patient ? $this->patient->firstName . " " . $this->patient->lastName : null,
            "phone_no" => $this->phone ?? null,
            "gender" => $this->patient->gender ?? null,
            "marital_status" => $this->patient->marital_status ?? null,
            "address_of_residence" => $this->patient->address ?? null
        ];
    }
}
