<?php

declare(strict_types=1);

namespace AdminKit\Services;

use Throwable;

class DebugService
{
    private bool $enabled;
    private string $logPath;
    private array $debugData = [];
    private float $startTime;

    public function __construct(bool $enabled = false, string $logPath = 'var/logs')
    {
        $this->enabled = $enabled;
        $this->logPath = $logPath;
        $this->startTime = microtime(true);
        
        if ($this->enabled) {
            $this->initializeDebugger();
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): self
    {
        $this->enabled = true;
        $this->initializeDebugger();
        return $this;
    }

    public function disable(): self
    {
        $this->enabled = false;
        return $this;
    }

    /**
     * Log debug message
     */
    public function log(string $message, array $context = [], string $level = 'debug'): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->debugData[] = [
            'timestamp' => microtime(true),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'memory' => memory_get_usage(true),
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
        ];

        $this->writeLogFile($level, $message, $context);
    }

    /**
     * Log error with full context
     */
    public function logError(Throwable $exception, array $context = []): void
    {
        $errorData = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
        ];

        $this->log('Exception occurred: ' . $exception->getMessage(), $errorData, 'error');
    }

    /**
     * Start performance timer
     */
    public function startTimer(string $name): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->debugData['timers'][$name] = [
            'start' => microtime(true),
            'end' => null,
            'duration' => null,
        ];
    }

    /**
     * End performance timer
     */
    public function endTimer(string $name): float
    {
        if (!$this->enabled || !isset($this->debugData['timers'][$name])) {
            return 0.0;
        }

        $endTime = microtime(true);
        $this->debugData['timers'][$name]['end'] = $endTime;
        $this->debugData['timers'][$name]['duration'] = $endTime - $this->debugData['timers'][$name]['start'];

        $this->log("Timer '$name' completed", [
            'duration' => $this->debugData['timers'][$name]['duration'] . ' seconds'
        ], 'performance');

        return $this->debugData['timers'][$name]['duration'];
    }

    /**
     * Log database query
     */
    public function logQuery(string $sql, array $params = [], float $executionTime = 0): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->debugData['queries'][] = [
            'sql' => $sql,
            'params' => $params,
            'execution_time' => $executionTime,
            'timestamp' => microtime(true),
        ];

        $this->log('Database query executed', [
            'sql' => $sql,
            'params' => $params,
            'execution_time' => $executionTime . ' seconds',
        ], 'database');
    }

    /**
     * Dump variable with context
     */
    public function dump($variable, string $label = 'Variable dump'): void
    {
        if (!$this->enabled) {
            return;
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        
        $this->log($label, [
            'value' => $variable,
            'type' => gettype($variable),
            'file' => $trace['file'] ?? 'unknown',
            'line' => $trace['line'] ?? 'unknown',
        ], 'dump');
    }

    /**
     * Get debug toolbar data
     */
    public function getToolbarData(): array
    {
        if (!$this->enabled) {
            return [];
        }

        $totalTime = microtime(true) - $this->startTime;
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        return [
            'execution_time' => $totalTime,
            'memory_usage' => $memoryUsage,
            'peak_memory' => $peakMemory,
            'log_count' => count($this->debugData),
            'query_count' => count($this->debugData['queries'] ?? []),
            'timer_count' => count($this->debugData['timers'] ?? []),
            'php_version' => PHP_VERSION,
            'included_files' => count(get_included_files()),
        ];
    }

    /**
     * Get all debug data
     */
    public function getDebugData(): array
    {
        return $this->debugData;
    }

    /**
     * Generate debug report
     */
    public function generateReport(): string
    {
        if (!$this->enabled) {
            return 'Debug mode is disabled';
        }

        $toolbarData = $this->getToolbarData();
        $report = "AdminKit Debug Report\n";
        $report .= "=====================\n\n";
        
        $report .= "Performance:\n";
        $report .= "- Execution Time: " . number_format($toolbarData['execution_time'], 4) . " seconds\n";
        $report .= "- Memory Usage: " . $this->formatBytes($toolbarData['memory_usage']) . "\n";
        $report .= "- Peak Memory: " . $this->formatBytes($toolbarData['peak_memory']) . "\n";
        $report .= "- PHP Version: " . $toolbarData['php_version'] . "\n";
        $report .= "- Included Files: " . $toolbarData['included_files'] . "\n\n";

        if (!empty($this->debugData['queries'])) {
            $report .= "Database Queries (" . count($this->debugData['queries']) . "):\n";
            foreach ($this->debugData['queries'] as $i => $query) {
                $report .= ($i + 1) . ". " . $query['sql'] . "\n";
                if (!empty($query['params'])) {
                    $report .= "   Params: " . json_encode($query['params']) . "\n";
                }
                $report .= "   Time: " . number_format($query['execution_time'], 4) . "s\n\n";
            }
        }

        if (!empty($this->debugData['timers'])) {
            $report .= "Performance Timers:\n";
            foreach ($this->debugData['timers'] as $name => $timer) {
                if ($timer['duration'] !== null) {
                    $report .= "- $name: " . number_format($timer['duration'], 4) . " seconds\n";
                }
            }
            $report .= "\n";
        }

        $report .= "Debug Logs (" . count($this->debugData) . "):\n";
        foreach ($this->debugData as $i => $log) {
            if (is_array($log) && isset($log['level'])) {
                $time = date('H:i:s', (int)$log['timestamp']);
                $report .= "[$time] {$log['level']}: {$log['message']}\n";
            }
        }

        return $report;
    }

    /**
     * Clear debug data
     */
    public function clear(): void
    {
        $this->debugData = [];
        $this->startTime = microtime(true);
    }

    /**
     * Initialize debugger
     */
    private function initializeDebugger(): void
    {
        // Create log directory if it doesn't exist
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }

        // Set error handlers
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
        
        // Initialize data structures
        $this->debugData['queries'] = [];
        $this->debugData['timers'] = [];
    }

    /**
     * Custom error handler
     */
    public function errorHandler(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $this->log("PHP Error: $message", [
            'severity' => $severity,
            'file' => $file,
            'line' => $line,
        ], 'error');

        return false; // Don't prevent default error handling
    }

    /**
     * Custom exception handler
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $this->logError($exception);
        
        if ($this->enabled && !headers_sent()) {
            http_response_code(500);
            echo $this->renderErrorPage($exception);
        }
    }

    /**
     * Write to log file
     */
    private function writeLogFile(string $level, string $message, array $context = []): void
    {
        $logFile = $this->logPath . '/debug-' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logLine = "[$timestamp] $level: $message$contextStr\n";
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return number_format($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Render error page
     */
    private function renderErrorPage(Throwable $exception): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <title>AdminKit Debug - Error</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .error { background: #fff; border: 1px solid #ddd; padding: 20px; border-radius: 4px; }
        .title { color: #d32f2f; font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .message { color: #333; margin-bottom: 15px; }
        .trace { background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto; }
        pre { margin: 0; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="error">
        <div class="title">AdminKit Debug - Uncaught Exception</div>
        <div class="message">
            <strong>Message:</strong> ' . htmlspecialchars($exception->getMessage()) . '<br>
            <strong>File:</strong> ' . htmlspecialchars($exception->getFile()) . '<br>
            <strong>Line:</strong> ' . $exception->getLine() . '
        </div>
        <div class="trace">
            <strong>Stack Trace:</strong>
            <pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>
        </div>
    </div>
</body>
</html>';
    }
}
