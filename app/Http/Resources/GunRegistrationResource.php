<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GunRegistrationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'gun_application_id' => $this->gun_application_id,
            'registration_number' => $this->registration_number,
            'serial_number' => $this->serial_number,
            'gun_type' => $this->gun_type,
            'gun_model' => $this->gun_model,
            'manufacturer' => $this->manufacturer,
            'caliber' => $this->caliber,
            'barrel_length' => $this->barrel_length,
            'gun_condition' => $this->gun_condition,
            'status' => $this->status,
            'notes' => $this->notes,
            'issued_at' => $this->issued_at,
            'license_expires_at' => $this->license_expires_at,
            'is_active' => $this->is_active,
            'is_expired' => $this->is_expired,
            'is_expiring_soon' => $this->is_expiring_soon,
            'days_until_expiry' => $this->days_until_expiry,
            'license_status' => $this->license_status,
            'user' => new UserResource($this->whenLoaded('user')),
            'gun_application' => new GunApplicationResource($this->whenLoaded('gunApplication')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}