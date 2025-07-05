<?php

declare(strict_types=1);

namespace AdminKit\Services;

use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;

class ImportExportService
{
    private EntityManagerInterface $entityManager;
    private array $supportedFormats = ['csv', 'xlsx', 'json', 'xml'];

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Export data to various formats
     */
    public function exportData(string $entityClass, string $format = 'csv', array $options = []): string
    {
        if (!in_array($format, $this->supportedFormats)) {
            throw new \InvalidArgumentException("Unsupported format: $format");
        }

        $repository = $this->entityManager->getRepository($entityClass);
        $queryBuilder = $repository->createQueryBuilder('e');

        // Apply filters if provided
        if (!empty($options['filters'])) {
            foreach ($options['filters'] as $field => $value) {
                $queryBuilder->andWhere("e.$field = :$field")
                           ->setParameter($field, $value);
            }
        }

        // Apply sorting
        if (!empty($options['orderBy'])) {
            foreach ($options['orderBy'] as $field => $direction) {
                $queryBuilder->addOrderBy("e.$field", $direction);
            }
        }

        // Apply limit
        if (!empty($options['limit'])) {
            $queryBuilder->setMaxResults($options['limit']);
        }

        $entities = $queryBuilder->getQuery()->getResult();

        return match($format) {
            'csv' => $this->exportToCsv($entities, $options),
            'xlsx' => $this->exportToXlsx($entities, $options),
            'json' => $this->exportToJson($entities, $options),
            'xml' => $this->exportToXml($entities, $options),
        };
    }

