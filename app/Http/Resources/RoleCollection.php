<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class RoleCollection extends ResourceCollection
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
                'total_roles' => $this->collection->count(),
                'roles_with_users' => $this->collection->where('users_count', '>', 0)->count(),
                'roles_without_users' => $this->collection->where('users_count', 0)->count(),
            ],
        ];
    }
}