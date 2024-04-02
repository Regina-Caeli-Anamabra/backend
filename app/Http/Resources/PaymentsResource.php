<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "amount" =>  $this->amount,
            "service" => $this->services->name,
            "session_start" => Carbon::parse($this->paymentBookings->session_start)->format("d M, Y H:m:s"),
            "session_end" => Carbon::parse($this->paymentBookings->session_end)->format("d M, Y H:m:s"),
            "name" => $this->patients->first_name . " " . $this->patients->last_name,
        ];
    }
}
