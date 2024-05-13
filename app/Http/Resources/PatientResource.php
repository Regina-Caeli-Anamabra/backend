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
            "name" => $this->patient->first_name . " " .$this->patient->last_name,
            "phone" => $this->patient->phone,
            "gender" => $this->patient->gender,
            "marital_status" => $this->patient->marital_status,
            "address_of_residence" => $this->patient->address_of_residence
        ];
    }
}
