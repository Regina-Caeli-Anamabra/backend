<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return  [
            'first_name' => $this->patients->firstName  ?? null,
            'last_name' => $this->patients->lastName  ?? null,
            'service_name' => $this->services->service_name  ?? null,
            'amount' => number_format($this->amount_settled, 2)  ?? null,
            'app_fee' => number_format($this->app_fee, 2)  ?? null,
            'created_at' => $this->created_at  ?? null,
            'amount_settled' => number_format($this->amount_settled, 2)  ?? null
        ];
    }
}
