<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Turkpin\AdminKit\Services\ValidationService;

class ImportService
{
    private ValidationService $validator;
    private array $config = [];
    private array $fieldMapping = [];
    private array $validationRules = [];
    private array $errors = [];
    private array $warnings = [];

    public function __construct(ValidationService $validator = null, array $config = [])
    {
        $this->validator = $validator ?: new ValidationService();
        $this->config = array_merge([
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '"',
            'encoding' => 'UTF-8',
            'skip_empty_rows' => true,
            'max_rows' => 10000,
            'chunk_size' => 1000,
            'validate_data' => true,
            'allow_duplicates' => false
        ], $config);
    }

    /**
     * Set field mapping for import
     */
    public function setFieldMapping(array $mapping): self
    {
        $this->fieldMapping = $mapping;
        return $this;
    }

    /**
     * Set validation rules
     */
    public function setValidationRules(array $rules): self
    {
        $this->validationRules = $rules;
        return $this;
    }

    /**
     * Import from CSV file
     */
    public function importFromCsv(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        // Validate file
        $this->validateFile($filePath);

        // Parse CSV
        $data = $this->parseCsvFile($filePath);

        // Process data
        return $this->processImportData($data);
    }

    /**
     * Import from uploaded file
     */
    public function importFromUpload(array $uploadedFile): array
    {
        // Validate upload
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('File upload error: ' . $uploadedFile['error']);
        }

