<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_reference',
        'user_id',
        'payable_type',
        'payable_id',
        'amount',
        'currency',
        'payment_method',
        'status',
        'gateway_reference',
        'gateway_response',
        'gateway_data',
        'description',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_data' => 'array',
        'paid_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(PaymentReceipt::class);
    }

    // Accessors
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsFailedAttribute(): bool
    {
        return $this->status === 'failed';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }

    // Methods
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);
        
        // Generate receipt
        $this->generateReceipt();
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'gateway_response' => $reason,
        ]);
    }

    public function generateReceipt(): PaymentReceipt
    {
        return PaymentReceipt::create([
            'payment_id' => $this->id,
            'receipt_number' => PaymentReceipt::generateReceiptNumber(),
            'receipt_path' => $this->generateReceiptPath(),
            'generated_at' => now(),
        ]);
    }

    private function generateReceiptPath(): string
    {
        // Logic to generate PDF receipt and return path
        return 'receipts/' . $this->payment_reference . '.pdf';
    }

    public static function generatePaymentReference(): string
    {
        return 'PAY-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->payment_reference)) {
                $model->payment_reference = static::generatePaymentReference();
            }
        });
    }
}