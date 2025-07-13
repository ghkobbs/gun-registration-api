<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class RegionCollection extends ResourceCollection
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
                'total_regions' => $this->collection->count(),
                'active_regions' => $this->collection->where('is_active', true)->count(),
                'inactive_regions' => $this->collection->where('is_active', false)->count(),
            ],
        ];
    }
}