        // Validate file type
        $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'txt'])) {
            throw new \Exception('Unsupported file type. Only CSV files are allowed.');
        }

        // Process file
        return $this->importFromCsv($uploadedFile['tmp_name']);
    }

    /**
     * Import from JSON data
     */
    public function importFromJson(string $jsonData): array
    {
        $data = json_decode($jsonData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON data: ' . json_last_error_msg());
        }

        return $this->processImportData($data);
    }

    /**
     * Import from array data
     */
    public function importFromArray(array $data): array
    {
        return $this->processImportData($data);
    }

    /**
     * Validate file before import
     */
    protected function validateFile(string $filePath): void
    {
        // Check file size
        $fileSize = filesize($filePath);
        $maxSize = 50 * 1024 * 1024; // 50MB
        
        if ($fileSize > $maxSize) {
            throw new \Exception('File too large. Maximum size: ' . $this->formatBytes($maxSize));
        }

        // Check file encoding
        $content = file_get_contents($filePath, false, null, 0, 1024);
        if (!mb_check_encoding($content, $this->config['encoding'])) {
            throw new \Exception('File encoding must be ' . $this->config['encoding']);
        }
    }

    /**
     * Parse CSV file
     */
    protected function parseCsvFile(string $filePath): array
    {
        $data = [];
        $headers = [];
        $rowCount = 0;

        if (($handle = fopen($filePath, 'r')) !== false) {
            // Read headers
            if (($headerRow = fgetcsv($handle, 0, $this->config['delimiter'], $this->config['enclosure'], $this->config['escape'])) !== false) {
                $headers = array_map('trim', $headerRow);
                $this->validateHeaders($headers);
            }

            // Read data rows
            while (($row = fgetcsv($handle, 0, $this->config['delimiter'], $this->config['enclosure'], $this->config['escape'])) !== false && $rowCount < $this->config['max_rows']) {
                
                // Skip empty rows
                if ($this->config['skip_empty_rows'] && $this->isEmptyRow($row)) {
                    continue;
                }

                // Map row to headers
                $mappedRow = [];
                foreach ($headers as $index => $header) {
                    $mappedRow[$header] = isset($row[$index]) ? trim($row[$index]) : '';
                }

                $data[] = $mappedRow;
                $rowCount++;
            }

            fclose($handle);
        }

        return $data;
    }

    /**
     * Validate CSV headers
     */
    protected function validateHeaders(array $headers): void
    {
        if (empty($headers)) {
            throw new \Exception('CSV file must have headers');
        }

        // Check for duplicate headers
        $duplicates = array_diff_assoc($headers, array_unique($headers));
        if (!empty($duplicates)) {
            throw new \Exception('Duplicate headers found: ' . implode(', ', array_unique($duplicates)));
        }

        // Check required fields if mapping is set
        if (!empty($this->fieldMapping)) {
            $missingFields = array_diff(array_keys($this->fieldMapping), $headers);
            if (!empty($missingFields)) {
                throw new \Exception('Missing required fields: ' . implode(', ', $missingFields));
            }
        }
    }

    /**
     * Check if row is empty
     */
    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (!empty(trim($cell))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Process import data
     */
    protected function processImportData(array $data): array
    {
        $this->errors = [];
        $this->warnings = [];
        $processedData = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($data as $rowIndex => $row) {
            try {
                // Map fields
                $mappedRow = $this->mapRowFields($row);

                // Validate row
                if ($this->config['validate_data']) {
                    $validationErrors = $this->validateRow($mappedRow, $rowIndex + 2); // +2 for header and 0-index
                    if (!empty($validationErrors)) {
                        $this->errors = array_merge($this->errors, $validationErrors);
                        $errorCount++;
                        continue;
                    }
                }

                // Check for duplicates
                if (!$this->config['allow_duplicates']) {
                    if ($this->isDuplicateRow($mappedRow, $processedData)) {
                        $this->warnings[] = "Row " . ($rowIndex + 2) . ": Duplicate entry skipped";
                        continue;
                    }
                }

                // Process row data
                $processedRow = $this->processRowData($mappedRow);
                $processedData[] = $processedRow;
                $successCount++;

            } catch (\Exception $e) {
                $this->errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                $errorCount++;
            }
        }

        return [
            'success' => $errorCount === 0,
            'data' => $processedData,
            'stats' => [
                'total_rows' => count($data),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'warning_count' => count($this->warnings)
            ],
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }

    /**
     * Map row fields according to field mapping
     */
    protected function mapRowFields(array $row): array
    {
        if (empty($this->fieldMapping)) {
            return $row;
        }

        $mappedRow = [];
        foreach ($this->fieldMapping as $csvField => $entityField) {
            $mappedRow[$entityField] = $row[$csvField] ?? '';
        }

        return $mappedRow;
    }

    /**
     * Validate single row
     */
    protected function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];

        foreach ($this->validationRules as $field => $rules) {
            $value = $row[$field] ?? null;
            
            $this->validator->setRules([$field => $rules]);
            if (!$this->validator->validate([$field => $value])) {
                $fieldErrors = $this->validator->getFieldErrors($field);
                foreach ($fieldErrors as $error) {
                    $errors[] = "Row {$rowNumber}, Field '{$field}': {$error}";
                }
            }
        }

        return $errors;
    }

    /**
     * Check for duplicate rows
     */
    protected function isDuplicateRow(array $row, array $existingData): bool
    {
        foreach ($existingData as $existingRow) {
            if ($this->rowsAreEqual($row, $existingRow)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Compare two rows for equality
     */
    protected function rowsAreEqual(array $row1, array $row2): bool
    {
        // Compare by all fields or specific unique fields
        $compareFields = $this->getUniqueFields();
        
        if (empty($compareFields)) {
            return $row1 === $row2;
        }

        foreach ($compareFields as $field) {
            if (($row1[$field] ?? '') !== ($row2[$field] ?? '')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get unique fields for duplicate checking
     */
    protected function getUniqueFields(): array
    {
        // This would typically be configured per entity
        // For now, return common unique fields
        return ['email', 'username', 'code', 'id'];
    }

    /**
     * Process row data (convert types, format values, etc.)
     */
    protected function processRowData(array $row): array
    {
        $processedRow = [];

        foreach ($row as $field => $value) {
            $processedRow[$field] = $this->processFieldValue($field, $value);
        }

        return $processedRow;
    }

    /**
     * Process individual field value
     */
    protected function processFieldValue(string $field, $value)
    {
        // Convert empty strings to null
        if ($value === '') {
            return null;
        }

        // Type conversions based on field name patterns
        if (preg_match('/_(id|count|number|quantity)$/', $field)) {
            return is_numeric($value) ? (int)$value : $value;
        }

        if (preg_match('/_(price|amount|rate|percentage)$/', $field)) {
            return is_numeric($value) ? (float)$value : $value;
        }

        if (preg_match('/_(date|time)$/', $field)) {
            return $this->parseDateTime($value);
        }

        if (preg_match('/_(active|enabled|visible|published)$/', $field)) {
            return $this->parseBoolean($value);
        }

        // Default: return as string
        return (string)$value;
    }

    /**
     * Parse datetime value
     */
    protected function parseDateTime(string $value): ?\DateTime
    {
        if (empty($value)) {
            return null;
        }

        // Try different date formats
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd.m.Y H:i:s',
            'd.m.Y H:i',
            'd.m.Y',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y'
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false) {
                return $date;
            }
        }

        // Try strtotime as fallback
        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return new \DateTime('@' . $timestamp);
        }

        return null;
    }

    /**
     * Parse boolean value
     */
    protected function parseBoolean(string $value): bool
    {
        $value = strtolower(trim($value));
        
        $trueValues = ['1', 'true', 'yes', 'on', 'active', 'enabled', 'evet', 'aktif'];
        $falseValues = ['0', 'false', 'no', 'off', 'inactive', 'disabled', 'hayır', 'pasif'];

        if (in_array($value, $trueValues)) {
            return true;
        }

        if (in_array($value, $falseValues)) {
            return false;
        }

        // Default to false for unknown values
        return false;
    }

    /**
     * Get preview of import data
     */
    public function getPreview(string $filePath, int $rows = 5): array
    {
        $data = $this->parseCsvFile($filePath);
        return array_slice($data, 0, $rows);
    }

    /**
     * Get available columns from file
     */
    public function getAvailableColumns(string $filePath): array
    {
        if (($handle = fopen($filePath, 'r')) !== false) {
            if (($headerRow = fgetcsv($handle, 0, $this->config['delimiter'], $this->config['enclosure'], $this->config['escape'])) !== false) {
                fclose($handle);
                return array_map('trim', $headerRow);
            }
            fclose($handle);
        }

        return [];
    }

    /**
     * Generate field mapping suggestions
     */
    public function suggestFieldMapping(array $csvHeaders, array $entityFields): array
    {
        $suggestions = [];

        foreach ($csvHeaders as $csvHeader) {
            $normalizedHeader = $this->normalizeFieldName($csvHeader);
            
            foreach ($entityFields as $entityField) {
                $normalizedEntity = $this->normalizeFieldName($entityField);
                
                // Exact match
                if ($normalizedHeader === $normalizedEntity) {
                    $suggestions[$csvHeader] = $entityField;
                    break;
                }
                
                // Partial match
                if (strpos($normalizedEntity, $normalizedHeader) !== false || 
                    strpos($normalizedHeader, $normalizedEntity) !== false) {
                    $suggestions[$csvHeader] = $entityField;
                    break;
                }
            }
        }

        return $suggestions;
    }

    /**
     * Normalize field name for comparison
     */
    protected function normalizeFieldName(string $name): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    }

    /**
     * Format bytes for display
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get import errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get import warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Quick import methods
     */
    public static function quickCsvImport(string $filePath, array $fieldMapping = [], array $validationRules = []): array
    {
        $service = new self();
        $service->setFieldMapping($fieldMapping);
        $service->setValidationRules($validationRules);
        
        return $service->importFromCsv($filePath);
    }

    /**
     * Create import template
     */
    public function createTemplate(array $fields, string $filename = null): string
    {
        $filename = $filename ?: 'import_template_' . date('Y_m_d') . '.csv';
        
        // Create CSV with headers only
        $output = '';
        
        // Add BOM for UTF-8
        $output .= "\xEF\xBB\xBF";
        
        // Add headers
        $headers = array_map(function($field) {
            return '"' . str_replace('"', '""', $field) . '"';
        }, $fields);
        
        $output .= implode($this->config['delimiter'], $headers) . "\n";
        
        // Add example row
        $exampleRow = array_map(function($field) {
            return '"Example ' . $field . '"';
        }, $fields);
        
        $output .= implode($this->config['delimiter'], $exampleRow) . "\n";
        
        // Set headers for download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
        
        return $output;
    }
}
