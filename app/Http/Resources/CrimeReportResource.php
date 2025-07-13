<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CrimeReportResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'title' => $this->title,
            'description' => $this->description,
            'incident_date' => $this->incident_date,
            'incident_time' => $this->incident_time,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'priority' => $this->priority,
            'is_anonymous' => $this->is_anonymous,
            'status_color' => $this->status_color,
            'priority_color' => $this->priority_color,
            'formatted_location' => $this->formatted_location,
            'user' => new UserResource($this->whenLoaded('user')),
            'crime_type' => new CrimeTypeResource($this->whenLoaded('crimeType')),
            'crime_category' => new CrimeCategoryResource($this->whenLoaded('crimeCategory')),
            'region' => new RegionResource($this->whenLoaded('region')),
            'district' => new DistrictResource($this->whenLoaded('district')),
            'community' => new CommunityResource($this->whenLoaded('community')),
            'evidence' => CrimeEvidenceResource::collection($this->whenLoaded('crimeEvidence')),
            'updates' => ReportUpdateResource::collection($this->whenLoaded('reportUpdates')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}