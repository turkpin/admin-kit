<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

class BooleanField extends AbstractFieldType
{
    protected array $supportedOptions = [
        'label',
        'help',
        'required',
        'readonly',
        'disabled',
        'default_value',
        'css_class',
        'attr',
        'true_label',
        'false_label',
        'render_as_switch'
    ];

    public function getTypeName(): string
    {
        return 'boolean';
    }

    public function getDefaultOptions(): array
    {
        return array_merge(parent::getDefaultOptions(), [
            'true_label' => 'Evet',
            'false_label' => 'Hayır',
            'render_as_switch' => true,
            'css_class' => 'form-checkbox'
        ]);
    }

    public function renderDisplay($value, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        $isTrue = (bool)$value;
        
        $label = $isTrue ? $options['true_label'] : $options['false_label'];
        $colorClass = $isTrue ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        
        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $colorClass . '">'
               . htmlspecialchars($label) . '</span>';
    }

    public function renderFormInput(string $name, $value, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        $isChecked = (bool)($value ?? $options['default_value'] ?? false);
        
        if ($options['render_as_switch']) {
            return $this->renderAsSwitch($name, $isChecked, $options);
        } else {
            return $this->renderAsCheckbox($name, $isChecked, $options);
        }
    }

    protected function renderAsSwitch(string $name, bool $isChecked, array $options): string
    {
        $checkedClass = $isChecked ? 'checked' : '';
        $disabled = $options['disabled'] ? 'disabled' : '';
        $readonly = $options['readonly'] ? 'readonly' : '';
        
        $html = '<div class="flex items-center">';
        $html .= '<label class="toggle-switch ' . $checkedClass . ' ' . $disabled . '" for="' . $this->escapeValue($name) . '">';
        $html .= '<input type="checkbox" ';
        $html .= 'name="' . $this->escapeValue($name) . '" ';
        $html .= 'id="' . $this->escapeValue($name) . '" ';
        $html .= 'value="1" ';
        
        if ($isChecked) {
            $html .= 'checked ';
        }
        
        if ($options['disabled']) {
            $html .= 'disabled ';
        }
        
        if ($options['readonly']) {
            $html .= 'readonly ';
        }
        
        $html .= 'class="sr-only">';
        $html .= '<span class="toggle-switch-thumb"></span>';
        $html .= '</label>';
        
        if (!empty($options['label'])) {
            $html .= '<span class="ml-3 text-sm text-gray-700">';
            $html .= htmlspecialchars($options['label']);
            if ($options['required'] ?? false) {
                $html .= ' <span class="text-red-500">*</span>';
            }
            $html .= '</span>';
        }
        
        $html .= '</div>';
        $html .= $this->renderHelpText($options);
        
        return $html;
    }

    protected function renderAsCheckbox(string $name, bool $isChecked, array $options): string
    {
        $attributes = $this->renderAttributes($options);
        
        $html = '<div class="flex items-center">';
        $html .= '<input type="checkbox" ';
        $html .= 'name="' . $this->escapeValue($name) . '" ';
        $html .= 'id="' . $this->escapeValue($name) . '" ';
        $html .= 'value="1" ';
        
        if ($isChecked) {
            $html .= 'checked ';
        }
        
        $html .= $attributes . '>';
        
        if (!empty($options['label'])) {
            $html .= '<label for="' . $this->escapeValue($name) . '" class="ml-2 text-sm text-gray-700">';
            $html .= htmlspecialchars($options['label']);
            if ($options['required'] ?? false) {
                $html .= ' <span class="text-red-500">*</span>';
            }
            $html .= '</label>';
        }
        
        $html .= '</div>';
        $html .= $this->renderHelpText($options);
        
        return $html;
    }

    public function validate($value, array $options = []): array
    {
        $errors = [];
        
        // For boolean fields, "required" means it must be true
        if ($options['required'] ?? false) {
            if (!$value) {
                $errors[] = ($options['label'] ?? 'Bu alan') . ' işaretlenmelidir.';
            }
        }
        
        return $errors;
    }

    public function processFormValue($value, array $options = [])
    {
        // Checkbox values come as "1" when checked, null when unchecked
        return !empty($value);
    }
}
