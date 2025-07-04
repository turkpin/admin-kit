<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

class ExportService
{
    private array $config = [];
    private array $data = [];
    private array $headers = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'date_format' => 'd.m.Y H:i:s',
            'encoding' => 'UTF-8',
            'delimiter' => ';',
            'enclosure' => '"',
            'escape' => '"',
            'bom' => true
        ], $config);
    }

    /**
     * Set data to export
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Set headers
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Export to CSV
     */
    public function exportToCsv(string $filename = null): string
    {
        $filename = $filename ?: 'export_' . date('Y_m_d_H_i_s') . '.csv';
        
        // Prepare output
        $output = '';
        
        // Add BOM for UTF-8
        if ($this->config['bom']) {
            $output .= "\xEF\xBB\xBF";
        }
        
        // Add headers
        if (!empty($this->headers)) {
            $output .= $this->formatCsvRow($this->headers);
        }
        
        // Add data rows
        foreach ($this->data as $row) {
            if (is_object($row)) {
                $row = $this->objectToArray($row);
            }
            $output .= $this->formatCsvRow($row);
        }
        
        // Set headers for download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
        
        return $output;
    }

    /**
     * Export to Excel (basic CSV with Excel compatibility)
     */
    public function exportToExcel(string $filename = null): string
    {
        $filename = $filename ?: 'export_' . date('Y_m_d_H_i_s') . '.xlsx';
        
        // For now, we'll create Excel-compatible CSV
        // In production, you'd use a library like PhpSpreadsheet
        $this->config['delimiter'] = ',';
        $this->config['bom'] = true;
        
        $csvContent = $this->exportToCsv($filename);
        
        // Convert to proper Excel file would require PhpSpreadsheet
        // For now, return CSV with Excel MIME type
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        return $csvContent;
    }

    /**
     * Export to JSON
     */
    public function exportToJson(string $filename = null): string
    {
        $filename = $filename ?: 'export_' . date('Y_m_d_H_i_s') . '.json';
        
        $output = json_encode([
            'export_date' => date($this->config['date_format']),
            'total_records' => count($this->data),
            'headers' => $this->headers,
            'data' => $this->data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
        
        return $output;
    }

    /**
     * Export to XML
     */
    public function exportToXml(string $filename = null): string
    {
        $filename = $filename ?: 'export_' . date('Y_m_d_H_i_s') . '.xml';
        
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><export></export>');
        $xml->addChild('export_date', date($this->config['date_format']));
        $xml->addChild('total_records', count($this->data));
        
        $dataNode = $xml->addChild('data');
        
        foreach ($this->data as $index => $row) {
            if (is_object($row)) {
                $row = $this->objectToArray($row);
            }
            
            $recordNode = $dataNode->addChild('record');
            $recordNode->addAttribute('index', $index);
            
            foreach ($row as $key => $value) {
                $recordNode->addChild($this->sanitizeXmlTag($key), htmlspecialchars($value));
            }
        }
        
        $output = $xml->asXML();
        
        header('Content-Type: text/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
        
        return $output;
    }

    /**
     * Export to PDF (basic HTML to PDF)
     */
    public function exportToPdf(string $filename = null, array $options = []): string
    {
        $filename = $filename ?: 'export_' . date('Y_m_d_H_i_s') . '.pdf';
        
        $options = array_merge([
            'title' => 'Data Export',
            'orientation' => 'landscape',
            'paper_size' => 'A4'
        ], $options);
        
        // Generate HTML table
        $html = $this->generateHtmlTable($options);
        
        // In production, use a PDF library like TCPDF or mPDF
        // For now, return HTML with PDF headers
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        return $html;
    }

    /**
     * Format CSV row
     */
    protected function formatCsvRow(array $row): string
    {
        $formatted = [];
        
        foreach ($row as $value) {
            // Convert objects/arrays to string
            if (is_object($value) || is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            
            // Escape and enclose value
            $value = str_replace($this->config['enclosure'], $this->config['escape'] . $this->config['enclosure'], $value);
            $formatted[] = $this->config['enclosure'] . $value . $this->config['enclosure'];
        }
        
        return implode($this->config['delimiter'], $formatted) . "\n";
    }

    /**
     * Convert object to array
     */
    protected function objectToArray($object): array
    {
        if (is_object($object)) {
            $array = [];
            
            // Try to use getters
            $reflection = new \ReflectionClass($object);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $methodName = $method->getName();
                if (strpos($methodName, 'get') === 0 && strlen($methodName) > 3) {
                    $property = lcfirst(substr($methodName, 3));
                    $array[$property] = $method->invoke($object);
                }
            }
            
            // If no getters found, try properties
            if (empty($array)) {
                $array = get_object_vars($object);
            }
            
            return $array;
        }
        
        return (array)$object;
    }

    /**
     * Sanitize XML tag name
     */
    protected function sanitizeXmlTag(string $tag): string
    {
        // Remove invalid characters and ensure valid XML tag name
        $tag = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tag);
        if (is_numeric($tag[0])) {
            $tag = 'field_' . $tag;
        }
        return $tag;
    }

    /**
     * Generate HTML table for PDF export
     */
    protected function generateHtmlTable(array $options): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($options['title']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .header { text-align: center; margin-bottom: 20px; }
        .export-info { font-size: 10px; color: #666; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($options['title']) . '</h1>
        <div class="export-info">Exported on: ' . date($this->config['date_format']) . ' | Total records: ' . count($this->data) . '</div>
    </div>
    
    <table>';
        
        // Add headers
        if (!empty($this->headers)) {
            $html .= '<thead><tr>';
            foreach ($this->headers as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr></thead>';
        }
        
        // Add data
        $html .= '<tbody>';
        foreach ($this->data as $row) {
            if (is_object($row)) {
                $row = $this->objectToArray($row);
            }
            
            $html .= '<tr>';
            foreach ($row as $value) {
                if (is_object($value) || is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                $html .= '<td>' . htmlspecialchars($value) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        
        $html .= '</table>
</body>
</html>';
        
        return $html;
    }

    /**
     * Create export from table data
     */
    public static function fromTable(array $data, array $headers = []): self
    {
        $service = new self();
        $service->setData($data);
        
        if (!empty($headers)) {
            $service->setHeaders($headers);
        } elseif (!empty($data)) {
            // Auto-generate headers from first row
            $firstRow = reset($data);
            if (is_object($firstRow)) {
                $firstRow = $service->objectToArray($firstRow);
            }
            $service->setHeaders(array_keys($firstRow));
        }
        
        return $service;
    }

    /**
     * Quick export methods
     */
    public static function quickCsv(array $data, string $filename = null): string
    {
        return self::fromTable($data)->exportToCsv($filename);
    }

    public static function quickExcel(array $data, string $filename = null): string
    {
        return self::fromTable($data)->exportToExcel($filename);
    }

    public static function quickJson(array $data, string $filename = null): string
    {
        return self::fromTable($data)->exportToJson($filename);
    }

    public static function quickPdf(array $data, string $filename = null): string
    {
        return self::fromTable($data)->exportToPdf($filename);
    }

    /**
     * Get available export formats
     */
    public function getAvailableFormats(): array
    {
        return [
            'csv' => [
                'name' => 'CSV',
                'description' => 'Comma Separated Values',
                'extension' => 'csv',
                'mime_type' => 'text/csv'
            ],
            'excel' => [
                'name' => 'Excel',
                'description' => 'Microsoft Excel',
                'extension' => 'xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ],
            'json' => [
                'name' => 'JSON',
                'description' => 'JavaScript Object Notation',
                'extension' => 'json',
                'mime_type' => 'application/json'
            ],
            'xml' => [
                'name' => 'XML',
                'description' => 'Extensible Markup Language',
                'extension' => 'xml',
                'mime_type' => 'text/xml'
            ],
            'pdf' => [
                'name' => 'PDF',
                'description' => 'Portable Document Format',
                'extension' => 'pdf',
                'mime_type' => 'application/pdf'
            ]
        ];
    }

    /**
     * Export by format name
     */
    public function exportByFormat(string $format, string $filename = null): string
    {
        switch (strtolower($format)) {
            case 'csv':
                return $this->exportToCsv($filename);
            case 'excel':
            case 'xlsx':
                return $this->exportToExcel($filename);
            case 'json':
                return $this->exportToJson($filename);
            case 'xml':
                return $this->exportToXml($filename);
            case 'pdf':
                return $this->exportToPdf($filename);
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    /**
     * Filter data before export
     */
    public function filterData(callable $callback): self
    {
        $this->data = array_filter($this->data, $callback);
        return $this;
    }

    /**
     * Transform data before export
     */
    public function transformData(callable $callback): self
    {
        $this->data = array_map($callback, $this->data);
        return $this;
    }

    /**
     * Limit export data
     */
    public function limit(int $limit, int $offset = 0): self
    {
        $this->data = array_slice($this->data, $offset, $limit);
        return $this;
    }
}
