<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportUpdateResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'update_type' => $this->update_type,
            'message' => $this->message,
            'visibility' => $this->visibility,
            'is_public' => $this->is_public,
            'is_internal' => $this->is_internal,
            'is_reporter_only' => $this->is_reporter_only,
            'formatted_message' => $this->formatted_message,
            'updated_by' => new UserResource($this->whenLoaded('updatedBy')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}