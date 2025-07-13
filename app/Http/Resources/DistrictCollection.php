<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class DistrictCollection extends ResourceCollection
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
                'total_districts' => $this->collection->count(),
                'active_districts' => $this->collection->where('is_active', true)->count(),
                'inactive_districts' => $this->collection->where('is_active', false)->count(),
            ],
        ];
    }
}