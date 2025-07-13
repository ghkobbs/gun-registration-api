<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CommunityCollection extends ResourceCollection
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
                'total_communities' => $this->collection->count(),
                'active_communities' => $this->collection->where('is_active', true)->count(),
                'inactive_communities' => $this->collection->where('is_active', false)->count(),
                'communities_with_coordinates' => $this->collection->where('has_coordinates', true)->count(),
            ],
        ];
    }
}