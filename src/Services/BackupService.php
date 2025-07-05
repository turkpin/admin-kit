<?php

declare(strict_types=1);

namespace AdminKit\Services;

use Doctrine\ORM\EntityManagerInterface;
use ZipArchive;

class BackupService
{
    private EntityManagerInterface $entityManager;
    private string $backupPath;

    public function __construct(EntityManagerInterface $entityManager, string $backupPath = 'var/backups')
    {
        $this->entityManager = $entityManager;
        $this->backupPath = $backupPath;
        
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    /**
     * Create a full backup
     */
    public function createBackup(array $options = []): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $backupName = 'adminkit_backup_' . $timestamp;
        $backupDir = $this->backupPath . '/' . $backupName;
        
        mkdir($backupDir, 0755, true);
        
        // Backup database
        $this->backupDatabase($backupDir);
        
        // Backup files
        if ($options['include_files'] ?? true) {
            $this->backupFiles($backupDir, $options['file_paths'] ?? []);
        }
        
        // Create manifest
        $this->createManifest($backupDir, $options);
        
        // Create ZIP archive
        $zipFile = $this->backupPath . '/' . $backupName . '.zip';
        $this->createZipArchive($backupDir, $zipFile);
        
        // Cleanup temporary directory
        $this->removeDirectory($backupDir);
        
        return $zipFile;
    }

    /**
     * Restore from backup
     */
    public function restoreBackup(string $backupFile, array $options = []): bool
    {
        if (!file_exists($backupFile)) {
            throw new \Exception("Backup file not found: $backupFile");
        }
        
        $tempDir = $this->backupPath . '/restore_' . uniqid();
        
        // Extract backup
        if (!$this->extractZipArchive($backupFile, $tempDir)) {
            throw new \Exception("Failed to extract backup archive");
        }
        
        try {
            // Restore database
            if ($options['restore_database'] ?? true) {
                $this->restoreDatabase($tempDir);
            }
            
            // Restore files
            if ($options['restore_files'] ?? true) {
                $this->restoreFiles($tempDir, $options['target_paths'] ?? []);
            }
            
            return true;
        } finally {
            // Cleanup
            $this->removeDirectory($tempDir);
        }
    }

