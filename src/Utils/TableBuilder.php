<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Utils;

use Turkpin\AdminKit\Fields\FieldTypeInterface;

class TableBuilder
{
    private array $columns = [];
    private array $fieldTypes = [];
    private array $data = [];
    private array $config = [];
    private array $filters = [];
    private array $sorting = [];
    private array $pagination = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'css_class' => 'admin-table',
            'sortable' => true,
            'searchable' => true,
            'show_actions' => true,
            'show_selection' => true,
            'striped' => true,
            'bordered' => true,
            'responsive' => true,
            'actions_column_width' => '120px'
        ], $config);

        $this->registerDefaultFieldTypes();
    }

    /**
     * Register field type for column rendering
     */
    public function registerFieldType(string $name, FieldTypeInterface $fieldType): self
    {
        $this->fieldTypes[$name] = $fieldType;
        return $this;
    }

    /**
     * Add column to table
     */
    public function addColumn(string $name, string $type = 'text', array $options = []): self
    {
        if (!isset($this->fieldTypes[$type])) {
            throw new \InvalidArgumentException("Field type '{$type}' is not registered.");
        }

        $this->columns[$name] = [
            'type' => $type,
            'options' => array_merge([
                'label' => ucfirst($name),
                'sortable' => true,
                'searchable' => true,
                'width' => null,
                'align' => 'left',
                'format' => null,
                'callback' => null,
                'hidden' => false
            ], $options)
        ];

        return $this;
    }

    /**
     * Remove column
     */
    public function removeColumn(string $name): self
    {
        unset($this->columns[$name]);
        return $this;
    }

    /**
     * Set table data
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Set filters
     */
    public function setFilters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Set sorting
     */
    public function setSorting(array $sorting): self
    {
        $this->sorting = $sorting;
        return $this;
    }

    /**
     * Set pagination
     */
    public function setPagination(array $pagination): self
    {
        $this->pagination = $pagination;
        return $this;
    }

    /**
     * Render complete table
     */
    public function render(): string
    {
        $html = '<div class="table-container">';
        
        // Table filters
        if ($this->config['searchable'] && !empty($this->filters)) {
            $html .= $this->renderFilters();
        }

        // Table wrapper
        $tableClass = $this->config['css_class'];
        if ($this->config['striped']) $tableClass .= ' table-striped';
        if ($this->config['bordered']) $tableClass .= ' table-bordered';
        if ($this->config['responsive']) $tableClass .= ' table-responsive';

        $html .= '<div class="table-wrapper overflow-x-auto">';
        $html .= '<table class="' . htmlspecialchars($tableClass) . '"';
        if ($this->config['sortable']) {
            $html .= ' data-sortable="true"';
        }
        $html .= '>';

        // Table header
        $html .= $this->renderHeader();

        // Table body
        $html .= $this->renderBody();

        $html .= '</table>';
        $html .= '</div>';

        // Pagination
        if (!empty($this->pagination)) {
            $html .= $this->renderPagination();
        }

        $html .= '</div>';

        // Add JavaScript for interactive features
        $html .= $this->renderJavaScript();

        return $html;
    }

    /**
     * Render table header
     */
    protected function renderHeader(): string
    {
        $html = '<thead>';
        $html .= '<tr>';

        // Selection column
        if ($this->config['show_selection']) {
            $html .= '<th class="w-8">';
            $html .= '<input type="checkbox" id="selectAll" class="form-checkbox">';
            $html .= '</th>';
        }

        // Data columns
        foreach ($this->columns as $columnName => $columnConfig) {
            if ($columnConfig['options']['hidden']) continue;

            $html .= '<th';
            
            // Width
            if ($columnConfig['options']['width']) {
                $html .= ' style="width: ' . htmlspecialchars($columnConfig['options']['width']) . '"';
            }
            
            // Sorting
            if ($this->config['sortable'] && $columnConfig['options']['sortable']) {
                $html .= ' data-sortable="' . htmlspecialchars($columnName) . '"';
                $html .= ' class="cursor-pointer hover:bg-gray-50"';
            }
            
            $html .= '>';
            
            // Column content
            $html .= '<div class="flex items-center space-x-1">';
            $html .= '<span>' . htmlspecialchars($columnConfig['options']['label']) . '</span>';
            
            if ($this->config['sortable'] && $columnConfig['options']['sortable']) {
                $sortDirection = '';
                if (isset($this->sorting['field']) && $this->sorting['field'] === $columnName) {
                    $sortDirection = $this->sorting['direction'] === 'ASC' ? '▲' : '▼';
                }
                $html .= '<span class="sort-indicator text-gray-400">' . ($sortDirection ?: '↕️') . '</span>';
            }
            
            $html .= '</div>';
            $html .= '</th>';
        }

        // Actions column
        if ($this->config['show_actions']) {
            $html .= '<th style="width: ' . $this->config['actions_column_width'] . '">İşlemler</th>';
        }

        $html .= '</tr>';
        $html .= '</thead>';

        return $html;
    }

    /**
     * Render table body
     */
    protected function renderBody(): string
    {
        $html = '<tbody>';

        if (empty($this->data)) {
            $colSpan = count($this->columns);
            if ($this->config['show_selection']) $colSpan++;
            if ($this->config['show_actions']) $colSpan++;

            $html .= '<tr>';
            $html .= '<td colspan="' . $colSpan . '" class="text-center py-8 text-gray-500">';
            $html .= 'Gösterilecek veri bulunamadı.';
            $html .= '</td>';
            $html .= '</tr>';
        } else {
            foreach ($this->data as $index => $row) {
                $html .= $this->renderRow($row, $index);
            }
        }

        $html .= '</tbody>';

        return $html;
    }

    /**
     * Render single row
     */
    protected function renderRow($row, int $index): string
    {
        $html = '<tr class="hover:bg-gray-50" data-row="' . $index . '">';

        // Selection column
        if ($this->config['show_selection']) {
            $rowId = $this->getRowId($row);
            $html .= '<td>';
            $html .= '<input type="checkbox" name="selected[]" value="' . htmlspecialchars($rowId) . '" class="form-checkbox">';
            $html .= '</td>';
        }

        // Data columns
        foreach ($this->columns as $columnName => $columnConfig) {
            if ($columnConfig['options']['hidden']) continue;

            $html .= '<td';
            
            // Alignment
            $align = $columnConfig['options']['align'];
            if ($align !== 'left') {
                $html .= ' class="text-' . htmlspecialchars($align) . '"';
            }
            
            $html .= '>';
            $html .= $this->renderCell($row, $columnName, $columnConfig);
            $html .= '</td>';
        }

        // Actions column
        if ($this->config['show_actions']) {
            $html .= '<td>';
            $html .= $this->renderActions($row, $index);
            $html .= '</td>';
        }

        $html .= '</tr>';

        return $html;
    }

    /**
     * Render cell content
     */
    protected function renderCell($row, string $columnName, array $columnConfig): string
    {
        $value = $this->getCellValue($row, $columnName);
        
        // Custom callback
        if ($columnConfig['options']['callback'] && is_callable($columnConfig['options']['callback'])) {
            return call_user_func($columnConfig['options']['callback'], $value, $row, $columnName);
        }

        // Use field type renderer
        $fieldType = $this->fieldTypes[$columnConfig['type']];
        return $fieldType->renderDisplay($value, $columnConfig['options']);
    }

    /**
     * Get cell value from row data
     */
    protected function getCellValue($row, string $columnName)
    {
        if (is_array($row)) {
            return $row[$columnName] ?? null;
        } elseif (is_object($row)) {
            // Try getter method first
            $getter = 'get' . ucfirst($columnName);
            if (method_exists($row, $getter)) {
                return $row->$getter();
            }
            // Try property access
            if (property_exists($row, $columnName)) {
                return $row->$columnName;
            }
        }
        
        return null;
    }

    /**
     * Get row ID for selection
     */
    protected function getRowId($row): string
    {
        if (is_array($row)) {
            return $row['id'] ?? uniqid();
        } elseif (is_object($row)) {
            if (method_exists($row, 'getId')) {
                return (string)$row->getId();
            }
            if (property_exists($row, 'id')) {
                return (string)$row->id;
            }
        }
        
        return uniqid();
    }

    /**
     * Render action buttons
     */
    protected function renderActions($row, int $index): string
    {
        $rowId = $this->getRowId($row);
        
        $html = '<div class="flex space-x-1">';
        
        // View action
        $html .= '<button onclick="viewRecord(\'' . htmlspecialchars($rowId) . '\')" ';
        $html .= 'class="btn btn-sm btn-secondary" title="Görüntüle">';
        $html .= '👁️';
        $html .= '</button>';
        
        // Edit action
        $html .= '<button onclick="editRecord(\'' . htmlspecialchars($rowId) . '\')" ';
        $html .= 'class="btn btn-sm btn-warning" title="Düzenle">';
        $html .= '✏️';
        $html .= '</button>';
        
        // Delete action
        $html .= '<button onclick="deleteRecord(\'' . htmlspecialchars($rowId) . '\')" ';
        $html .= 'class="btn btn-sm btn-danger" title="Sil">';
        $html .= '🗑️';
        $html .= '</button>';
        
        $html .= '</div>';

        return $html;
    }

    /**
     * Render filters
     */
    protected function renderFilters(): string
    {
        $html = '<div class="table-filters bg-gray-50 p-4 rounded-lg mb-4">';
        $html .= '<form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">';
        
        // Search input
        $html .= '<div>';
        $html .= '<input type="text" name="search" placeholder="Ara..." ';
        $html .= 'value="' . htmlspecialchars($this->filters['search'] ?? '') . '" ';
        $html .= 'class="form-input">';
        $html .= '</div>';
        
        // Column filters
        foreach ($this->columns as $columnName => $columnConfig) {
            if (!$columnConfig['options']['searchable']) continue;
            
            $html .= '<div>';
            $html .= '<select name="filter_' . htmlspecialchars($columnName) . '" class="form-select">';
            $html .= '<option value="">Tüm ' . htmlspecialchars($columnConfig['options']['label']) . '</option>';
            // Filter options would be populated based on data
            $html .= '</select>';
            $html .= '</div>';
        }
        
        // Filter buttons
        $html .= '<div class="flex space-x-2">';
        $html .= '<button type="submit" class="btn btn-primary">Filtrele</button>';
        $html .= '<a href="?" class="btn btn-secondary">Temizle</a>';
        $html .= '</div>';
        
        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render pagination
     */
    protected function renderPagination(): string
    {
        if (empty($this->pagination) || $this->pagination['total_pages'] <= 1) {
            return '';
        }

        $current = $this->pagination['current_page'];
        $total = $this->pagination['total_pages'];
        $prev = max(1, $current - 1);
        $next = min($total, $current + 1);

        $html = '<div class="table-pagination flex items-center justify-between mt-4">';
        
        // Info
        $html .= '<div class="text-sm text-gray-700">';
        $html .= 'Sayfa ' . $current . ' / ' . $total;
        $html .= ' (Toplam ' . ($this->pagination['total_items'] ?? 0) . ' kayıt)';
        $html .= '</div>';
        
        // Navigation
        $html .= '<div class="flex space-x-1">';
        
        // Previous page
        if ($current > 1) {
            $html .= '<a href="?page=' . $prev . '" class="btn btn-sm btn-secondary">‹ Önceki</a>';
        }
        
        // Page numbers
        $start = max(1, $current - 2);
        $end = min($total, $current + 2);
        
        for ($i = $start; $i <= $end; $i++) {
            $class = $i === $current ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-secondary';
            $html .= '<a href="?page=' . $i . '" class="' . $class . '">' . $i . '</a>';
        }
        
        // Next page
        if ($current < $total) {
            $html .= '<a href="?page=' . $next . '" class="btn btn-sm btn-secondary">Sonraki ›</a>';
        }
        
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render JavaScript for interactive features
     */
    protected function renderJavaScript(): string
    {
        return '<script>
        // Table selection
        document.addEventListener("DOMContentLoaded", function() {
            const selectAll = document.getElementById("selectAll");
            const checkboxes = document.querySelectorAll("input[name=\"selected[]\"]");
            
            if (selectAll) {
                selectAll.addEventListener("change", function() {
                    checkboxes.forEach(cb => cb.checked = this.checked);
                });
            }
            
            // Sorting
            document.querySelectorAll("[data-sortable]").forEach(header => {
                header.addEventListener("click", function() {
                    const field = this.dataset.sortable;
                    const url = new URL(window.location);
                    
                    // Toggle direction
                    let direction = "ASC";
                    if (url.searchParams.get("sort") === field && url.searchParams.get("direction") === "ASC") {
                        direction = "DESC";
                    }
                    
                    url.searchParams.set("sort", field);
                    url.searchParams.set("direction", direction);
                    window.location.href = url.toString();
                });
            });
        });
        
        // Row actions
        function viewRecord(id) {
            window.location.href = `view/${id}`;
        }
        
        function editRecord(id) {
            window.location.href = `edit/${id}`;
        }
        
        function deleteRecord(id) {
            if (confirm("Bu kaydı silmek istediğinizden emin misiniz?")) {
                // Handle delete
                fetch(`delete/${id}`, {method: "DELETE"})
                    .then(() => window.location.reload());
            }
        }
        </script>';
    }

    /**
     * Create from entity configuration
     */
    public static function fromEntityConfig(array $entityConfig): self
    {
        $builder = new self();

        foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
            if ($fieldConfig['list_hidden'] ?? false) {
                continue;
            }

            $fieldType = $fieldConfig['type'] ?? 'text';
            $options = $fieldConfig;
            unset($options['type']);

            $builder->addColumn($fieldName, $fieldType, $options);
        }

        return $builder;
    }

    /**
     * Register default field types
     */
    protected function registerDefaultFieldTypes(): void
    {
        $this->registerFieldType('text', new \Turkpin\AdminKit\Fields\TextField());
        $this->registerFieldType('email', new \Turkpin\AdminKit\Fields\EmailField());
        $this->registerFieldType('boolean', new \Turkpin\AdminKit\Fields\BooleanField());
        $this->registerFieldType('number', new \Turkpin\AdminKit\Fields\NumberField());
        $this->registerFieldType('textarea', new \Turkpin\AdminKit\Fields\TextareaField());
        $this->registerFieldType('date', new \Turkpin\AdminKit\Fields\DateField());
        $this->registerFieldType('choice', new \Turkpin\AdminKit\Fields\ChoiceField());
        $this->registerFieldType('image', new \Turkpin\AdminKit\Fields\ImageField());
    }

    /**
     * Add custom action
     */
    public function addAction(string $name, string $label, string $url, array $options = []): self
    {
        // Custom action implementation
        return $this;
    }

    /**
     * Set row click action
     */
    public function setRowClickAction(string $action): self
    {
        $this->config['row_click_action'] = $action;
        return $this;
    }

    /**
     * Enable/disable features
     */
    public function setSortable(bool $sortable): self
    {
        $this->config['sortable'] = $sortable;
        return $this;
    }

    public function setSearchable(bool $searchable): self
    {
        $this->config['searchable'] = $searchable;
        return $this;
    }

    public function setSelectable(bool $selectable): self
    {
        $this->config['show_selection'] = $selectable;
        return $this;
    }
}
