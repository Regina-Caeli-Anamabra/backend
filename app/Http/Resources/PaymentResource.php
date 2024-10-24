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
            'amount' => number_format($this->amount, 2),
            'app_fee' => number_format($this->app_fee, 2),
            'amount_settled' => number_format($this->amount_settled, 2)
        ];
    }
}
