<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
			// if($request->expectsJson()) {
						return $this->handleApiException($request, $e);
				// }
    }

    private function handleApiException(Request $request, Throwable $e): JsonResponse
    {
			   if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json([
								'success' => false,
                'message' => 'Method not allowed for this endpoint'
            ], 405);
        }

        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found',
            ], 404);
				}

        if ($e instanceof AccessDeniedHttpException) {
            return response()->json([
                'success' => 'false',
								'message' => 'This action is unauthorized.'
						], 403);
        }

        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

				Log::error('API Exception', [$e]);
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated',
        ], $statusCode);
    }

		protected function unauthenticated($request, AuthenticationException $exception)
		{
			return response()->json([
					'message' => 'Unauthenticated.',
					'error' => 'authentication_required'
			], 401);
		}
}