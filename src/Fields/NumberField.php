<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

class NumberField extends AbstractFieldType
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
        'attr',
        'min',
        'max',
        'step',
        'format',
        'suffix',
        'prefix',
        'thousands_separator',
        'decimal_separator',
        'decimal_places'
    ];

    public function getTypeName(): string
    {
        return 'number';
    }

    public function getDefaultOptions(): array
    {
        return array_merge(parent::getDefaultOptions(), [
            'min' => null,
            'max' => null,
            'step' => 1,
            'format' => 'integer', // integer, decimal, float
            'suffix' => null,
            'prefix' => null,
            'thousands_separator' => '.',
            'decimal_separator' => ',',
            'decimal_places' => 2
        ]);
    }

    public function renderDisplay($value, array $options = []): string
    {
        if ($this->isEmpty($value)) {
            return '<span class="text-gray-400">-</span>';
        }

        $options = array_merge($this->getDefaultOptions(), $options);
        $formattedValue = $this->formatNumber($value, $options);

        return '<span class="font-mono text-right">' . $this->escapeValue($formattedValue) . '</span>';
    }

    public function renderFormInput(string $name, $value, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        $attributes = $this->renderAttributes($options);

        // Add number-specific attributes
        if ($options['min'] !== null) {
            $attributes .= ' min="' . $options['min'] . '"';
        }

        if ($options['max'] !== null) {
            $attributes .= ' max="' . $options['max'] . '"';
        }

        if ($options['step'] !== null) {
            $attributes .= ' step="' . $options['step'] . '"';
        }

        // Determine input type based on format
        $inputType = $options['format'] === 'integer' ? 'number' : 'number';
        
        $html = $this->renderLabel($name, $options);
        $html .= $this->renderHelpText($options);

        // Render with prefix/suffix if specified
        if (!empty($options['prefix']) || !empty($options['suffix'])) {
            $html .= '<div class="relative">';
            
            if (!empty($options['prefix'])) {
                $html .= '<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">';
                $html .= '<span class="text-gray-500 sm:text-sm">' . $this->escapeValue($options['prefix']) . '</span>';
                $html .= '</div>';
                $attributes .= ' class="' . $options['css_class'] . ' pl-8"';
            }
            
            $html .= '<input type="' . $inputType . '" name="' . $this->escapeValue($name) . '" ';
            $html .= 'id="' . $this->escapeValue($name) . '" ';
            $html .= 'value="' . $this->escapeValue($value ?? $options['default_value'] ?? '') . '" ';
            $html .= $attributes . '>';
            
            if (!empty($options['suffix'])) {
                $html .= '<div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">';
                $html .= '<span class="text-gray-500 sm:text-sm">' . $this->escapeValue($options['suffix']) . '</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        } else {
            $html .= '<input type="' . $inputType . '" name="' . $this->escapeValue($name) . '" ';
            $html .= 'id="' . $this->escapeValue($name) . '" ';
            $html .= 'value="' . $this->escapeValue($value ?? $options['default_value'] ?? '') . '" ';
            $html .= $attributes . '>';
        }

        // Add range display if min/max are set
        if ($options['min'] !== null || $options['max'] !== null) {
            $html .= '<p class="text-xs text-gray-500 mt-1">';
            if ($options['min'] !== null && $options['max'] !== null) {
                $html .= 'Aralık: ' . $options['min'] . ' - ' . $options['max'];
            } elseif ($options['min'] !== null) {
                $html .= 'Minimum: ' . $options['min'];
            } elseif ($options['max'] !== null) {
                $html .= 'Maksimum: ' . $options['max'];
            }
            $html .= '</p>';
        }

        return $html;
    }

    public function validate($value, array $options = []): array
    {
        $errors = parent::validate($value, $options);
        $options = array_merge($this->getDefaultOptions(), $options);

        if (!$this->isEmpty($value)) {
            // Check if it's a valid number
            if (!is_numeric($value)) {
                $errors[] = ($options['label'] ?? 'Bu alan') . ' geçerli bir sayı olmalıdır.';
                return $errors; // Don't continue with other validations
            }

            $numValue = (float)$value;

            // Min validation
            if ($options['min'] !== null && $numValue < $options['min']) {
                $errors[] = ($options['label'] ?? 'Bu alan') . ' en az ' . $options['min'] . ' olmalıdır.';
            }

            // Max validation
            if ($options['max'] !== null && $numValue > $options['max']) {
                $errors[] = ($options['label'] ?? 'Bu alan') . ' en fazla ' . $options['max'] . ' olabilir.';
            }

            // Step validation
            if ($options['step'] !== null && $options['step'] > 0) {
                $remainder = fmod($numValue - ($options['min'] ?? 0), $options['step']);
                if (abs($remainder) > 0.0001) { // Allow for floating point precision issues
                    $errors[] = ($options['label'] ?? 'Bu alan') . ' ' . $options['step'] . ' adımlarında olmalıdır.';
                }
            }

            // Integer validation
            if ($options['format'] === 'integer' && !is_int($numValue) && $numValue != (int)$numValue) {
                $errors[] = ($options['label'] ?? 'Bu alan') . ' tam sayı olmalıdır.';
            }
        }

        return $errors;
    }

    public function processFormValue($value, array $options = [])
    {
        if ($this->isEmpty($value)) {
            return null;
        }

        $options = array_merge($this->getDefaultOptions(), $options);

        // Convert to appropriate type
        if ($options['format'] === 'integer') {
            return (int)$value;
        } else {
            return (float)$value;
        }
    }

    protected function formatNumber($value, array $options): string
    {
        if (!is_numeric($value)) {
            return (string)$value;
        }

        $formatted = '';

        // Add prefix
        if (!empty($options['prefix'])) {
            $formatted .= $options['prefix'] . ' ';
        }

        // Format the number
        if ($options['format'] === 'integer') {
            $formatted .= number_format((int)$value, 0, $options['decimal_separator'], $options['thousands_separator']);
        } else {
            $decimalPlaces = $options['decimal_places'] ?? 2;
            $formatted .= number_format((float)$value, $decimalPlaces, $options['decimal_separator'], $options['thousands_separator']);
        }

        // Add suffix
        if (!empty($options['suffix'])) {
            $formatted .= ' ' . $options['suffix'];
        }

        return $formatted;
    }

    /**
     * Generate input with increment/decrement buttons
     */
    public function renderSpinnerInput(string $name, $value, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        $step = $options['step'] ?? 1;
        
        $html = $this->renderLabel($name, $options);
        $html .= $this->renderHelpText($options);
        
        $html .= '<div class="flex items-center border border-gray-300 rounded-md">';
        
        // Decrease button
        $html .= '<button type="button" onclick="changeNumber(\'' . $name . '\', -' . $step . ')" ';
        $html .= 'class="px-3 py-2 text-gray-500 hover:text-gray-700 border-r border-gray-300">';
        $html .= '−</button>';
        
        // Number input
        $html .= '<input type="number" name="' . $this->escapeValue($name) . '" ';
        $html .= 'id="' . $this->escapeValue($name) . '" ';
        $html .= 'value="' . $this->escapeValue($value ?? $options['default_value'] ?? '') . '" ';
        $html .= 'class="flex-1 border-0 focus:ring-0 text-center" ';
        
        if ($options['min'] !== null) {
            $html .= 'min="' . $options['min'] . '" ';
        }
        if ($options['max'] !== null) {
            $html .= 'max="' . $options['max'] . '" ';
        }
        if ($options['step'] !== null) {
            $html .= 'step="' . $options['step'] . '" ';
        }
        
        $html .= '>';
        
        // Increase button
        $html .= '<button type="button" onclick="changeNumber(\'' . $name . '\', ' . $step . ')" ';
        $html .= 'class="px-3 py-2 text-gray-500 hover:text-gray-700 border-l border-gray-300">';
        $html .= '+</button>';
        
        $html .= '</div>';
        
        // Add JavaScript for number change functionality
        $html .= '<script>';
        $html .= 'function changeNumber(fieldName, change) {';
        $html .= '  const field = document.getElementById(fieldName);';
        $html .= '  const currentValue = parseFloat(field.value) || 0;';
        $html .= '  const newValue = currentValue + change;';
        $html .= '  const min = parseFloat(field.getAttribute("min"));';
        $html .= '  const max = parseFloat(field.getAttribute("max"));';
        $html .= '  if ((!min || newValue >= min) && (!max || newValue <= max)) {';
        $html .= '    field.value = newValue;';
        $html .= '    field.dispatchEvent(new Event("change"));';
        $html .= '  }';
        $html .= '}';
        $html .= '</script>';
        
        return $html;
    }
}
