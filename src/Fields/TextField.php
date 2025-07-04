<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

class TextField extends AbstractFieldType
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
        'maxlength',
        'minlength',
        'pattern'
    ];

    public function getTypeName(): string
    {
        return 'text';
    }

    public function getDefaultOptions(): array
    {
        return array_merge(parent::getDefaultOptions(), [
            'maxlength' => null,
            'minlength' => null,
            'pattern' => null
        ]);
    }

    public function renderDisplay($value, array $options = []): string
    {
        if ($this->isEmpty($value)) {
            return '<span class="text-gray-400">-</span>';
        }

        $maxLength = $options['display_max_length'] ?? 50;
        $displayValue = strlen($value) > $maxLength 
            ? substr($value, 0, $maxLength) . '...' 
            : $value;

        return '<span class="break-words" title="' . $this->escapeValue($value) . '">' 
               . $this->escapeValue($displayValue) . '</span>';
    }

    public function renderFormInput(string $name, $value, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        $attributes = $this->renderAttributes($options);

        // Add specific attributes
        if (!empty($options['maxlength'])) {
            $attributes .= ' maxlength="' . (int)$options['maxlength'] . '"';
        }

        if (!empty($options['minlength'])) {
            $attributes .= ' minlength="' . (int)$options['minlength'] . '"';
        }

        if (!empty($options['pattern'])) {
            $attributes .= ' pattern="' . $this->escapeValue($options['pattern']) . '"';
        }

        $html = $this->renderLabel($name, $options);
        $html .= $this->renderHelpText($options);
        $html .= '<input type="text" name="' . $this->escapeValue($name) . '" ';
        $html .= 'id="' . $this->escapeValue($name) . '" ';
        $html .= 'value="' . $this->escapeValue($value ?? $options['default_value'] ?? '') . '" ';
        $html .= $attributes . '>';

        return $html;
    }

    public function validate($value, array $options = []): array
    {
        $errors = parent::validate($value, $options);

        if (!$this->isEmpty($value)) {
            // Min length validation
            if (!empty($options['minlength']) && strlen($value) < $options['minlength']) {
                $errors[] = ($options['label'] ?? 'Bu alan') . ' en az ' . $options['minlength'] . ' karakter olmalıdır.';
            }

            // Max length validation
            if (!empty($options['maxlength']) && strlen($value) > $options['maxlength']) {
                $errors[] = ($options['label'] ?? 'Bu alan') . ' en fazla ' . $options['maxlength'] . ' karakter olabilir.';
            }

            // Pattern validation
            if (!empty($options['pattern']) && !preg_match('/' . $options['pattern'] . '/', $value)) {
                $errors[] = ($options['label'] ?? 'Bu alan') . ' geçerli bir formatta olmalıdır.';
            }
        }

        return $errors;
    }

    public function processFormValue($value, array $options = [])
    {
        if ($this->isEmpty($value)) {
            return null;
        }

        // Trim whitespace
        $value = trim($value);

        // Apply max length if specified
        if (!empty($options['maxlength'])) {
            $value = substr($value, 0, $options['maxlength']);
        }

        return $value;
    }
}
