<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

abstract class AbstractFieldType implements FieldTypeInterface
{
    protected array $supportedOptions = [
        'label',
        'help',
        'required',
        'readonly',
        'disabled',
        'placeholder',
        'default_value',
        'css_class',
        'attr'
    ];

    public function getDefaultOptions(): array
    {
        return [
            'label' => null,
            'help' => null,
            'required' => false,
            'readonly' => false,
            'disabled' => false,
            'placeholder' => null,
            'default_value' => null,
            'css_class' => 'form-input',
            'attr' => []
        ];
    }

    public function supportsOption(string $option): bool
    {
        return in_array($option, $this->supportedOptions);
    }

    public function validate($value, array $options = []): array
    {
        $errors = [];
        $options = array_merge($this->getDefaultOptions(), $options);

        // Required validation
        if ($options['required'] && $this->isEmpty($value)) {
            $errors[] = ($options['label'] ?? 'Bu alan') . ' gereklidir.';
        }

        return $errors;
    }

    public function processFormValue($value, array $options = [])
    {
        // Default: return value as-is
        return $value;
    }

    protected function isEmpty($value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    protected function renderAttributes(array $options): string
    {
        $attributes = [];
        
        if ($options['required'] ?? false) {
            $attributes[] = 'required';
        }
        
        if ($options['readonly'] ?? false) {
            $attributes[] = 'readonly';
        }
        
        if ($options['disabled'] ?? false) {
            $attributes[] = 'disabled';
        }
        
        if (!empty($options['placeholder'])) {
            $attributes[] = 'placeholder="' . htmlspecialchars($options['placeholder']) . '"';
        }
        
        if (!empty($options['css_class'])) {
            $attributes[] = 'class="' . htmlspecialchars($options['css_class']) . '"';
        }
        
        // Custom attributes
        if (!empty($options['attr']) && is_array($options['attr'])) {
            foreach ($options['attr'] as $key => $value) {
                $attributes[] = htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
            }
        }
        
        return implode(' ', $attributes);
    }

    protected function formatDisplayValue($value): string
    {
        if ($value === null || $value === '') {
            return '<span class="text-gray-400">-</span>';
        }
        
        return htmlspecialchars((string)$value);
    }

    protected function renderHelpText(array $options): string
    {
        if (empty($options['help'])) {
            return '';
        }
        
        return '<p class="text-sm text-gray-500 mt-1">' . htmlspecialchars($options['help']) . '</p>';
    }

    protected function renderLabel(string $name, array $options): string
    {
        $label = $options['label'] ?? ucfirst($name);
        $required = $options['required'] ?? false;
        
        $html = '<label for="' . htmlspecialchars($name) . '" class="form-label">';
        $html .= htmlspecialchars($label);
        
        if ($required) {
            $html .= ' <span class="text-red-500">*</span>';
        }
        
        $html .= '</label>';
        
        return $html;
    }

    protected function escapeValue($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    protected function generateId(string $name): string
    {
        return 'field_' . str_replace(['[', ']'], ['_', ''], $name) . '_' . uniqid();
    }
}
