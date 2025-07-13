<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DistrictResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'region_id' => $this->region_id,
            'is_active' => $this->is_active,
            'full_name' => $this->full_name,
            'communities_count' => $this->whenCounted('communities'),
            'crime_reports_count' => $this->whenCounted('crimeReports'),
            'region' => new RegionResource($this->whenLoaded('region')),
            'communities' => CommunityResource::collection($this->whenLoaded('communities')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}