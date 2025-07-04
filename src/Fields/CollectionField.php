<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

use Turkpin\AdminKit\Fields\AbstractFieldType;
use Turkpin\AdminKit\Services\LocalizationService;

class CollectionField extends AbstractFieldType
{
    private array $entryFields;
    private string $entryType;
    private bool $allowAdd;
    private bool $allowDelete;
    private int $minEntries;
    private int $maxEntries;
    private string $prototype;
    private array $entryOptions;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        
        $this->entryFields = $config['entry_fields'] ?? [];
        $this->entryType = $config['entry_type'] ?? 'form';
        $this->allowAdd = $config['allow_add'] ?? true;
        $this->allowDelete = $config['allow_delete'] ?? true;
        $this->minEntries = $config['min_entries'] ?? 0;
        $this->maxEntries = $config['max_entries'] ?? 10;
        $this->entryOptions = $config['entry_options'] ?? [];
        $this->prototype = $this->generatePrototype();
    }

    public function render($value = null, array $options = []): string
    {
        $fieldId = $this->getFieldId();
        $entries = $this->normalizeValue($value);
        
        $html = '<div class="collection-field" data-field-name="' . $this->name . '" data-prototype="' . htmlspecialchars($this->prototype) . '">';
        
        // Collection header
        $html .= '<div class="collection-header flex items-center justify-between mb-4">';
        $html .= '<label class="block text-sm font-medium text-gray-700">' . $this->label . '</label>';
        
        if ($this->allowAdd) {
            $html .= '<button type="button" onclick="addCollectionEntry(\'' . $fieldId . '\')" 
                             class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">';
            $html .= '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>';
            $html .= '</svg>';
            $html .= 'Add Item';
            $html .= '</button>';
        }
        
        $html .= '</div>';
        
        // Help text
        if ($this->helpText) {
            $html .= '<p class="mt-1 text-sm text-gray-500">' . $this->helpText . '</p>';
        }
        
        // Collection entries container
        $html .= '<div id="' . $fieldId . '-container" class="collection-entries space-y-4">';
        
        // Render existing entries
        foreach ($entries as $index => $entryData) {
            $html .= $this->renderEntry($index, $entryData);
        }
        
        $html .= '</div>';
        
        // Min/max validation info
        $html .= '<div class="collection-info mt-2 text-xs text-gray-500">';
        if ($this->minEntries > 0) {
            $html .= 'Minimum: ' . $this->minEntries . ' items. ';
        }
        if ($this->maxEntries > 0) {
            $html .= 'Maximum: ' . $this->maxEntries . ' items.';
        }
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Add JavaScript
        $html .= $this->renderJavaScript();
        
        return $html;
    }

    private function renderEntry(int $index, array $entryData = []): string
    {
        $entryId = $this->getFieldId() . '_' . $index;
        
        $html = '<div class="collection-entry border border-gray-200 rounded-lg p-4 bg-gray-50" data-index="' . $index . '">';
        
        // Entry header
        $html .= '<div class="entry-header flex items-center justify-between mb-3">';
        $html .= '<h4 class="text-sm font-medium text-gray-800">Item #' . ($index + 1) . '</h4>';
        
        $html .= '<div class="entry-actions space-x-2">';
        
        // Move up/down buttons
        $html .= '<button type="button" onclick="moveCollectionEntry(\'' . $entryId . '\', \'up\')" 
                         class="text-gray-400 hover:text-gray-600" title="Move Up">';
        $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>';
        $html .= '</svg>';
        $html .= '</button>';
        
        $html .= '<button type="button" onclick="moveCollectionEntry(\'' . $entryId . '\', \'down\')" 
                         class="text-gray-400 hover:text-gray-600" title="Move Down">';
        $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>';
        $html .= '</svg>';
        $html .= '</button>';
        
        // Delete button
        if ($this->allowDelete) {
            $html .= '<button type="button" onclick="removeCollectionEntry(\'' . $entryId . '\')" 
                             class="text-red-400 hover:text-red-600" title="Remove">';
            $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>';
            $html .= '</svg>';
            $html .= '</button>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        // Entry fields
        $html .= '<div class="entry-fields">';
        
        switch ($this->entryType) {
            case 'entity':
                $html .= $this->renderEntityEntry($index, $entryData);
                break;
            case 'form':
            default:
                $html .= $this->renderFormEntry($index, $entryData);
                break;
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    private function renderFormEntry(int $index, array $entryData = []): string
    {
        $html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        
        foreach ($this->entryFields as $fieldName => $fieldConfig) {
            $fieldValue = $entryData[$fieldName] ?? null;
            $inputName = $this->name . '[' . $index . '][' . $fieldName . ']';
            $inputId = $this->getFieldId() . '_' . $index . '_' . $fieldName;
            
            $html .= '<div class="form-group">';
            $html .= '<label for="' . $inputId . '" class="block text-sm font-medium text-gray-700">';
            $html .= $fieldConfig['label'] ?? ucfirst($fieldName);
            $html .= '</label>';
            
            $fieldType = $fieldConfig['type'] ?? 'text';
            
            switch ($fieldType) {
                case 'textarea':
                    $html .= '<textarea id="' . $inputId . '" name="' . $inputName . '" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                       rows="3">' . htmlspecialchars($fieldValue ?? '') . '</textarea>';
                    break;
                    
                case 'select':
                    $html .= '<select id="' . $inputId . '" name="' . $inputName . '" 
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">';
                    foreach ($fieldConfig['options'] ?? [] as $optionValue => $optionLabel) {
                        $selected = $fieldValue == $optionValue ? 'selected' : '';
                        $html .= '<option value="' . htmlspecialchars($optionValue) . '" ' . $selected . '>' . htmlspecialchars($optionLabel) . '</option>';
                    }
                    $html .= '</select>';
                    break;
                    
                case 'checkbox':
                    $checked = $fieldValue ? 'checked' : '';
                    $html .= '<div class="mt-1">';
                    $html .= '<input type="checkbox" id="' . $inputId . '" name="' . $inputName . '" value="1" ' . $checked . ' 
                                     class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">';
                    $html .= '<label for="' . $inputId . '" class="ml-2 text-sm text-gray-700">' . ($fieldConfig['label'] ?? 'Enable') . '</label>';
                    $html .= '</div>';
                    break;
                    
                default:
                    $html .= '<input type="' . $fieldType . '" id="' . $inputId . '" name="' . $inputName . '" 
                                     value="' . htmlspecialchars($fieldValue ?? '') . '"
                                     class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">';
                    break;
            }
            
            if (isset($fieldConfig['help'])) {
                $html .= '<p class="mt-1 text-xs text-gray-500">' . $fieldConfig['help'] . '</p>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    private function renderEntityEntry(int $index, array $entryData = []): string
    {
        // For entity collections, render a simplified view
        $entityId = $entryData['id'] ?? null;
        $entityTitle = $entryData['title'] ?? $entryData['name'] ?? 'Item #' . ($index + 1);
        
        $html = '<div class="entity-entry p-3 bg-white border border-gray-200 rounded">';
        $html .= '<input type="hidden" name="' . $this->name . '[' . $index . '][id]" value="' . ($entityId ?? '') . '">';
        $html .= '<div class="flex items-center justify-between">';
        $html .= '<div class="flex items-center">';
        $html .= '<div class="text-sm font-medium text-gray-900">' . htmlspecialchars($entityTitle) . '</div>';
        if ($entityId) {
            $html .= '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">ID: ' . $entityId . '</span>';
        }
        $html .= '</div>';
        $html .= '<button type="button" onclick="editCollectionEntity(\'' . $index . '\')" 
                         class="text-indigo-600 hover:text-indigo-900 text-sm">Edit</button>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    private function generatePrototype(): string
    {
        return $this->renderEntry('__INDEX__', []);
    }

    private function normalizeValue($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return [];
    }

    private function renderJavaScript(): string
    {
        $fieldId = $this->getFieldId();
        
        return "
        <script>
        function addCollectionEntry(fieldId) {
            const container = document.getElementById(fieldId + '-container');
            const collectionField = container.closest('.collection-field');
            const prototype = collectionField.getAttribute('data-prototype');
            const currentEntries = container.children.length;
            
            // Check max entries
            const maxEntries = " . $this->maxEntries . ";
            if (maxEntries > 0 && currentEntries >= maxEntries) {
                alert('Maximum number of entries reached (' + maxEntries + ')');
                return;
            }
            
            // Replace __INDEX__ with actual index
            const newEntry = prototype.replace(/__INDEX__/g, currentEntries);
            
            // Create new element
            const wrapper = document.createElement('div');
            wrapper.innerHTML = newEntry;
            const entryElement = wrapper.firstElementChild;
            
            // Update data-index
            entryElement.setAttribute('data-index', currentEntries);
            
            // Append to container
            container.appendChild(entryElement);
            
            // Update entry numbers
            updateEntryNumbers(fieldId);
            
            // Focus first input in new entry
            const firstInput = entryElement.querySelector('input, textarea, select');
            if (firstInput) {
                firstInput.focus();
            }
        }
        
        function removeCollectionEntry(entryId) {
            const entry = document.querySelector('[data-index]').closest('.collection-entry');
            const container = entry.parentElement;
            const fieldId = container.id.replace('-container', '');
            
            // Check min entries
            const minEntries = " . $this->minEntries . ";
            if (minEntries > 0 && container.children.length <= minEntries) {
                alert('Minimum number of entries required (' + minEntries + ')');
                return;
            }
            
            if (confirm('Are you sure you want to remove this item?')) {
                entry.remove();
                updateEntryNumbers(fieldId);
                reindexEntries(fieldId);
            }
        }
        
        function moveCollectionEntry(entryId, direction) {
            const entry = document.querySelector('.collection-entry[data-index]');
            const container = entry.parentElement;
            const fieldId = container.id.replace('-container', '');
            
            if (direction === 'up' && entry.previousElementSibling) {
                container.insertBefore(entry, entry.previousElementSibling);
            } else if (direction === 'down' && entry.nextElementSibling) {
                container.insertBefore(entry.nextElementSibling, entry);
            }
            
            updateEntryNumbers(fieldId);
            reindexEntries(fieldId);
        }
        
        function updateEntryNumbers(fieldId) {
            const container = document.getElementById(fieldId + '-container');
            const entries = container.querySelectorAll('.collection-entry');
            
            entries.forEach((entry, index) => {
                const header = entry.querySelector('.entry-header h4');
                if (header) {
                    header.textContent = 'Item #' + (index + 1);
                }
                entry.setAttribute('data-index', index);
            });
        }
        
        function reindexEntries(fieldId) {
            const container = document.getElementById(fieldId + '-container');
            const entries = container.querySelectorAll('.collection-entry');
            
            entries.forEach((entry, index) => {
                // Update all input names and ids
                const inputs = entry.querySelectorAll('input, textarea, select');
                inputs.forEach(input => {
                    if (input.name) {
                        input.name = input.name.replace(/\[\d+\]/, '[' + index + ']');
                    }
                    if (input.id) {
                        input.id = input.id.replace(/_\d+_/, '_' + index + '_');
                    }
                });
                
                // Update labels
                const labels = entry.querySelectorAll('label');
                labels.forEach(label => {
                    if (label.getAttribute('for')) {
                        label.setAttribute('for', label.getAttribute('for').replace(/_\d+_/, '_' + index + '_'));
                    }
                });
            });
        }
        
        function editCollectionEntity(index) {
            // This would open a modal or redirect to edit the entity
            alert('Edit entity functionality would be implemented here');
        }
        </script>";
    }

    public function validate($value): array
    {
        $errors = parent::validate($value);
        $entries = $this->normalizeValue($value);
        
        // Check minimum entries
        if (count($entries) < $this->minEntries) {
            $errors[] = "At least {$this->minEntries} entries are required";
        }
        
        // Check maximum entries
        if ($this->maxEntries > 0 && count($entries) > $this->maxEntries) {
            $errors[] = "Maximum {$this->maxEntries} entries allowed";
        }
        
        // Validate each entry
        foreach ($entries as $index => $entryData) {
            foreach ($this->entryFields as $fieldName => $fieldConfig) {
                $fieldValue = $entryData[$fieldName] ?? null;
                
                // Check required fields
                if (($fieldConfig['required'] ?? false) && empty($fieldValue)) {
                    $errors[] = "Entry " . ($index + 1) . ": {$fieldName} is required";
                }
                
                // Additional field validation can be added here
            }
        }
        
        return $errors;
    }

    public function transform($value)
    {
        return $this->normalizeValue($value);
    }

    // Configuration methods
    public function setEntryFields(array $fields): self
    {
        $this->entryFields = $fields;
        return $this;
    }

    public function setEntryType(string $type): self
    {
        $this->entryType = $type;
        return $this;
    }

    public function setAllowAdd(bool $allow): self
    {
        $this->allowAdd = $allow;
        return $this;
    }

    public function setAllowDelete(bool $allow): self
    {
        $this->allowDelete = $allow;
        return $this;
    }

    public function setMinEntries(int $min): self
    {
        $this->minEntries = $min;
        return $this;
    }

    public function setMaxEntries(int $max): self
    {
        $this->maxEntries = $max;
        return $this;
    }
}
