<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ValidationException extends DokterkunException
{
    protected $validationErrors;

    public function __construct(
        array $validationErrors = [],
        string $message = 'Data yang diberikan tidak valid.',
        string $userMessage = 'Mohon periksa kembali data yang Anda masukkan.',
        int $code = 422,
        string $errorCode = 'VALIDATION_ERROR',
        array $errorContext = []
    ) {
        $this->validationErrors = $validationErrors;
        
        parent::__construct(
            $message,
            $userMessage,
            $code,
            $errorCode,
            array_merge($errorContext, ['validation_errors' => $validationErrors]),
            'warning'
        );
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => true,
                'message' => $this->getUserMessage(),
                'error_code' => $this->getErrorCode(),
                'validation_errors' => $this->getValidationErrors(),
                'timestamp' => now()->toISOString(),
            ], $this->getCode());
        }

        return back()
            ->withErrors($this->getValidationErrors())
            ->with('error', $this->getUserMessage());
    }
}