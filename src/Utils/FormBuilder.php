<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Utils;

use Turkpin\AdminKit\Fields\FieldTypeInterface;

class FormBuilder
{
    private array $fields = [];
    private array $fieldTypes = [];
    private array $errors = [];
    private array $data = [];
    private array $config = [];
    private ?object $entity = null;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'method' => 'POST',
            'action' => '',
            'enctype' => 'multipart/form-data',
            'css_class' => 'space-y-6',
            'csrf_protection' => true,
            'validate_on_submit' => true
        ], $config);

        $this->registerDefaultFieldTypes();
    }

    /**
     * Register field type
     */
    public function registerFieldType(string $name, FieldTypeInterface $fieldType): self
    {
        $this->fieldTypes[$name] = $fieldType;
        return $this;
    }

    /**
     * Add field to form
     */
    public function add(string $name, string $type, array $options = []): self
    {
        if (!isset($this->fieldTypes[$type])) {
            throw new \InvalidArgumentException("Field type '{$type}' is not registered.");
        }

        $this->fields[$name] = [
            'type' => $type,
            'options' => array_merge($this->fieldTypes[$type]->getDefaultOptions(), $options)
        ];

        return $this;
    }

    /**
     * Remove field from form
     */
    public function remove(string $name): self
    {
        unset($this->fields[$name]);
        return $this;
    }

    /**
     * Set form data (for edit forms)
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Set entity (for edit forms)
     */
    public function setEntity(object $entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * Set validation errors
     */
    public function setErrors(array $errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Get field value
     */
    protected function getFieldValue(string $fieldName): mixed
    {
        // First check form data (from previous submission)
        if (isset($this->data[$fieldName])) {
            return $this->data[$fieldName];
        }

        // Then check entity
        if ($this->entity) {
            $getter = 'get' . ucfirst($fieldName);
            if (method_exists($this->entity, $getter)) {
                return $this->entity->$getter();
            }
        }

        // Return default value from field options
        return $this->fields[$fieldName]['options']['default_value'] ?? null;
    }

    /**
     * Render complete form
     */
    public function render(): string
    {
        $html = '<form method="' . $this->config['method'] . '" ';
        $html .= 'action="' . htmlspecialchars($this->config['action']) . '" ';
        $html .= 'enctype="' . $this->config['enctype'] . '" ';
        $html .= 'class="' . htmlspecialchars($this->config['css_class']) . '">';

        // CSRF token
        if ($this->config['csrf_protection']) {
            $html .= '<input type="hidden" name="_token" value="' . $this->generateCSRFToken() . '">';
        }

        // Render global errors
        if (!empty($this->errors) && isset($this->errors['_form'])) {
            $html .= '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">';
            $html .= '<div class="font-medium">Form Hataları:</div>';
            $html .= '<ul class="mt-2 list-disc list-inside text-sm">';
            foreach ((array)$this->errors['_form'] as $error) {
                $html .= '<li>' . htmlspecialchars($error) . '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        // Render fields
        foreach ($this->fields as $fieldName => $fieldConfig) {
            $html .= $this->renderField($fieldName, $fieldConfig);
        }

        $html .= '</form>';

        return $html;
    }

    /**
     * Render single field
     */
    public function renderField(string $fieldName, array $fieldConfig): string
    {
        $fieldType = $this->fieldTypes[$fieldConfig['type']];
        $value = $this->getFieldValue($fieldName);
        $options = $fieldConfig['options'];

        // Add field-specific errors
        if (isset($this->errors[$fieldName])) {
            $options['errors'] = (array)$this->errors[$fieldName];
        }

        $html = '<div class="form-field" data-field="' . htmlspecialchars($fieldName) . '">';
        
        // Render field with error handling
        try {
            $html .= $fieldType->renderFormInput($fieldName, $value, $options);
        } catch (\Exception $e) {
            $html .= '<div class="text-red-600">Field render error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        // Show field errors
        if (isset($this->errors[$fieldName])) {
            $html .= '<div class="field-errors mt-1">';
            foreach ((array)$this->errors[$fieldName] as $error) {
                $html .= '<p class="text-red-600 text-sm">' . htmlspecialchars($error) . '</p>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Validate form data
     */
    public function validate(array $submittedData): array
    {
        $errors = [];

        foreach ($this->fields as $fieldName => $fieldConfig) {
            $fieldType = $this->fieldTypes[$fieldConfig['type']];
            $value = $submittedData[$fieldName] ?? null;
            
            $fieldErrors = $fieldType->validate($value, $fieldConfig['options']);
            if (!empty($fieldErrors)) {
                $errors[$fieldName] = $fieldErrors;
            }
        }

        return $errors;
    }

    /**
     * Process form data (convert to proper types)
     */
    public function processData(array $submittedData): array
    {
        $processedData = [];

        foreach ($this->fields as $fieldName => $fieldConfig) {
            $fieldType = $this->fieldTypes[$fieldConfig['type']];
            $value = $submittedData[$fieldName] ?? null;
            
            $processedData[$fieldName] = $fieldType->processFormValue($value, $fieldConfig['options']);
        }

        return $processedData;
    }

    /**
     * Handle form submission
     */
    public function handleRequest(array $requestData): array
    {
        $result = [
            'valid' => false,
            'data' => [],
            'errors' => []
        ];

        // Validate CSRF token
        if ($this->config['csrf_protection']) {
            if (!$this->validateCSRFToken($requestData['_token'] ?? '')) {
                $result['errors']['_form'] = ['Invalid CSRF token'];
                return $result;
            }
        }

        // Validate form data
        if ($this->config['validate_on_submit']) {
            $errors = $this->validate($requestData);
            if (!empty($errors)) {
                $result['errors'] = $errors;
                $this->setErrors($errors);
                return $result;
            }
        }

        // Process data
        $result['data'] = $this->processData($requestData);
        $result['valid'] = true;

        return $result;
    }

    /**
     * Create form builder from entity configuration
     */
    public static function fromEntityConfig(array $entityConfig, array $formConfig = []): self
    {
        $builder = new self($formConfig);

        foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
            if ($fieldConfig['form_hidden'] ?? false) {
                continue;
            }

            $fieldType = $fieldConfig['type'] ?? 'text';
            unset($fieldConfig['type']);

            $builder->add($fieldName, $fieldType, $fieldConfig);
        }

        return $builder;
    }

    /**
     * Add form sections (for complex forms)
     */
    public function addSection(string $title, array $fields, array $options = []): self
    {
        $sectionHtml = '<div class="form-section ' . ($options['css_class'] ?? '') . '">';
        $sectionHtml .= '<h3 class="text-lg font-medium text-gray-900 mb-4">' . htmlspecialchars($title) . '</h3>';
        
        if (isset($options['description'])) {
            $sectionHtml .= '<p class="text-sm text-gray-600 mb-6">' . htmlspecialchars($options['description']) . '</p>';
        }

        // Add fields to this section
        foreach ($fields as $fieldName => $fieldConfig) {
            if (is_string($fieldConfig)) {
                // Simple field name only
                $this->add($fieldName, $fieldConfig);
            } else {
                // Full field configuration
                $this->add($fieldName, $fieldConfig['type'], $fieldConfig['options'] ?? []);
            }
        }

        return $this;
    }

    /**
     * Add form tabs
     */
    public function addTab(string $tabId, string $title, array $fields, array $options = []): self
    {
        // Implementation for tabbed forms
        // This would require additional JavaScript
        return $this;
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
     * Generate CSRF token
     */
    protected function generateCSRFToken(): string
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token'] = $token;
        
        return $token;
    }

    /**
     * Validate CSRF token
     */
    protected function validateCSRFToken(string $token): bool
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        $sessionToken = $_SESSION['_csrf_token'] ?? '';
        return hash_equals($sessionToken, $token);
    }

    /**
     * Get form configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get all fields
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get specific field
     */
    public function getField(string $name): ?array
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * Check if field exists
     */
    public function hasField(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * Modify field options
     */
    public function modifyField(string $name, array $options): self
    {
        if (isset($this->fields[$name])) {
            $this->fields[$name]['options'] = array_merge(
                $this->fields[$name]['options'], 
                $options
            );
        }
        
        return $this;
    }

    /**
     * Set field as required
     */
    public function setRequired(string $name, bool $required = true): self
    {
        return $this->modifyField($name, ['required' => $required]);
    }

    /**
     * Set field as readonly
     */
    public function setReadonly(string $name, bool $readonly = true): self
    {
        return $this->modifyField($name, ['readonly' => $readonly]);
    }

    /**
     * Add CSS class to field
     */
    public function addCssClass(string $name, string $cssClass): self
    {
        $currentClass = $this->fields[$name]['options']['css_class'] ?? '';
        $newClass = trim($currentClass . ' ' . $cssClass);
        
        return $this->modifyField($name, ['css_class' => $newClass]);
    }

    /**
     * Generate form HTML with wrapper
     */
    public function renderWithWrapper(array $wrapperConfig = []): string
    {
        $config = array_merge([
            'title' => '',
            'description' => '',
            'submit_text' => 'Kaydet',
            'cancel_url' => '',
            'show_cancel' => true,
            'form_class' => 'bg-white rounded-lg shadow p-6'
        ], $wrapperConfig);

        $html = '<div class="' . htmlspecialchars($config['form_class']) . '">';
        
        // Form header
        if ($config['title']) {
            $html .= '<div class="mb-6">';
            $html .= '<h2 class="text-xl font-semibold text-gray-900">' . htmlspecialchars($config['title']) . '</h2>';
            if ($config['description']) {
                $html .= '<p class="text-gray-600 mt-1">' . htmlspecialchars($config['description']) . '</p>';
            }
            $html .= '</div>';
        }

        // Form content
        $html .= $this->render();

        // Form actions
        $html .= '<div class="flex items-center justify-between pt-6 border-t border-gray-200 mt-6">';
        
        if ($config['show_cancel'] && $config['cancel_url']) {
            $html .= '<a href="' . htmlspecialchars($config['cancel_url']) . '" ';
            $html .= 'class="btn btn-secondary">İptal</a>';
        } else {
            $html .= '<div></div>';
        }
        
        $html .= '<button type="submit" class="btn btn-primary">';
        $html .= htmlspecialchars($config['submit_text']);
        $html .= '</button>';
        
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
