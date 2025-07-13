<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_size' => $this->file_size,
            'file_size_human' => $this->file_size_human,
            'verification_status' => $this->verification_status,
            'verification_notes' => $this->verification_notes,
            'verified_at' => $this->verified_at,
            'is_verified' => $this->is_verified,
            'is_pending' => $this->is_pending,
            'is_rejected' => $this->is_rejected,
            'status_color' => $this->status_color,
            'download_url' => $this->download_url,
            'verified_by' => new UserResource($this->whenLoaded('verifiedBy')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}