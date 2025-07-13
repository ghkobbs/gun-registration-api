<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'is_active' => $this->is_active,
            'districts_count' => $this->whenCounted('districts'),
            'crime_reports_count' => $this->whenCounted('crimeReports'),
            'districts' => DistrictResource::collection($this->whenLoaded('districts')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}