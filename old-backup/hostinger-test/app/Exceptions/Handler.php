<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Services\ErrorHandlingService;
use App\Exceptions\DokterkunException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Report or log an exception.
     */
    public function report(Throwable $e): void
    {
        // Let our error handling service handle the logging
        if (!$e instanceof DokterkunException) {
            $errorHandler = new ErrorHandlingService();
            $errorHandler->handleException($e);
        }
        
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Handle DokterkunException with custom rendering
        if ($e instanceof DokterkunException) {
            return $e->render($request);
        }

        // Convert other exceptions to DokterkunException for consistent handling
        $errorHandler = new ErrorHandlingService();
        $dokterkunException = $errorHandler->handleException($e);
        
        return $dokterkunException->render($request);
    }
}