<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentReceiptResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'receipt_path' => $this->receipt_path,
            'generated_at' => $this->generated_at,
            'is_digital' => $this->is_digital,
            'download_url' => $this->download_url,
            'view_url' => $this->view_url,
            'file_exists' => $this->file_exists,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}