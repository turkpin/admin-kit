<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Turkpin\AdminKit\Services\CacheService;

class QueueService
{
    private CacheService $cacheService;
    private array $config;
    private array $jobs;
    private array $queues;
    private bool $isRunning;

    public function __construct(CacheService $cacheService, array $config = [])
    {
        $this->cacheService = $cacheService;
        $this->config = array_merge([
            'default_queue' => 'default',
            'max_retries' => 3,
            'retry_delay' => 60, // seconds
            'job_timeout' => 300, // 5 minutes
            'batch_size' => 10,
            'sleep_time' => 1, // seconds between checks
            'failed_job_retention' => 7 * 24 * 3600, // 7 days
            'worker_processes' => 1
        ], $config);
        
        $this->jobs = [];
        $this->queues = ['default', 'high', 'low', 'critical'];
        $this->isRunning = false;
        
        $this->registerDefaultJobs();
    }

    /**
     * Register default job types
     */
    private function registerDefaultJobs(): void
    {
        $this->registerJob('email', [
            'class' => 'EmailJob',
            'timeout' => 60,
            'retries' => 3
        ]);
        
        $this->registerJob('export', [
            'class' => 'ExportJob',
            'timeout' => 300,
            'retries' => 1
        ]);
        
        $this->registerJob('import', [
            'class' => 'ImportJob', 
            'timeout' => 600,
            'retries' => 2
        ]);
        
        $this->registerJob('cleanup', [
            'class' => 'CleanupJob',
            'timeout' => 120,
            'retries' => 1
        ]);
        
        $this->registerJob('notification', [
            'class' => 'NotificationJob',
            'timeout' => 30,
            'retries' => 5
        ]);
    }

    /**
     * Register a job type
     */
    public function registerJob(string $type, array $config): void
    {
        $this->jobs[$type] = array_merge([
            'class' => null,
            'timeout' => $this->config['job_timeout'],
            'retries' => $this->config['max_retries'],
            'queue' => $this->config['default_queue']
        ], $config);
    }

    /**
     * Dispatch a job to queue
     */
    public function dispatch(string $type, array $data = [], array $options = []): string
    {
        $jobId = uniqid('job_');
        $queue = $options['queue'] ?? $this->jobs[$type]['queue'] ?? $this->config['default_queue'];
        $delay = $options['delay'] ?? 0;
        
        $job = [
            'id' => $jobId,
            'type' => $type,
            'data' => $data,
            'queue' => $queue,
            'attempts' => 0,
            'max_retries' => $options['retries'] ?? $this->jobs[$type]['retries'] ?? $this->config['max_retries'],
            'timeout' => $options['timeout'] ?? $this->jobs[$type]['timeout'] ?? $this->config['job_timeout'],
            'created_at' => time(),
            'available_at' => time() + $delay,
            'status' => 'pending',
            'error' => null
        ];

        // Add to queue
        $queueKey = "queue:{$queue}";
        $queueJobs = $this->cacheService->get($queueKey, fn() => []);
        $queueJobs[] = $jobId;
        $this->cacheService->set($queueKey, $queueJobs, 86400);

        // Store job data
        $this->cacheService->set("job:{$jobId}", $job, 86400);

        return $jobId;
    }

    /**
     * Process jobs in queue
     */
    public function work(string $queue = null): void
    {
        $queue = $queue ?? $this->config['default_queue'];
        $this->isRunning = true;

        while ($this->isRunning) {
            $job = $this->getNextJob($queue);
            
            if ($job) {
                $this->processJob($job);
            } else {
                sleep($this->config['sleep_time']);
            }
        }
    }

    /**
     * Get next available job
     */
    private function getNextJob(string $queue): ?array
    {
        $queueKey = "queue:{$queue}";
        $jobIds = $this->cacheService->get($queueKey, fn() => []);
        
        foreach ($jobIds as $index => $jobId) {
            $job = $this->cacheService->get("job:{$jobId}");
            
            if (!$job) {
                // Remove invalid job from queue
                unset($jobIds[$index]);
                continue;
            }
            
            // Check if job is available (not delayed)
            if ($job['available_at'] <= time() && $job['status'] === 'pending') {
                // Remove from queue
                unset($jobIds[$index]);
                $this->cacheService->set($queueKey, array_values($jobIds), 86400);
                
                return $job;
            }
        }
        
        return null;
    }

    /**
     * Process a single job
     */
    private function processJob(array $job): void
    {
        $startTime = time();
        
        try {
            // Mark as processing
            $job['status'] = 'processing';
            $job['started_at'] = $startTime;
            $job['attempts']++;
            $this->cacheService->set("job:{$job['id']}", $job, 86400);

            // Execute job
            $this->executeJob($job);
            
            // Mark as completed
            $job['status'] = 'completed';
            $job['completed_at'] = time();
            $this->cacheService->set("job:{$job['id']}", $job, 3600); // Keep for 1 hour

        } catch (\Exception $e) {
            // Handle job failure
            $this->handleJobFailure($job, $e);
        }
    }

