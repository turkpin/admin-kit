<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

class ChoiceField extends AbstractFieldType
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
        'choices',
        'multiple',
        'expanded',
        'choice_label',
        'choice_value',
        'choice_attr',
        'group_by',
        'empty_data',
        'placeholder_text',
        'allow_custom',
        'searchable'
    ];

    public function getTypeName(): string
    {
        return 'choice';
    }

    public function getDefaultOptions(): array
    {
        return array_merge(parent::getDefaultOptions(), [
            'choices' => [],
            'multiple' => false,
            'expanded' => false, // true = radio/checkbox, false = select
            'choice_label' => null,
            'choice_value' => null,
            'choice_attr' => [],
            'group_by' => null,
            'empty_data' => null,
            'placeholder_text' => 'Seçiniz...',
            'allow_custom' => false,
            'searchable' => false
        ]);
    }

    public function renderDisplay($value, array $options = []): string
    {
        if ($this->isEmpty($value)) {
            return '<span class="text-gray-400">-</span>';
        }

        $options = array_merge($this->getDefaultOptions(), $options);
        $choices = $options['choices'];

        if ($options['multiple']) {
            if (!is_array($value)) {
                $value = [$value];
            }

            $html = '<div class="space-y-1">';
            foreach ($value as $val) {
                $label = $this->getChoiceLabel($val, $choices, $options);
                $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">';
                $html .= $this->escapeValue($label);
                $html .= '</span>';
            }
            $html .= '</div>';

            return $html;
        } else {
            $label = $this->getChoiceLabel($value, $choices, $options);
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">'
                   . $this->escapeValue($label) . '</span>';
        }
    }

    public function renderFormInput(string $name, $value, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);

        if ($options['expanded']) {
            return $options['multiple'] 
                ? $this->renderCheckboxes($name, $value, $options)
                : $this->renderRadios($name, $value, $options);
        } else {
            return $this->renderSelect($name, $value, $options);
        }
    }

    protected function renderSelect(string $name, $value, array $options): string
    {
        $attributes = $this->renderAttributes($options);
        $selectId = $this->generateId($name);

        if ($options['multiple']) {
            $attributes .= ' multiple';
            $name .= '[]';
        }

        if ($options['searchable'] && !$options['multiple']) {
            $attributes .= ' data-searchable="true"';
        }

        $html = $this->renderLabel($name, $options);
        $html .= $this->renderHelpText($options);

        $html .= '<select name="' . $this->escapeValue($name) . '" ';
        $html .= 'id="' . $selectId . '" ';
        $html .= $attributes . '>';

        // Add placeholder option
        if (!$options['multiple'] && !$options['required']) {
            $html .= '<option value="">' . $this->escapeValue($options['placeholder_text']) . '</option>';
        }

        // Render choices
        $html .= $this->renderChoiceOptions($value, $options);

        $html .= '</select>';

        // Add searchable functionality
        if ($options['searchable']) {
            $html .= $this->renderSearchableScript($selectId);
        }

        return $html;
    }

    protected function renderRadios(string $name, $value, array $options): string
    {
        $html = $this->renderLabel($name, $options);
        $html .= $this->renderHelpText($options);

        $html .= '<div class="space-y-2">';

        foreach ($options['choices'] as $choiceValue => $choiceLabel) {
            $radioId = $this->generateId($name . '_' . $choiceValue);
            $checked = ($value == $choiceValue) ? 'checked' : '';

            $html .= '<div class="flex items-center">';
            $html .= '<input type="radio" ';
            $html .= 'name="' . $this->escapeValue($name) . '" ';
            $html .= 'id="' . $radioId . '" ';
            $html .= 'value="' . $this->escapeValue($choiceValue) . '" ';
            $html .= $checked . ' ';
            $html .= 'class="form-radio">';

            $html .= '<label for="' . $radioId . '" class="ml-2 text-sm text-gray-700">';
            $html .= $this->escapeValue($this->getChoiceLabel($choiceValue, $options['choices'], $options));
            $html .= '</label>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    protected function renderCheckboxes(string $name, $value, array $options): string
    {
        $html = $this->renderLabel($name, $options);
        $html .= $this->renderHelpText($options);

        if (!is_array($value)) {
            $value = $value ? [$value] : [];
        }

        $html .= '<div class="space-y-2">';

        foreach ($options['choices'] as $choiceValue => $choiceLabel) {
            $checkboxId = $this->generateId($name . '_' . $choiceValue);
            $checked = in_array($choiceValue, $value) ? 'checked' : '';

            $html .= '<div class="flex items-center">';
            $html .= '<input type="checkbox" ';
            $html .= 'name="' . $this->escapeValue($name) . '[]" ';
            $html .= 'id="' . $checkboxId . '" ';
            $html .= 'value="' . $this->escapeValue($choiceValue) . '" ';
            $html .= $checked . ' ';
            $html .= 'class="form-checkbox">';

            $html .= '<label for="' . $checkboxId . '" class="ml-2 text-sm text-gray-700">';
            $html .= $this->escapeValue($this->getChoiceLabel($choiceValue, $options['choices'], $options));
            $html .= '</label>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    protected function renderChoiceOptions($value, array $options): string
    {
        $html = '';
        $choices = $options['choices'];

        // Group choices if group_by is specified
        if ($options['group_by'] && is_callable($options['group_by'])) {
            $grouped = [];
            foreach ($choices as $choiceValue => $choiceLabel) {
                $group = call_user_func($options['group_by'], $choiceValue, $choiceLabel);
                $grouped[$group][$choiceValue] = $choiceLabel;
            }

            foreach ($grouped as $groupName => $groupChoices) {
                $html .= '<optgroup label="' . $this->escapeValue($groupName) . '">';
                foreach ($groupChoices as $choiceValue => $choiceLabel) {
                    $html .= $this->renderOption($choiceValue, $choiceLabel, $value, $options);
                }
                $html .= '</optgroup>';
            }
        } else {
            foreach ($choices as $choiceValue => $choiceLabel) {
                $html .= $this->renderOption($choiceValue, $choiceLabel, $value, $options);
            }
        }

        return $html;
    }

    protected function renderOption($choiceValue, $choiceLabel, $value, array $options): string
    {
        $selected = '';
        if ($options['multiple']) {
            if (is_array($value) && in_array($choiceValue, $value)) {
                $selected = 'selected';
            }
        } else {
            if ($value == $choiceValue) {
                $selected = 'selected';
            }
        }

        $label = $this->getChoiceLabel($choiceValue, $options['choices'], $options);

        $html = '<option value="' . $this->escapeValue($choiceValue) . '" ' . $selected;

        // Add custom attributes if specified
        if (!empty($options['choice_attr']) && isset($options['choice_attr'][$choiceValue])) {
            foreach ($options['choice_attr'][$choiceValue] as $attrName => $attrValue) {
                $html .= ' ' . $this->escapeValue($attrName) . '="' . $this->escapeValue($attrValue) . '"';
            }
        }

        $html .= '>' . $this->escapeValue($label) . '</option>';

        return $html;
    }

    protected function getChoiceLabel($value, array $choices, array $options): string
    {
        // Use custom choice_label function if provided
        if ($options['choice_label'] && is_callable($options['choice_label'])) {
            return call_user_func($options['choice_label'], $value, $choices[$value] ?? null);
        }

        // Return the label from choices array
        return $choices[$value] ?? (string)$value;
    }

    protected function renderSearchableScript(string $selectId): string
    {
        return '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const select = document.getElementById("' . $selectId . '");
            if (select) {
                makeSelectSearchable(select);
            }
        });

        function makeSelectSearchable(select) {
            const wrapper = document.createElement("div");
            wrapper.className = "relative";
            
            const searchInput = document.createElement("input");
            searchInput.type = "text";
            searchInput.className = "form-input pr-8";
            searchInput.placeholder = "Ara ve seç...";
            
            const dropdownButton = document.createElement("button");
            dropdownButton.type = "button";
            dropdownButton.className = "absolute inset-y-0 right-0 pr-2 flex items-center";
            dropdownButton.innerHTML = "▼";
            
            const dropdown = document.createElement("div");
            dropdown.className = "absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto hidden";
            
            // Populate dropdown with options
            Array.from(select.options).forEach(option => {
                if (option.value === "") return;
                
                const item = document.createElement("div");
                item.className = "px-3 py-2 hover:bg-gray-100 cursor-pointer";
                item.textContent = option.textContent;
                item.dataset.value = option.value;
                
                item.addEventListener("click", function() {
                    select.value = this.dataset.value;
                    searchInput.value = this.textContent;
                    dropdown.classList.add("hidden");
                    select.dispatchEvent(new Event("change"));
                });
                
                dropdown.appendChild(item);
            });
            
            // Search functionality
            searchInput.addEventListener("input", function() {
                const searchTerm = this.value.toLowerCase();
                Array.from(dropdown.children).forEach(item => {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(searchTerm) ? "block" : "none";
                });
            });
            
            // Toggle dropdown
            searchInput.addEventListener("focus", () => dropdown.classList.remove("hidden"));
            dropdownButton.addEventListener("click", () => dropdown.classList.toggle("hidden"));
            
            // Hide dropdown when clicking outside
            document.addEventListener("click", function(e) {
                if (!wrapper.contains(e.target)) {
                    dropdown.classList.add("hidden");
                }
            });
            
            // Replace select with custom component
            select.style.display = "none";
            select.parentNode.insertBefore(wrapper, select);
            
            wrapper.appendChild(searchInput);
            wrapper.appendChild(dropdownButton);
            wrapper.appendChild(dropdown);
            wrapper.appendChild(select);
        }
        </script>';
    }

    public function validate($value, array $options = []): array
    {
        $errors = parent::validate($value, $options);
        $options = array_merge($this->getDefaultOptions(), $options);

        if (!$this->isEmpty($value)) {
            $choices = $options['choices'];

            if ($options['multiple']) {
                if (!is_array($value)) {
                    $errors[] = ($options['label'] ?? 'Bu alan') . ' geçerli bir seçim listesi olmalıdır.';
                } else {
                    foreach ($value as $val) {
                        if (!array_key_exists($val, $choices) && !$options['allow_custom']) {
                            $errors[] = ($options['label'] ?? 'Bu alan') . ' geçersiz seçim içeriyor: ' . $val;
                        }
                    }
                }
            } else {
                if (!array_key_exists($value, $choices) && !$options['allow_custom']) {
                    $errors[] = ($options['label'] ?? 'Bu alan') . ' geçerli bir seçim olmalıdır.';
                }
            }
        }

        return $errors;
    }

    public function processFormValue($value, array $options = [])
    {
        if ($this->isEmpty($value)) {
            return $options['multiple'] ? [] : null;
        }

        $options = array_merge($this->getDefaultOptions(), $options);

        if ($options['multiple']) {
            return is_array($value) ? array_values($value) : [$value];
        } else {
            return is_array($value) ? $value[0] : $value;
        }
    }

    /**
     * Create choices from array of objects
     */
    public static function fromEntities(array $entities, string $labelProperty = 'name', string $valueProperty = 'id'): array
    {
        $choices = [];
        
        foreach ($entities as $entity) {
            $value = is_object($entity) 
                ? (method_exists($entity, 'get' . ucfirst($valueProperty)) 
                    ? $entity->{'get' . ucfirst($valueProperty)}() 
                    : $entity->$valueProperty)
                : $entity[$valueProperty];
                
            $label = is_object($entity)
                ? (method_exists($entity, 'get' . ucfirst($labelProperty))
                    ? $entity->{'get' . ucfirst($labelProperty)}()
                    : $entity->$labelProperty)
                : $entity[$labelProperty];
                
            $choices[$value] = $label;
        }
        
        return $choices;
    }

    /**
     * Create grouped choices
     */
    public static function createGrouped(array $groups): array
    {
        $choices = [];
        
        foreach ($groups as $groupName => $groupItems) {
            foreach ($groupItems as $value => $label) {
                $choices[$value] = $label;
            }
        }
        
        return $choices;
    }

    /**
     * Create choices from enum
     */
    public static function fromEnum(string $enumClass): array
    {
        if (!class_exists($enumClass)) {
            return [];
        }

        $choices = [];
        
        // PHP 8.1+ enum support
        if (enum_exists($enumClass)) {
            foreach ($enumClass::cases() as $case) {
                $choices[$case->value ?? $case->name] = $case->name;
            }
        }
        
        return $choices;
    }
}
