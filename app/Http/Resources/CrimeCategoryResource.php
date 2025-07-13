<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CrimeCategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_active' => $this->is_active,
            'crime_types_count' => $this->whenCounted('crimeTypes'),
            'crime_types' => CrimeTypeResource::collection($this->whenLoaded('crimeTypes')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}