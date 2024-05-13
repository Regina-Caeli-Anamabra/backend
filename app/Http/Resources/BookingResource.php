<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "session_start" => Carbon::parse($this->session_start)->format('d M, Y H:m:s'),
            "session_end" => Carbon::parse($this->session_end)->format('d M, Y H:m:s'),
            "price" => number_format($this->price, 2),
            "name" => $this->patient->first_name . ' ' . $this->patient->last_name
        ];
    }
}
