<?php

namespace App\Traits;

use App\Exceptions\DokterkunException;
use App\Services\ErrorHandlingService;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

trait HandlesErrors
{
    protected function handleError(Exception $exception): void
    {
        $errorHandler = new ErrorHandlingService();
        $dokterkunException = $errorHandler->handleException($exception);
        
        $this->sendErrorNotification($dokterkunException);
    }

    protected function handleErrorWithReturn(Exception $exception, $default = null)
    {
        $this->handleError($exception);
        return $default;
    }

    protected function handleErrorWithResponse(Exception $exception): JsonResponse|RedirectResponse
    {
        $errorHandler = new ErrorHandlingService();
        $dokterkunException = $errorHandler->handleException($exception);
        
        if (request()->expectsJson()) {
            return response()->json([
                'error' => true,
                'message' => $dokterkunException->getUserMessage(),
                'error_code' => $dokterkunException->getErrorCode(),
                'timestamp' => now()->toISOString(),
            ], $dokterkunException->getCode() ?: 500);
        }

        $this->sendErrorNotification($dokterkunException);
        return back();
    }

    protected function sendErrorNotification(DokterkunException $exception): void
    {
        $errorHandler = new ErrorHandlingService();
        $errorInfo = $errorHandler->formatErrorForUser($exception);
        
        $notification = Notification::make()
            ->title($errorInfo['title'])
            ->body($errorInfo['message']);

        // Set notification color based on error type
        switch ($errorInfo['type']) {
            case 'danger':
                $notification->danger();
                break;
            case 'warning':
                $notification->warning();
                break;
            case 'info':
                $notification->info();
                break;
            default:
                $notification->warning();
        }

        $notification->send();
    }

    protected function wrapOperation(callable $operation, string $operationName = 'operation')
    {
        try {
            return $operation();
        } catch (Exception $e) {
            return $this->handleErrorWithReturn($e);
        }
    }

    protected function safeExecute(callable $operation, $default = null, string $operationName = 'operation')
    {
        try {
            return $operation();
        } catch (Exception $e) {
            $this->handleError($e);
            return $default;
        }
    }

    protected function executeWithNotification(callable $operation, string $successMessage, string $operationName = 'operation')
    {
        try {
            $result = $operation();
            
            Notification::make()
                ->title('✅ Berhasil')
                ->body($successMessage)
                ->success()
                ->send();
                
            return $result;
        } catch (Exception $e) {
            $this->handleError($e);
            return null;
        }
    }

    protected function validateAndExecute(array $data, array $rules, callable $operation, string $successMessage = 'Operasi berhasil')
    {
        try {
            // Validate data
            $validated = validator($data, $rules)->validate();
            
            // Execute operation
            $result = $operation($validated);
            
            // Send success notification
            Notification::make()
                ->title('✅ Berhasil')
                ->body($successMessage)
                ->success()
                ->send();
                
            return $result;
        } catch (Exception $e) {
            $this->handleError($e);
            return null;
        }
    }
}