    /**
     * List available backups
     */
    public function listBackups(): array
    {
        $backups = [];
        $files = glob($this->backupPath . '/adminkit_backup_*.zip');
        
        foreach ($files as $file) {
            $backups[] = [
                'file' => $file,
                'name' => basename($file, '.zip'),
                'size' => filesize($file),
                'created' => filemtime($file),
                'formatted_size' => $this->formatBytes(filesize($file)),
                'formatted_date' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }
        
        // Sort by creation time, newest first
        usort($backups, function($a, $b) {
            return $b['created'] <=> $a['created'];
        });
        
        return $backups;
    }

    /**
     * Delete a backup
     */
    public function deleteBackup(string $backupFile): bool
    {
        if (file_exists($backupFile)) {
            return unlink($backupFile);
        }
        return false;
    }

    /**
     * Get backup info
     */
    public function getBackupInfo(string $backupFile): ?array
    {
        if (!file_exists($backupFile)) {
            return null;
        }
        
        $tempDir = $this->backupPath . '/info_' . uniqid();
        
        if (!$this->extractZipArchive($backupFile, $tempDir)) {
            return null;
        }
        
        $manifestFile = $tempDir . '/manifest.json';
        $info = null;
        
        if (file_exists($manifestFile)) {
            $info = json_decode(file_get_contents($manifestFile), true);
        }
        
        $this->removeDirectory($tempDir);
        
        return $info;
    }

    /**
     * Backup database
     */
    private function backupDatabase(string $backupDir): void
    {
        $connection = $this->entityManager->getConnection();
        $params = $connection->getParams();
        
        if ($params['driver'] === 'pdo_mysql') {
            $this->backupMysql($backupDir, $params);
        } elseif ($params['driver'] === 'pdo_sqlite') {
            $this->backupSqlite($backupDir, $params);
        } else {
            // Generic SQL dump
            $this->backupGeneric($backupDir);
        }
    }

    /**
     * Backup MySQL database
     */
    private function backupMysql(string $backupDir, array $params): void
    {
        $host = $params['host'] ?? 'localhost';
        $port = $params['port'] ?? 3306;
        $database = $params['dbname'];
        $username = $params['user'];
        $password = $params['password'] ?? '';
        
        $outputFile = $backupDir . '/database.sql';
        
        $command = sprintf(
            'mysqldump --host=%s --port=%d --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
            escapeshellarg($host),
            $port,
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($outputFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("MySQL backup failed");
        }
    }

    /**
     * Backup SQLite database
     */
    private function backupSqlite(string $backupDir, array $params): void
    {
        $dbFile = $params['path'];
        $outputFile = $backupDir . '/database.sqlite';
        
        if (!copy($dbFile, $outputFile)) {
            throw new \Exception("SQLite backup failed");
        }
    }

    /**
     * Generic database backup
     */
    private function backupGeneric(string $backupDir): void
    {
        // This would require implementing custom logic for each entity
        // For now, we'll create a simple JSON export
        $outputFile = $backupDir . '/database.json';
        $data = [];
        
        // This is a simplified approach - in a real implementation,
        // you'd iterate through all entities and export their data
        
        file_put_contents($outputFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Backup files
     */
    private function backupFiles(string $backupDir, array $filePaths = []): void
    {
        $defaultPaths = [
            'uploads',
            'public/assets',
            'config',
            '.env',
        ];
        
        $paths = array_merge($defaultPaths, $filePaths);
        $filesDir = $backupDir . '/files';
        mkdir($filesDir, 0755, true);
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $destination = $filesDir . '/' . basename($path);
                
                if (is_dir($path)) {
                    $this->copyDirectory($path, $destination);
                } else {
                    copy($path, $destination);
                }
            }
        }
    }

    /**
     * Create backup manifest
     */
    private function createManifest(string $backupDir, array $options): void
    {
        $manifest = [
            'version' => '1.0.7',
            'created_at' => date('Y-m-d H:i:s'),
            'timestamp' => time(),
            'type' => 'full',
            'options' => $options,
            'php_version' => PHP_VERSION,
            'adminkit_version' => '1.0.7',
            'files' => [],
        ];
        
        // Add file information
        $files = $this->scanDirectory($backupDir);
        foreach ($files as $file) {
            $relativePath = str_replace($backupDir . '/', '', $file);
            $manifest['files'][$relativePath] = [
                'size' => filesize($file),
                'hash' => md5_file($file),
                'modified' => filemtime($file),
            ];
        }
        
        file_put_contents($backupDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
    }

    /**
     * Restore database
     */
    private function restoreDatabase(string $backupDir): void
    {
        $connection = $this->entityManager->getConnection();
        $params = $connection->getParams();
        
        if (file_exists($backupDir . '/database.sql')) {
            $this->restoreMysql($backupDir, $params);
        } elseif (file_exists($backupDir . '/database.sqlite')) {
            $this->restoreSqlite($backupDir, $params);
        } elseif (file_exists($backupDir . '/database.json')) {
            $this->restoreGeneric($backupDir);
        }
    }

    /**
     * Restore MySQL database
     */
    private function restoreMysql(string $backupDir, array $params): void
    {
        $host = $params['host'] ?? 'localhost';
        $port = $params['port'] ?? 3306;
        $database = $params['dbname'];
        $username = $params['user'];
        $password = $params['password'] ?? '';
        
        $inputFile = $backupDir . '/database.sql';
        
        $command = sprintf(
            'mysql --host=%s --port=%d --user=%s --password=%s %s < %s',
            escapeshellarg($host),
            $port,
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($inputFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("MySQL restore failed");
        }
    }

    /**
     * Restore SQLite database
     */
    private function restoreSqlite(string $backupDir, array $params): void
    {
        $dbFile = $params['path'];
        $inputFile = $backupDir . '/database.sqlite';
        
        if (!copy($inputFile, $dbFile)) {
            throw new \Exception("SQLite restore failed");
        }
    }

    /**
     * Restore generic database
     */
    private function restoreGeneric(string $backupDir): void
    {
        $inputFile = $backupDir . '/database.json';
        $data = json_decode(file_get_contents($inputFile), true);
        
        // Implement custom restoration logic here
    }

    /**
     * Restore files
     */
    private function restoreFiles(string $backupDir, array $targetPaths = []): void
    {
        $filesDir = $backupDir . '/files';
        
        if (!is_dir($filesDir)) {
            return;
        }
        
        $items = scandir($filesDir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $source = $filesDir . '/' . $item;
            $destination = $item;
            
            if (is_dir($source)) {
                $this->copyDirectory($source, $destination);
            } else {
                copy($source, $destination);
            }
        }
    }

    /**
     * Create ZIP archive
     */
    private function createZipArchive(string $sourceDir, string $zipFile): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            return false;
        }
        
        $files = $this->scanDirectory($sourceDir);
        foreach ($files as $file) {
            $relativePath = str_replace($sourceDir . '/', '', $file);
            $zip->addFile($file, $relativePath);
        }
        
        return $zip->close();
    }

    /**
     * Extract ZIP archive
     */
    private function extractZipArchive(string $zipFile, string $destination): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== TRUE) {
            return false;
        }
        
        $result = $zip->extractTo($destination);
        $zip->close();
        
        return $result;
    }

    /**
     * Copy directory recursively
     */
    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $target = $destination . '/' . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item, $target);
            }
        }
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Scan directory recursively
     */
    private function scanDirectory(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
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
}