    /**
     * Execute job logic
     */
    private function executeJob(array $job): void
    {
        $type = $job['type'];
        
        if (!isset($this->jobs[$type])) {
            throw new \Exception("Unknown job type: {$type}");
        }
        
        switch ($type) {
            case 'email':
                $this->executeEmailJob($job['data']);
                break;
            case 'export':
                $this->executeExportJob($job['data']);
                break;
            case 'import':
                $this->executeImportJob($job['data']);
                break;
            case 'cleanup':
                $this->executeCleanupJob($job['data']);
                break;
            case 'notification':
                $this->executeNotificationJob($job['data']);
                break;
            default:
                throw new \Exception("Job handler not implemented for: {$type}");
        }
    }

    /**
     * Execute email job
     */
    private function executeEmailJob(array $data): void
    {
        // Simulate email sending
        $to = $data['to'] ?? 'unknown';
        $subject = $data['subject'] ?? 'No Subject';
        
        // In real implementation, integrate with mail service
        error_log("Sending email to {$to}: {$subject}");
        
        // Simulate processing time
        sleep(1);
    }

    /**
     * Execute export job
     */
    private function executeExportJob(array $data): void
    {
        $format = $data['format'] ?? 'csv';
        $entity = $data['entity'] ?? 'unknown';
        $userId = $data['user_id'] ?? 0;
        
        error_log("Exporting {$entity} as {$format} for user {$userId}");
        
        // Simulate long-running export
        sleep(5);
        
        // In real implementation, generate file and notify user
    }

    /**
     * Execute import job
     */
    private function executeImportJob(array $data): void
    {
        $file = $data['file'] ?? 'unknown';
        $entity = $data['entity'] ?? 'unknown';
        
        error_log("Importing {$file} into {$entity}");
        
        // Simulate import processing
        sleep(3);
    }

    /**
     * Execute cleanup job
     */
    private function executeCleanupJob(array $data): void
    {
        $type = $data['type'] ?? 'cache';
        
        switch ($type) {
            case 'cache':
                $this->cleanupExpiredCache();
                break;
            case 'logs':
                $this->cleanupOldLogs();
                break;
            case 'temp':
                $this->cleanupTempFiles();
                break;
        }
    }

    /**
     * Execute notification job
     */
    private function executeNotificationJob(array $data): void
    {
        $userId = $data['user_id'] ?? 0;
        $message = $data['message'] ?? '';
        $type = $data['type'] ?? 'info';
        
        error_log("Sending notification to user {$userId}: {$message}");
        
        // In real implementation, integrate with NotificationService
    }

    /**
     * Handle job failure
     */
    private function handleJobFailure(array $job, \Exception $exception): void
    {
        $job['status'] = 'failed';
        $job['error'] = $exception->getMessage();
        $job['failed_at'] = time();

        // Check if we should retry
        if ($job['attempts'] < $job['max_retries']) {
            // Schedule retry
            $delay = $this->config['retry_delay'] * pow(2, $job['attempts'] - 1); // Exponential backoff
            
            $job['status'] = 'pending';
            $job['available_at'] = time() + $delay;
            
            // Add back to queue
            $queueKey = "queue:{$job['queue']}";
            $queueJobs = $this->cacheService->get($queueKey, fn() => []);
            $queueJobs[] = $job['id'];
            $this->cacheService->set($queueKey, $queueJobs, 86400);
            
            error_log("Job {$job['id']} failed, retrying in {$delay} seconds (attempt {$job['attempts']}/{$job['max_retries']})");
        } else {
            // Move to failed jobs
            $this->moveToFailedJobs($job);
            error_log("Job {$job['id']} permanently failed after {$job['attempts']} attempts: " . $exception->getMessage());
        }

        $this->cacheService->set("job:{$job['id']}", $job, 86400);
    }

    /**
     * Move job to failed jobs list
     */
    private function moveToFailedJobs(array $job): void
    {
        $failedKey = 'failed_jobs';
        $failedJobs = $this->cacheService->get($failedKey, fn() => []);
        $failedJobs[] = $job['id'];
        
        // Keep only recent failed jobs
        if (count($failedJobs) > 1000) {
            $failedJobs = array_slice($failedJobs, -1000);
        }
        
        $this->cacheService->set($failedKey, $failedJobs, $this->config['failed_job_retention']);
    }

