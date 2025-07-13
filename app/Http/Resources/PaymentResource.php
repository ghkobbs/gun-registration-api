<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'payment_reference' => $this->payment_reference,
            'payment_type' => $this->payment_type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'provider' => $this->provider,
            'provider_reference' => $this->provider_reference,
            'status' => $this->status,
            'payment_date' => $this->payment_date,
            'processed_at' => $this->processed_at,
            'is_paid' => $this->is_paid,
            'is_pending' => $this->is_pending,
            'is_failed' => $this->is_failed,
            'status_color' => $this->status_color,
            'formatted_amount' => $this->formatted_amount,
            'user' => new UserResource($this->whenLoaded('user')),
            'receipt' => new PaymentReceiptResource($this->whenLoaded('receipt')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}