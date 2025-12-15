<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogsControllerActions
{
    /**
     * Get the log channel for this controller
     */
    protected function getLogChannel(): string
    {
        // Override this in your controller
        return 'operations';
    }

    /**
     * Get network log channel name
     */
    protected function getNetworkChannel(): string
    {
        return $this->getLogChannel() . '_network';
    }

    /**
     * Log to both local and network
     */
    protected function logToBoth(string $level, string $message, array $context = []): void
    {
        $fullContext = array_merge([
            'user' => auth()->user()?->name ?? 'Guest',
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'timestamp' => now()->toDateTimeString(),
        ], $context);

        // Log to local
        Log::channel($this->getLogChannel())->$level($message, $fullContext);

        // Log to network (with fallback handling)
        try {
            Log::channel($this->getNetworkChannel())->$level($message, $fullContext);
        } catch (\Exception $e) {
            // If network logging fails, log the error locally
            Log::channel($this->getLogChannel())->warning('Network logging failed', [
                'error' => $e->getMessage(),
                'original_message' => $message,
            ]);
        }
    }

    /**
     * Log an info message
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->logToBoth('info', $message, $context);
    }

    /**
     * Log an error message
     */
    protected function logError(string $message, \Throwable $exception = null, array $context = []): void
    {
        $errorContext = array_merge([
            'user' => auth()->user()?->name ?? 'Guest',
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'timestamp' => now()->toDateTimeString(),
        ], $context);

        if ($exception) {
            $errorContext['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        // Log to local
        Log::channel($this->getLogChannel())->error($message, $errorContext);
        Log::channel($this->getLogChannel() . '_errors')->error($message, $errorContext);

        // Log to network (with fallback)
        try {
            Log::channel($this->getNetworkChannel())->error($message, $errorContext);
            Log::channel($this->getLogChannel() . '_errors_network')->error($message, $errorContext);
        } catch (\Exception $e) {
            Log::channel($this->getLogChannel())->warning('Network error logging failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log a warning message
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->logToBoth('warning', $message, $context);
    }

    /**
     * Log a debug message
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $this->logToBoth('debug', $message, $context);
    }

    /**
     * Log a CRUD operation
     */
    protected function logCrudOperation(string $action, string $model, $modelId, array $data = []): void
    {
        $this->logInfo("[$action] $model", [
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'data' => $data,
        ]);
    }

    /**
     * Log a validation failure
     */
    protected function logValidationFailure(array $errors, array $input = []): void
    {
        $this->logWarning('Validation failed', [
            'errors' => $errors,
            'input' => $input,
        ]);
    }

    /**
     * Log an authorization failure
     */
    protected function logAuthorizationFailure(string $action, string $resource): void
    {
        $this->logWarning('Authorization failed', [
            'action' => $action,
            'resource' => $resource,
        ]);
    }

    /**
     * Log file upload
     */
    protected function logFileUpload(string $filename, string $path, int $size): void
    {
        $this->logInfo('File uploaded', [
            'filename' => $filename,
            'path' => $path,
            'size_bytes' => $size,
            'size_readable' => $this->formatBytes($size),
        ]);
    }

    /**
     * Log file deletion
     */
    protected function logFileDeletion(string $filename, string $path): void
    {
        $this->logInfo('File deleted', [
            'filename' => $filename,
            'path' => $path,
        ]);
    }

    /**
     * Log approval/rejection actions
     */
    protected function logApprovalAction(string $action, string $model, $modelId, array $context = []): void
    {
        $this->logInfo("[$action] $model #$modelId", array_merge([
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
        ], $context));
    }

    /**
     * Log status change
     */
    protected function logStatusChange(string $model, $modelId, string $oldStatus, string $newStatus, array $context = []): void
    {
        $this->logInfo("Status changed: $model #$modelId", array_merge([
            'model' => $model,
            'model_id' => $modelId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ], $context));
    }

    /**
     * Format bytes for logging
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}