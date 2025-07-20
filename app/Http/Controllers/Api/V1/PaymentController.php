<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Models\Payment;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends ApiController
{
    protected PaymentManager $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Payment::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $payments = $query->latest()->paginate();
        return $this->paginatedResponse($payments);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'currency' => 'required|string|size:3',
            'payable_type' => 'required|string',
            'payable_id' => 'required|integer',
            'description' => 'nullable|string',
        ]);

        // Create payment record
        $payment = Payment::create([
            'payment_reference' => 'PAY-' . Str::random(10),
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'currency' => strtoupper($request->currency),
            'payment_method' => $request->payment_method,
            'payable_type' => $request->payable_type,
            'payable_id' => $request->payable_id,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        // Initialize payment with provider
        try {
            $result = $this->paymentManager->driver($request->payment_method)
                ->initialize([
                    'email' => auth()->user()->email,
                    'amount' => $payment->amount,
                    'reference' => $payment->payment_reference,
                    'callback_url' => route('payments.verify'),
                    'metadata' => [
                        'payment_id' => $payment->id,
                        'payable_type' => $payment->payable_type,
                        'payable_id' => $payment->payable_id,
                    ],
                ]);

            $payment->update([
                'gateway_reference' => $result['data']['reference'] ?? null,
                'gateway_response' => $result['message'] ?? null,
                'gateway_data' => $result,
            ]);

            return $this->successResponse([
                'payment' => $payment,
                'authorization_url' => $result['data']['authorization_url'] ?? null,
            ], 'Payment initialized successfully', 201);

        } catch (\Exception $e) {
            $payment->update([
                'status' => 'failed',
                'gateway_response' => $e->getMessage(),
            ]);

            return $this->errorResponse('Payment initialization failed: ' . $e->getMessage());
        }
    }

    public function show(Payment $payment): JsonResponse
    {
        return $this->successResponse($payment);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => 'required|string',
        ]);

        $payment = Payment::where('payment_reference', $request->reference)
            ->orWhere('gateway_reference', $request->reference)
            ->firstOrFail();

        try {
            $result = $this->paymentManager->driver($payment->payment_method)
                ->verify($request->reference);

            $status = $result['data']['status'] ?? 'failed';
            $payment->update([
                'status' => $status,
                'gateway_response' => $result['message'] ?? null,
                'gateway_data' => $result,
                'paid_at' => $status === 'completed' ? now() : null,
            ]);

            if ($status === 'completed') {
                // Trigger payment completed event
                event(new PaymentCompleted($payment));
            }

            return $this->successResponse($payment, 'Payment verified successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Payment verification failed: ' . $e->getMessage());
        }
    }

    public function refund(Payment $payment): JsonResponse
    {
        if ($payment->status !== 'completed') {
            return $this->errorResponse('Only completed payments can be refunded');
        }

        try {
            $result = $this->paymentManager->driver($payment->payment_method)
                ->refund($payment->gateway_reference, $payment->amount);

            $payment->update([
                'status' => 'refunded',
                'gateway_response' => $result['message'] ?? null,
                'gateway_data' => array_merge($payment->gateway_data ?? [], [
                    'refund' => $result
                ]),
            ]);

            // Trigger payment refunded event
            event(new PaymentRefunded($payment));

            return $this->successResponse($payment, 'Payment refunded successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Payment refund failed: ' . $e->getMessage());
        }
    }
}