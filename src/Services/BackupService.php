<?php

namespace AdminKit\Services;

use PDO;
use Exception;
use ZipArchive;

/**
 * AdminKit Backup Service v1.0.7
 * 
 * Comprehensive database backup and restore functionality
 * with compression, validation, and automatic scheduling
 */
class BackupService
{
    private PDO $pdo;
    private string $backupPath;
    private array $config;

    public function __construct(PDO $pdo, array $config = [])
    {
        $this->pdo = $pdo;
        $this->config = array_merge([
            'backup_path' => getcwd() . '/backups',
            'max_backups' => 10,
            'compression' => true,
            'include_data' => true,
            'include_structure' => true,
            'chunk_size' => 1000
        ], $config);
        
        $this->backupPath = $this->config['backup_path'];
        $this->ensureBackupDirectory();
    }

    /**
     * Create a database backup
     */
    public function createBackup(string $name = null): array
    {
        $timestamp = date('Y-m-d_H-i-s');
        $name = $name ?: "adminkit_backup_{$timestamp}";
        
        try {
            $filename = $this->generateBackupFilename($name);
            $sqlFile = $this->backupPath . '/' . $name . '.sql';
            
            // Generate SQL dump
            $this->generateSqlDump($sqlFile);
            
            // Compress if enabled
            if ($this->config['compression']) {
                $zipFile = $this->compressBackup($sqlFile, $filename);
                unlink($sqlFile); // Remove uncompressed file
                $finalFile = $zipFile;
            } else {
                $finalFile = $sqlFile;
            }
            
            // Clean old backups
            $this->cleanOldBackups();
            
            return [
                'success' => true,
                'filename' => basename($finalFile),
                'filepath' => $finalFile,
                'size' => filesize($finalFile),
                'created_at' => date('Y-m-d H:i:s'),
                'compressed' => $this->config['compression']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Restore database from backup
     */
    public function restoreBackup(string $filename): array
    {
        try {
            $filepath = $this->backupPath . '/' . $filename;
            
            if (!file_exists($filepath)) {
                throw new Exception("Backup file not found: {$filename}");
            }
            
            // Extract if compressed
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'zip') {
                $sqlFile = $this->extractBackup($filepath);
            } else {
                $sqlFile = $filepath;
            }
            
            // Validate SQL file
            if (!$this->validateSqlFile($sqlFile)) {
                throw new Exception("Invalid SQL backup file");
            }
            
            // Execute restore
            $this->executeSqlFile($sqlFile);
            
            // Clean up extracted file if it was compressed
            if ($sqlFile !== $filepath && file_exists($sqlFile)) {
                unlink($sqlFile);
            }
            
            return [
                'success' => true,
                'message' => 'Database restored successfully',
                'restored_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List available backups
     */
    public function listBackups(): array
    {
        $backups = [];
        $files = glob($this->backupPath . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $backups[] = [
                    'filename' => basename($file),
                    'filepath' => $file,
                    'size' => filesize($file),
                    'size_formatted' => $this->formatFileSize(filesize($file)),
                    'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                    'is_compressed' => pathinfo($file, PATHINFO_EXTENSION) === 'zip'
                ];
            }
        }
        
        // Sort by creation time (newest first)
        usort($backups, function($a, $b) {
            return filemtime($b['filepath']) - filemtime($a['filepath']);
        });
        
        return $backups;
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup(string $filename): bool
    {
        $filepath = $this->backupPath . '/' . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }

    /**
     * Get backup statistics
     */
    public function getBackupStats(): array
    {
        $backups = $this->listBackups();
        $totalSize = array_sum(array_column($backups, 'size'));
        
        return [
            'total_backups' => count($backups),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatFileSize($totalSize),
            'oldest_backup' => end($backups)['created_at'] ?? null,
            'newest_backup' => $backups[0]['created_at'] ?? null,
            'backup_path' => $this->backupPath
        ];
    }

    /**
     * Validate database connection and permissions
     */
    public function validateEnvironment(): array
    {
        $issues = [];
        
        // Check database connection
        try {
            $this->pdo->query("SELECT 1");
        } catch (Exception $e) {
            $issues[] = "Database connection failed: " . $e->getMessage();
        }
        
        // Check backup directory
        if (!is_dir($this->backupPath)) {
            $issues[] = "Backup directory does not exist: {$this->backupPath}";
        } elseif (!is_writable($this->backupPath)) {
            $issues[] = "Backup directory is not writable: {$this->backupPath}";
        }
        
        // Check required extensions
        if ($this->config['compression'] && !extension_loaded('zip')) {
            $issues[] = "ZIP extension is required for compression";
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues
        ];
    }

    /**
     * Generate SQL dump
     */
    private function generateSqlDump(string $filename): void
    {
        $sql = "-- AdminKit Database Backup\n";
        $sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Generator: AdminKit BackupService v1.0.7\n\n";
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET AUTOCOMMIT = 0;\n";
        $sql .= "START TRANSACTION;\n\n";
        
        // Get all tables
        $tables = $this->getTables();
        
        foreach ($tables as $table) {
            if ($this->config['include_structure']) {
                $sql .= $this->getTableStructure($table);
            }
            
            if ($this->config['include_data']) {
                $sql .= $this->getTableData($table);
            }
        }
        
        $sql .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
        $sql .= "COMMIT;\n";
        
        file_put_contents($filename, $sql);
    }

    /**
     * Get all tables in database
     */
    private function getTables(): array
    {
        $stmt = $this->pdo->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get table structure (CREATE TABLE statement)
     */
    private function getTableStructure(string $table): string
    {
        $stmt = $this->pdo->query("SHOW CREATE TABLE `{$table}`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $sql = "\n-- Table structure for table `{$table}`\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $row['Create Table'] . ";\n\n";
        
        return $sql;
    }

    /**
     * Get table data (INSERT statements)
     */
    private function getTableData(string $table): string
    {
        $sql = "-- Dumping data for table `{$table}`\n";
        
        // Get column names
        $stmt = $this->pdo->query("DESCRIBE `{$table}`");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $columnList = '`' . implode('`, `', $columns) . '`';
        
        // Get row count
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM `{$table}`");
        $rowCount = $stmt->fetchColumn();
        
        if ($rowCount > 0) {
            $sql .= "LOCK TABLES `{$table}` WRITE;\n";
            
            // Process data in chunks
            $offset = 0;
            while ($offset < $rowCount) {
                $stmt = $this->pdo->query(
                    "SELECT * FROM `{$table}` LIMIT {$this->config['chunk_size']} OFFSET {$offset}"
                );
                
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($rows)) {
                    $sql .= "INSERT INTO `{$table}` ({$columnList}) VALUES\n";
                    
                    $values = [];
                    foreach ($rows as $row) {
                        $escapedValues = array_map(function($value) {
                            return $value === null ? 'NULL' : $this->pdo->quote($value);
                        }, array_values($row));
                        
                        $values[] = '(' . implode(', ', $escapedValues) . ')';
                    }
                    
                    $sql .= implode(",\n", $values) . ";\n";
                }
                
                $offset += $this->config['chunk_size'];
            }
            
            $sql .= "UNLOCK TABLES;\n";
        }
        
        return $sql . "\n";
    }

    /**
     * Compress backup file
     */
    private function compressBackup(string $sqlFile, string $zipFilename): string
    {
        $zip = new ZipArchive();
        $zipPath = $this->backupPath . '/' . $zipFilename;
        
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("Cannot create zip file: {$zipPath}");
        }
        
        $zip->addFile($sqlFile, basename($sqlFile));
        $zip->close();
        
        return $zipPath;
    }

    /**
     * Extract compressed backup
     */
    private function extractBackup(string $zipFile): string
    {
        $zip = new ZipArchive();
        
        if ($zip->open($zipFile) !== TRUE) {
            throw new Exception("Cannot open zip file: {$zipFile}");
        }
        
        $extractPath = $this->backupPath . '/temp_' . uniqid();
        mkdir($extractPath);
        
        $zip->extractTo($extractPath);
        $zip->close();
        
        // Find SQL file
        $files = glob($extractPath . '/*.sql');
        if (empty($files)) {
            throw new Exception("No SQL file found in backup");
        }
        
        return $files[0];
    }

    /**
     * Validate SQL file
     */
    private function validateSqlFile(string $sqlFile): bool
    {
        if (!file_exists($sqlFile)) {
            return false;
        }
        
        $content = file_get_contents($sqlFile);
        
        // Basic validation - check for SQL keywords
        return strpos($content, 'CREATE TABLE') !== false || 
               strpos($content, 'INSERT INTO') !== false;
    }

    /**
     * Execute SQL file
     */
    private function executeSqlFile(string $sqlFile): void
    {
        $sql = file_get_contents($sqlFile);
        
        // Split by statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql))
        );
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !str_starts_with($statement, '--')) {
                $this->pdo->exec($statement);
            }
        }
    }

    /**
     * Generate backup filename
     */
    private function generateBackupFilename(string $name): string
    {
        $extension = $this->config['compression'] ? 'zip' : 'sql';
        return $name . '.' . $extension;
    }

    /**
     * Ensure backup directory exists
     */
    private function ensureBackupDirectory(): void
    {
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    /**
     * Clean old backups
     */
    private function cleanOldBackups(): void
    {
        $backups = $this->listBackups();
        
        if (count($backups) > $this->config['max_backups']) {
            $toDelete = array_slice($backups, $this->config['max_backups']);
            
            foreach ($toDelete as $backup) {
                $this->deleteBackup($backup['filename']);
            }
        }
    }

    /**
     * Format file size
     */
    private function formatFileSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($size) - 1) / 3);
        
        return sprintf("%.2f", $size / pow(1024, $factor)) . ' ' . $units[$factor];
    }
}