    /**
     * Import data from various formats
     */
    public function importData(string $entityClass, string $filePath, array $options = []): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: $filePath");
        }

        $format = $this->detectFormat($filePath);
        
        $data = match($format) {
            'csv' => $this->importFromCsv($filePath, $options),
            'xlsx' => $this->importFromXlsx($filePath, $options),
            'json' => $this->importFromJson($filePath, $options),
            'xml' => $this->importFromXml($filePath, $options),
            default => throw new \InvalidArgumentException("Unsupported file format")
        };

        return $this->processImportData($entityClass, $data, $options);
    }

    /**
     * Export to CSV format
     */
    private function exportToCsv(array $entities, array $options): string
    {
        $outputPath = $options['output_path'] ?? sys_get_temp_dir() . '/export_' . uniqid() . '.csv';
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if (empty($entities)) {
            file_put_contents($outputPath, '');
            return $outputPath;
        }

        // Get field names from first entity
        $fields = $this->getEntityFields($entities[0], $options);
        
        // Write headers
        $col = 1;
        foreach ($fields as $field) {
            $sheet->setCellValueByColumnAndRow($col, 1, $this->formatHeader($field));
            $col++;
        }

        // Write data
        $row = 2;
        foreach ($entities as $entity) {
            $col = 1;
            foreach ($fields as $field) {
                $value = $this->getEntityValue($entity, $field);
                $sheet->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }
            $row++;
        }

        $writer = new Csv($spreadsheet);
        $writer->setDelimiter($options['delimiter'] ?? ',');
        $writer->setEnclosure($options['enclosure'] ?? '"');
        $writer->save($outputPath);

        return $outputPath;
    }

    /**
     * Export to XLSX format
     */
    private function exportToXlsx(array $entities, array $options): string
    {
        $outputPath = $options['output_path'] ?? sys_get_temp_dir() . '/export_' . uniqid() . '.xlsx';
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($options['sheet_name'] ?? 'Export');

        if (empty($entities)) {
            $writer = new Xlsx($spreadsheet);
            $writer->save($outputPath);
            return $outputPath;
        }

        // Get field names
        $fields = $this->getEntityFields($entities[0], $options);
        
        // Write headers with styling
        $col = 1;
        foreach ($fields as $field) {
            $cell = $sheet->getCellByColumnAndRow($col, 1);
            $cell->setValue($this->formatHeader($field));
            $cell->getStyle()->getFont()->setBold(true);
            $cell->getStyle()->getFill()
                 ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                 ->getStartColor()->setRGB('E0E0E0');
            $col++;
        }

        // Write data
        $row = 2;
        foreach ($entities as $entity) {
            $col = 1;
            foreach ($fields as $field) {
                $value = $this->getEntityValue($entity, $field);
                $sheet->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range(1, count($fields)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($outputPath);

        return $outputPath;
    }

    /**
     * Export to JSON format
     */
    private function exportToJson(array $entities, array $options): string
    {
        $outputPath = $options['output_path'] ?? sys_get_temp_dir() . '/export_' . uniqid() . '.json';
        
        $data = [];
        foreach ($entities as $entity) {
            $fields = $this->getEntityFields($entity, $options);
            $row = [];
            
            foreach ($fields as $field) {
                $row[$field] = $this->getEntityValue($entity, $field);
            }
            
            $data[] = $row;
        }

        $jsonOptions = JSON_PRETTY_PRINT;
        if ($options['compact'] ?? false) {
            $jsonOptions = 0;
        }

        file_put_contents($outputPath, json_encode($data, $jsonOptions));
        
        return $outputPath;
    }

    /**
     * Export to XML format
     */
    private function exportToXml(array $entities, array $options): string
    {
        $outputPath = $options['output_path'] ?? sys_get_temp_dir() . '/export_' . uniqid() . '.xml';
        
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $root = $xml->createElement($options['root_element'] ?? 'data');
        $xml->appendChild($root);

        foreach ($entities as $entity) {
            $fields = $this->getEntityFields($entity, $options);
            $item = $xml->createElement($options['item_element'] ?? 'item');
            
            foreach ($fields as $field) {
                $value = $this->getEntityValue($entity, $field);
                $element = $xml->createElement($field, htmlspecialchars($value));
                $item->appendChild($element);
            }
            
            $root->appendChild($item);
        }

        $xml->save($outputPath);
        
        return $outputPath;
    }

    /**
     * Import from CSV format
     */
    private function importFromCsv(string $filePath, array $options): array
    {
        $reader = new CsvReader();
        $reader->setDelimiter($options['delimiter'] ?? ',');
        $reader->setEnclosure($options['enclosure'] ?? '"');
        
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        $data = [];
        $headers = [];
        
        // Read headers
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();
        
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $headers[] = $sheet->getCell($col . '1')->getValue();
        }
        
        // Read data
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            $colIndex = 0;
            
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $value = $sheet->getCell($col . $row)->getValue();
                $rowData[$headers[$colIndex]] = $value;
                $colIndex++;
            }
            
            $data[] = $rowData;
        }
        
        return $data;
    }

    /**
     * Import from XLSX format
     */
    private function importFromXlsx(string $filePath, array $options): array
    {
        $reader = new XlsxReader();
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        $data = [];
        $headers = [];
        
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();
        
        // Read headers
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $headers[] = $sheet->getCell($col . '1')->getValue();
        }
        
        // Read data
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            $colIndex = 0;
            
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $value = $sheet->getCell($col . $row)->getValue();
                $rowData[$headers[$colIndex]] = $value;
                $colIndex++;
            }
            
            $data[] = $rowData;
        }
        
        return $data;
    }

    /**
     * Import from JSON format
     */
    private function importFromJson(string $filePath, array $options): array
    {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON file');
        }
        
        return $data;
    }

    /**
     * Import from XML format
     */
    private function importFromXml(string $filePath, array $options): array
    {
        $xml = simplexml_load_file($filePath);
        if ($xml === false) {
            throw new \InvalidArgumentException('Invalid XML file');
        }
        
        $data = [];
        foreach ($xml->children() as $item) {
            $row = [];
            foreach ($item->children() as $field => $value) {
                $row[$field] = (string)$value;
            }
            $data[] = $row;
        }
        
        return $data;
    }

    /**
     * Process imported data and create entities
     */
    private function processImportData(string $entityClass, array $data, array $options): array
    {
        $results = [
            'success' => 0,
            'errors' => 0,
            'warnings' => [],
            'created' => [],
            'updated' => [],
            'skipped' => [],
        ];

        foreach ($data as $row) {
            try {
                $entity = $this->createOrUpdateEntity($entityClass, $row, $options);
                
                if ($entity) {
                    $this->entityManager->persist($entity);
                    $results['created'][] = $entity;
                    $results['success']++;
                } else {
                    $results['skipped'][] = $row;
                }
            } catch (\Exception $e) {
                $results['errors']++;
                $results['warnings'][] = [
                    'row' => $row,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($options['flush'] ?? true) {
            $this->entityManager->flush();
        }

        return $results;
    }

    /**
     * Create or update entity from data
     */
    private function createOrUpdateEntity(string $entityClass, array $data, array $options)
    {
        // This is a simplified implementation
        // In a real application, you would use reflection or mapping
        // to properly create and populate entities
        
        $entity = new $entityClass();
        
        foreach ($data as $field => $value) {
            $setter = 'set' . ucfirst($field);
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }
        
        return $entity;
    }

    /**
     * Get entity fields for export
     */
    private function getEntityFields($entity, array $options): array
    {
        if (!empty($options['fields'])) {
            return $options['fields'];
        }

        // Use reflection to get public properties and methods
        $reflection = new \ReflectionClass($entity);
        $fields = [];

        foreach ($reflection->getMethods() as $method) {
            if (strpos($method->getName(), 'get') === 0 && $method->isPublic()) {
                $field = lcfirst(substr($method->getName(), 3));
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Get entity value for field
     */
    private function getEntityValue($entity, string $field)
    {
        $getter = 'get' . ucfirst($field);
        
        if (method_exists($entity, $getter)) {
            $value = $entity->$getter();
            
            // Handle different data types
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d H:i:s');
            } elseif (is_object($value)) {
                return method_exists($value, '__toString') ? (string)$value : get_class($value);
            } elseif (is_array($value)) {
                return implode(', ', $value);
            }
            
            return $value;
        }

        return null;
    }

    /**
     * Format header for display
     */
    private function formatHeader(string $field): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $field));
    }

    /**
     * Detect file format from extension
     */
    private function detectFormat(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        return match($extension) {
            'csv' => 'csv',
            'xlsx', 'xls' => 'xlsx',
            'json' => 'json',
            'xml' => 'xml',
            default => throw new \InvalidArgumentException("Unsupported file extension: $extension")
        };
    }

    /**
     * Get supported formats
     */
    public function getSupportedFormats(): array
    {
        return $this->supportedFormats;
    }

    /**
     * Validate import file
     */
    public function validateImportFile(string $filePath, array $options = []): array
    {
        $errors = [];
        
        if (!file_exists($filePath)) {
            $errors[] = 'File does not exist';
            return $errors;
        }

        if (!is_readable($filePath)) {
            $errors[] = 'File is not readable';
            return $errors;
        }

        $format = $this->detectFormat($filePath);
        
        try {
            $data = match($format) {
                'csv' => $this->importFromCsv($filePath, $options),
                'xlsx' => $this->importFromXlsx($filePath, $options),
                'json' => $this->importFromJson($filePath, $options),
                'xml' => $this->importFromXml($filePath, $options),
            };
            
            if (empty($data)) {
                $errors[] = 'File contains no data';
            }
            
            if (isset($options['required_fields'])) {
                $firstRow = $data[0] ?? [];
                $missing = array_diff($options['required_fields'], array_keys($firstRow));
                
                if (!empty($missing)) {
                    $errors[] = 'Missing required fields: ' . implode(', ', $missing);
                }
            }
            
        } catch (\Exception $e) {
            $errors[] = 'File format error: ' . $e->getMessage();
        }
        
        return $errors;
    }
}
