<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GunApplicationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'application_type' => $this->application_type,
            'purpose' => $this->purpose,
            'gun_type' => $this->gun_type,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at,
            'processed_at' => $this->processed_at,
            'status_color' => $this->status_color,
            'is_pending' => $this->is_pending,
            'is_approved' => $this->is_approved,
            'is_rejected' => $this->is_rejected,
            'processing_time_days' => $this->processing_time_days,
            'user' => new UserResource($this->whenLoaded('user')),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}