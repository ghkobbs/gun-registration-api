<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class GunRegistrationCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
        ];
    }

    public function with($request)
    {
        return [
            'meta' => [
                'total_registrations' => $this->collection->count(),
                'active_registrations' => $this->collection->where('status', 'active')->count(),
                'suspended_registrations' => $this->collection->where('status', 'suspended')->count(),
                'revoked_registrations' => $this->collection->where('status', 'revoked')->count(),
                'expired_licenses' => $this->collection->where('is_expired', true)->count(),
                'expiring_soon' => $this->collection->where('is_expiring_soon', true)->count(),
            ],
        ];
    }
}