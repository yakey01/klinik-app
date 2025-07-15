<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DokterkunException extends Exception
{
    protected $errorCode;
    protected $errorContext;
    protected $userMessage;
    protected $logLevel;

    public function __construct(
        string $message = '',
        string $userMessage = '',
        int $code = 0,
        string $errorCode = '',
        array $errorContext = [],
        string $logLevel = 'error',
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->errorCode = $errorCode;
        $this->errorContext = $errorContext;
        $this->userMessage = $userMessage ?: 'Terjadi kesalahan dalam sistem.';
        $this->logLevel = $logLevel;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrorContext(): array
    {
        return $this->errorContext;
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => true,
                'message' => $this->getUserMessage(),
                'error_code' => $this->getErrorCode(),
                'timestamp' => now()->toISOString(),
            ], $this->getCode() ?: 500);
        }

        return back()->with('error', $this->getUserMessage());
    }
}