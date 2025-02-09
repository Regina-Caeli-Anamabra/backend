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
            "name" => $this->firstName . " " . $this->lastName,
            "phone" => $this->phone_no ?? null,
            "gender" => $this->gender ?? null,
            "patient_id" => $this->system_id ?? null,
            "marital_status" => $this->marital_status ?? null,
            "address_of_residence" => $this->address ?? null
        ];
    }
}
