<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommunityResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'district_id' => $this->district_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'boundary' => $this->boundary,
            'is_active' => $this->is_active,
            'full_name' => $this->full_name,
            'has_coordinates' => $this->has_coordinates,
            'crime_reports_count' => $this->whenCounted('crimeReports'),
            'district' => new DistrictResource($this->whenLoaded('district')),
            'crime_reports' => CrimeReportResource::collection($this->whenLoaded('crimeReports')),
            'coordinates' => $this->when($this->has_coordinates, [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}