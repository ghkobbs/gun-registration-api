<?php
// app/Http/Resources/CrimeEvidenceResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CrimeEvidenceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'evidence_type' => $this->evidence_type,
            'title' => $this->title,
            'description' => $this->description,
            'file_name' => $this->file_name,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'file_size_human' => $this->file_size_human,
            'metadata' => $this->metadata,
            'captured_at' => $this->captured_at,
            'is_image' => $this->is_image,
            'is_video' => $this->is_video,
            'is_audio' => $this->is_audio,
            'is_document' => $this->is_document,
            'view_url' => $this->view_url,
            'download_url' => $this->download_url,
            'thumbnail_url' => $this->thumbnail_url,
            'gps_coordinates' => $this->gps_coordinates,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}