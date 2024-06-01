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
            'first_name' => $this->patients->first_name,
            'last_name' => $this->patients->last_name,
            'service_name' => $this->services->name,
            'amount' => $this->amount,
            'app_fee' => $this->app_fee,
            'amount_settled' => $this->amount_settled
        ];
    }
}
