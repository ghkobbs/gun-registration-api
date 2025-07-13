<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CrimeTypeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'severity_level' => $this->severity_level,
            'is_active' => $this->is_active,
            'crime_category_id' => $this->crime_category_id,
            'crime_category' => new CrimeCategoryResource($this->whenLoaded('crimeCategory')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}