    /**
     * Get job status
     */
    public function getJobStatus(string $jobId): ?array
    {
        return $this->cacheService->get("job:{$jobId}");
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats(): array
    {
        $stats = [];
        
        foreach ($this->queues as $queue) {
            $queueKey = "queue:{$queue}";
            $jobs = $this->cacheService->get($queueKey, fn() => []);
            
            $pending = 0;
            $processing = 0;
            $failed = 0;
            $completed = 0;
            
            foreach ($jobs as $jobId) {
                $job = $this->cacheService->get("job:{$jobId}");
                if ($job) {
                    switch ($job['status']) {
                        case 'pending':
                            $pending++;
                            break;
                        case 'processing':
                            $processing++;
                            break;
                        case 'failed':
                            $failed++;
                            break;
                        case 'completed':
                            $completed++;
                            break;
                    }
                }
            }
            
            $stats[$queue] = [
                'total' => count($jobs),
                'pending' => $pending,
                'processing' => $processing,
                'failed' => $failed,
                'completed' => $completed
            ];
        }
        
        // Failed jobs count
        $failedJobs = $this->cacheService->get('failed_jobs', fn() => []);
        $stats['failed_total'] = count($failedJobs);
        
        return $stats;
    }

    /**
     * Retry failed job
     */
    public function retryJob(string $jobId): bool
    {
        $job = $this->getJobStatus($jobId);
        
        if (!$job || $job['status'] !== 'failed') {
            return false;
        }
        
        // Reset job for retry
        $job['status'] = 'pending';
        $job['attempts'] = 0;
        $job['available_at'] = time();
        $job['error'] = null;
        
        // Add back to queue
        $queueKey = "queue:{$job['queue']}";
        $queueJobs = $this->cacheService->get($queueKey, fn() => []);
        $queueJobs[] = $jobId;
        $this->cacheService->set($queueKey, $queueJobs, 86400);
        
        // Remove from failed jobs
        $failedJobs = $this->cacheService->get('failed_jobs', fn() => []);
        $failedJobs = array_filter($failedJobs, fn($id) => $id !== $jobId);
        $this->cacheService->set('failed_jobs', $failedJobs, $this->config['failed_job_retention']);
        
        $this->cacheService->set("job:{$jobId}", $job, 86400);
        
        return true;
    }

    /**
     * Cancel job
     */
    public function cancelJob(string $jobId): bool
    {
        $job = $this->getJobStatus($jobId);
        
        if (!$job || $job['status'] === 'completed') {
            return false;
        }
        
        // Remove from queue
        $queueKey = "queue:{$job['queue']}";
        $queueJobs = $this->cacheService->get($queueKey, fn() => []);
        $queueJobs = array_filter($queueJobs, fn($id) => $id !== $jobId);
        $this->cacheService->set($queueKey, $queueJobs, 86400);
        
        // Mark as cancelled
        $job['status'] = 'cancelled';
        $job['cancelled_at'] = time();
        $this->cacheService->set("job:{$jobId}", $job, 3600);
        
        return true;
    }

    /**
     * Stop worker
     */
    public function stop(): void
    {
        $this->isRunning = false;
    }

    /**
     * Clean up expired cache
     */
    private function cleanupExpiredCache(): void
    {
        // In real implementation, clean expired cache entries
        error_log("Cleaning up expired cache entries");
    }

    /**
     * Clean up old logs
     */
    private function cleanupOldLogs(): void
    {
        // In real implementation, clean old log files
        error_log("Cleaning up old log files");
    }

    /**
     * Clean up temp files
     */
    private function cleanupTempFiles(): void
    {
        // In real implementation, clean temporary files
        error_log("Cleaning up temporary files");
    }

    /**
     * Schedule recurring job
     */
    public function schedule(string $type, array $data, string $cron): void
    {
        // Store scheduled job
        $scheduleId = uniqid('schedule_');
        $schedule = [
            'id' => $scheduleId,
            'type' => $type,
            'data' => $data,
            'cron' => $cron,
            'next_run' => $this->getNextCronTime($cron),
            'enabled' => true
        ];
        
        $this->cacheService->set("schedule:{$scheduleId}", $schedule, 86400 * 365);
        
        // Add to schedules list
        $schedules = $this->cacheService->get('schedules', fn() => []);
        $schedules[] = $scheduleId;
        $this->cacheService->set('schedules', $schedules, 86400 * 365);
    }

    /**
     * Process scheduled jobs
     */
    public function processScheduledJobs(): void
    {
        $schedules = $this->cacheService->get('schedules', fn() => []);
        
        foreach ($schedules as $scheduleId) {
            $schedule = $this->cacheService->get("schedule:{$scheduleId}");
            
            if (!$schedule || !$schedule['enabled']) {
                continue;
            }
            
            if ($schedule['next_run'] <= time()) {
                // Dispatch job
                $this->dispatch($schedule['type'], $schedule['data']);
                
                // Update next run time
                $schedule['next_run'] = $this->getNextCronTime($schedule['cron']);
                $this->cacheService->set("schedule:{$scheduleId}", $schedule, 86400 * 365);
            }
        }
    }

    /**
     * Get next cron execution time
     */
    private function getNextCronTime(string $cron): int
    {
        // Simple cron parser - in real implementation use proper cron library
        switch ($cron) {
            case '@hourly':
                return strtotime('+1 hour');
            case '@daily':
                return strtotime('+1 day');
            case '@weekly':
                return strtotime('+1 week');
            case '@monthly':
                return strtotime('+1 month');
            default:
                // Default to hourly
                return strtotime('+1 hour');
        }
    }

    /**
     * Render queue management UI
     */
    public function renderQueueManager(): string
    {
        $stats = $this->getQueueStats();
        
        $html = '
        <div class="queue-manager">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Queue Manager</h2>
                <div class="grid grid-cols-4 gap-4">';
        
        foreach ($this->queues as $queue) {
            $queueStats = $stats[$queue] ?? ['total' => 0, 'pending' => 0, 'processing' => 0, 'failed' => 0];
            
            $html .= '
                <div class="bg-white p-4 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">' . ucfirst($queue) . ' Queue</h3>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Pending:</span>
                            <span class="font-medium text-yellow-600">' . $queueStats['pending'] . '</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Processing:</span>
                            <span class="font-medium text-blue-600">' . $queueStats['processing'] . '</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Failed:</span>
                            <span class="font-medium text-red-600">' . $queueStats['failed'] . '</span>
                        </div>
                    </div>
                </div>';
        }
        
        $html .= '
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Queue Actions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Queue Actions</h3>
                    <div class="space-y-3">
                        <button onclick="startWorker()" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">
                            Start Worker
                        </button>
                        <button onclick="stopWorker()" class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700">
                            Stop Worker
                        </button>
                        <button onclick="retryFailedJobs()" class="w-full bg-yellow-600 text-white py-2 px-4 rounded-md hover:bg-yellow-700">
                            Retry Failed Jobs
                        </button>
                        <button onclick="clearQueue()" class="w-full bg-gray-600 text-white py-2 px-4 rounded-md hover:bg-gray-700">
                            Clear All Queues
                        </button>
                    </div>
                </div>
                
                <!-- Schedule Job -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Schedule Job</h3>
                    <form onsubmit="scheduleJob(event)">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Job Type</label>
                                <select name="type" class="mt-1 block w-full border-gray-300 rounded-md">
                                    <option value="email">Email</option>
                                    <option value="export">Export</option>
                                    <option value="cleanup">Cleanup</option>
                                    <option value="notification">Notification</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Queue</label>
                                <select name="queue" class="mt-1 block w-full border-gray-300 rounded-md">
                                    <option value="default">Default</option>
                                    <option value="high">High Priority</option>
                                    <option value="low">Low Priority</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Schedule</label>
                                <select name="schedule" class="mt-1 block w-full border-gray-300 rounded-md">
                                    <option value="now">Now</option>
                                    <option value="@hourly">Hourly</option>
                                    <option value="@daily">Daily</option>
                                    <option value="@weekly">Weekly</option>
                                </select>
                            </div>
                            <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">
                                Schedule Job
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        function startWorker() {
            fetch("/admin/queue/start", { method: "POST" })
                .then(response => response.json())
                .then(data => {
                    alert(data.success ? "Worker started" : "Failed to start worker");
                    if (data.success) location.reload();
                })
                .catch(error => alert("Error: " + error.message));
        }
        
        function stopWorker() {
            fetch("/admin/queue/stop", { method: "POST" })
                .then(response => response.json())
                .then(data => {
                    alert(data.success ? "Worker stopped" : "Failed to stop worker");
                    if (data.success) location.reload();
                })
                .catch(error => alert("Error: " + error.message));
        }
        
        function retryFailedJobs() {
            if (confirm("Retry all failed jobs?")) {
                fetch("/admin/queue/retry-failed", { method: "POST" })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message || "Jobs queued for retry");
                        location.reload();
                    })
                    .catch(error => alert("Error: " + error.message));
            }
        }
        
        function clearQueue() {
            if (confirm("Clear all queues? This cannot be undone.")) {
                fetch("/admin/queue/clear", { method: "DELETE" })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.success ? "Queues cleared" : "Failed to clear queues");
                        if (data.success) location.reload();
                    })
                    .catch(error => alert("Error: " + error.message));
            }
        }
        
        function scheduleJob(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch("/admin/queue/schedule", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.success ? "Job scheduled" : "Failed to schedule job");
                if (data.success) {
                    event.target.reset();
                    location.reload();
                }
            })
            .catch(error => alert("Error: " + error.message));
        }
        
        // Auto-refresh stats every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
        </script>';
        
        return $html;
    }
}
