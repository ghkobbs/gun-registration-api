<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PaymentReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'receipt_number',
        'receipt_path',
        'generated_at',
        'is_digital',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'is_digital' => 'boolean',
    ];

    // Relationships
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // Accessors
    public function getDownloadUrlAttribute(): string
    {
        return route('receipts.download', $this->id);
    }

    public function getViewUrlAttribute(): string
    {
        return route('receipts.view', $this->id);
    }

    public function getFileExistsAttribute(): bool
    {
        return Storage::disk('receipts')->exists($this->receipt_path);
    }

    // Methods
    public function generatePDF(): string
    {
        $payment = $this->payment;
        $user = $payment->user;

        // This would use a PDF generation library like TCPDF or DomPDF
        // For now, return a placeholder path
        $filename = 'receipt_' . $this->receipt_number . '.pdf';
        $path = 'receipts/' . $filename;

        // Generate PDF content
        $pdfContent = $this->generateReceiptContent($payment, $user);
        
        // Save to storage
        Storage::disk('receipts')->put($path, $pdfContent);

        return $path;
    }

    private function generateReceiptContent($payment, $user): string
    {
        // PDF generation logic would go here
        // This is a placeholder
        return "Receipt for payment: {$payment->payment_reference}";
    }

    public function regenerate(): void
    {
        $this->receipt_path = $this->generatePDF();
        $this->generated_at = now();
        $this->save();
    }

    public static function generateReceiptNumber(): string
    {
        return 'RCP-' . now()->format('YmdHis') . '-' . str_pad(static::count() + 1, 4, '0', STR_PAD_LEFT);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->receipt_number)) {
                $model->receipt_number = static::generateReceiptNumber();
            }
        });
    }
}