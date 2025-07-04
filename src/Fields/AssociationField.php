<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

class AssociationField extends AbstractFieldType
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
        'target_entity',
        'property',
        'multiple',
        'autocomplete',
        'min_length',
        'query_builder',
        'choice_label',
        'choice_value',
        'expanded',
        'placeholder',
        'empty_value'
    ];

    public function getTypeName(): string
    {
        return 'association';
    }

    public function getDefaultOptions(): array
    {
        return array_merge(parent::getDefaultOptions(), [
            'target_entity' => null,
            'property' => 'name',
            'multiple' => false,
            'autocomplete' => true,
            'min_length' => 2,
            'query_builder' => null,
            'choice_label' => null,
            'choice_value' => 'id',
            'expanded' => false,
            'placeholder' => 'Seçiniz...',
            'empty_value' => ''
        ]);
    }

    public function renderDisplay($value, array $options = []): string
    {
        if ($this->isEmpty($value)) {
            return '<span class="text-gray-400">-</span>';
        }

        $options = array_merge($this->getDefaultOptions(), $options);

        if ($options['multiple'] && is_array($value)) {
            return $this->renderMultipleAssociations($value, $options);
        } else {
            return $this->renderSingleAssociation($value, $options);
        }
    }

    protected function renderSingleAssociation($entity, array $options): string
    {
        $displayValue = $this->getEntityDisplayValue($entity, $options);
        
        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">'
               . $this->escapeValue($displayValue)
               . '</span>';
    }

    protected function renderMultipleAssociations(array $entities, array $options): string
    {
        $html = '<div class="flex flex-wrap gap-1">';
        
        foreach ($entities as $entity) {
            $html .= $this->renderSingleAssociation($entity, $options);
        }
        
        $html .= '</div>';
        
        return $html;
    }

    public function renderFormInput(string $name, $value, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        $fieldId = $this->generateId($name);

        $html = $this->renderLabel($name, $options);
        $html .= $this->renderHelpText($options);

        if ($options['expanded']) {
            return $html . $this->renderExpandedInput($name, $value, $options);
        } elseif ($options['autocomplete']) {
            return $html . $this->renderAutocompleteInput($name, $value, $options);
        } else {
            return $html . $this->renderSelectInput($name, $value, $options);
        }
    }

    protected function renderSelectInput(string $name, $value, array $options): string
    {
        $fieldId = $this->generateId($name);
        $attributes = $this->renderAttributes($options);
        
        if ($options['multiple']) {
            $attributes .= ' multiple';
            $name .= '[]';
        }

        $html = '<select name="' . $this->escapeValue($name) . '" ';
        $html .= 'id="' . $fieldId . '" ';
        $html .= $attributes . '>';

        // Empty option
        if (!$options['multiple'] && !$options['required']) {
            $html .= '<option value="' . $this->escapeValue($options['empty_value']) . '">';
            $html .= $this->escapeValue($options['placeholder']);
            $html .= '</option>';
        }

        // Load options
        $choices = $this->loadChoices($options);
        $selectedValues = $this->getSelectedValues($value, $options);

        foreach ($choices as $choice) {
            $choiceValue = $this->getEntityValue($choice, $options['choice_value']);
            $choiceLabel = $this->getEntityDisplayValue($choice, $options);
            $selected = in_array($choiceValue, $selectedValues) ? 'selected' : '';
            
            $html .= '<option value="' . $this->escapeValue($choiceValue) . '" ' . $selected . '>';
            $html .= $this->escapeValue($choiceLabel);
            $html .= '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    protected function renderAutocompleteInput(string $name, $value, array $options): string
    {
        $fieldId = $this->generateId($name);
        $displayValue = '';
        $hiddenValue = '';

        if (!$this->isEmpty($value)) {
            if ($options['multiple'] && is_array($value)) {
                $hiddenValue = implode(',', array_map(function($entity) use ($options) {
                    return $this->getEntityValue($entity, $options['choice_value']);
                }, $value));
                $displayValue = implode(', ', array_map(function($entity) use ($options) {
                    return $this->getEntityDisplayValue($entity, $options);
                }, $value));
            } else {
                $hiddenValue = $this->getEntityValue($value, $options['choice_value']);
                $displayValue = $this->getEntityDisplayValue($value, $options);
            }
        }

        $html = '<div class="association-autocomplete" data-field-id="' . $fieldId . '">';
        
        // Hidden input for actual value
        $html .= '<input type="hidden" name="' . $this->escapeValue($name) . '" ';
        $html .= 'id="' . $fieldId . '_value" ';
        $html .= 'value="' . $this->escapeValue($hiddenValue) . '">';
        
        // Display input for autocomplete
        $html .= '<input type="text" ';
        $html .= 'id="' . $fieldId . '" ';
        $html .= 'value="' . $this->escapeValue($displayValue) . '" ';
        $html .= 'placeholder="' . $this->escapeValue($options['placeholder']) . '" ';
        $html .= 'class="' . ($options['css_class'] ?? 'form-input') . '" ';
        $html .= 'autocomplete="off">';
        
        // Results dropdown
        $html .= '<div id="' . $fieldId . '_results" class="autocomplete-results hidden absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">';
        $html .= '</div>';
        
        $html .= '</div>';

        // Add autocomplete JavaScript
        $html .= $this->renderAutocompleteScript($fieldId, $options);

        return $html;
    }

    protected function renderExpandedInput(string $name, $value, array $options): string
    {
        $choices = $this->loadChoices($options);
        $selectedValues = $this->getSelectedValues($value, $options);
        $inputType = $options['multiple'] ? 'checkbox' : 'radio';
        
        $html = '<div class="space-y-2">';
        
        foreach ($choices as $choice) {
            $choiceValue = $this->getEntityValue($choice, $options['choice_value']);
            $choiceLabel = $this->getEntityDisplayValue($choice, $options);
            $checked = in_array($choiceValue, $selectedValues) ? 'checked' : '';
            $fieldName = $options['multiple'] ? $name . '[]' : $name;
            
            $html .= '<label class="inline-flex items-center">';
            $html .= '<input type="' . $inputType . '" ';
            $html .= 'name="' . $this->escapeValue($fieldName) . '" ';
            $html .= 'value="' . $this->escapeValue($choiceValue) . '" ';
            $html .= $checked . ' class="form-' . $inputType . '">';
            $html .= '<span class="ml-2">' . $this->escapeValue($choiceLabel) . '</span>';
            $html .= '</label>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    protected function renderAutocompleteScript(string $fieldId, array $options): string
    {
        $targetEntity = $options['target_entity'];
        $minLength = $options['min_length'];
        $multiple = $options['multiple'] ? 'true' : 'false';

        return '<script>
        document.addEventListener("DOMContentLoaded", function() {
            setupAssociationAutocomplete("' . $fieldId . '", {
                targetEntity: "' . $targetEntity . '",
                minLength: ' . $minLength . ',
                multiple: ' . $multiple . '
            });
        });

        function setupAssociationAutocomplete(fieldId, options) {
            const input = document.getElementById(fieldId);
            const hiddenInput = document.getElementById(fieldId + "_value");
            const results = document.getElementById(fieldId + "_results");
            let currentRequest = null;

            input.addEventListener("input", function() {
                const query = this.value;
                
                if (query.length < options.minLength) {
                    results.classList.add("hidden");
                    return;
                }

                // Cancel previous request
                if (currentRequest) {
                    currentRequest.abort();
                }

                // Make new request
                currentRequest = fetch("/admin/api/autocomplete", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        entity: options.targetEntity,
                        query: query,
                        limit: 10
                    })
                })
                .then(response => response.json())
                .then(data => {
                    displayResults(data, fieldId, options);
                })
                .catch(error => {
                    if (error.name !== "AbortError") {
                        console.error("Autocomplete error:", error);
                    }
                });
            });

            // Hide results when clicking outside
            document.addEventListener("click", function(e) {
                if (!input.contains(e.target) && !results.contains(e.target)) {
                    results.classList.add("hidden");
                }
            });
        }

        function displayResults(data, fieldId, options) {
            const results = document.getElementById(fieldId + "_results");
            const input = document.getElementById(fieldId);
            const hiddenInput = document.getElementById(fieldId + "_value");
            
            results.innerHTML = "";
            
            if (data.length === 0) {
                results.innerHTML = "<div class=\"p-2 text-gray-500\">Sonuç bulunamadı</div>";
            } else {
                data.forEach(item => {
                    const div = document.createElement("div");
                    div.className = "p-2 hover:bg-gray-100 cursor-pointer";
                    div.textContent = item.label;
                    div.onclick = function() {
                        selectItem(item, fieldId, options);
                    };
                    results.appendChild(div);
                });
            }
            
            results.classList.remove("hidden");
        }

        function selectItem(item, fieldId, options) {
            const input = document.getElementById(fieldId);
            const hiddenInput = document.getElementById(fieldId + "_value");
            const results = document.getElementById(fieldId + "_results");
            
            if (options.multiple) {
                // Handle multiple selection
                const currentValues = hiddenInput.value ? hiddenInput.value.split(",") : [];
                const currentLabels = input.value ? input.value.split(", ") : [];
                
                if (!currentValues.includes(item.value)) {
                    currentValues.push(item.value);
                    currentLabels.push(item.label);
                    
                    hiddenInput.value = currentValues.join(",");
                    input.value = currentLabels.join(", ");
                }
            } else {
                // Handle single selection
                hiddenInput.value = item.value;
                input.value = item.label;
            }
            
            results.classList.add("hidden");
            input.dispatchEvent(new Event("change"));
        }
        </script>';
    }

    protected function loadChoices(array $options): array
    {
        // This would typically load from database
        // For now, return empty array - would be implemented with actual ORM
        return [];
    }

    protected function getSelectedValues($value, array $options): array
    {
        if ($this->isEmpty($value)) {
            return [];
        }

        if ($options['multiple'] && is_array($value)) {
            return array_map(function($entity) use ($options) {
                return $this->getEntityValue($entity, $options['choice_value']);
            }, $value);
        } else {
            return [$this->getEntityValue($value, $options['choice_value'])];
        }
    }

    protected function getEntityValue($entity, string $property)
    {
        if (is_array($entity)) {
            return $entity[$property] ?? null;
        } elseif (is_object($entity)) {
            $getter = 'get' . ucfirst($property);
            if (method_exists($entity, $getter)) {
                return $entity->$getter();
            }
            if (property_exists($entity, $property)) {
                return $entity->$property;
            }
        }
        
        return $entity;
    }

    protected function getEntityDisplayValue($entity, array $options): string
    {
        if ($options['choice_label'] && is_callable($options['choice_label'])) {
            return call_user_func($options['choice_label'], $entity);
        }

        $property = $options['choice_label'] ?? $options['property'];
        $value = $this->getEntityValue($entity, $property);
        
        return (string)$value;
    }

    public function validate($value, array $options = []): array
    {
        $errors = parent::validate($value, $options);
        $options = array_merge($this->getDefaultOptions(), $options);

        if (!$this->isEmpty($value)) {
            if ($options['multiple']) {
                if (!is_array($value)) {
                    $errors[] = ($options['label'] ?? 'İlişkili kayıt') . ' geçerli bir liste olmalıdır.';
                }
            } else {
                // Validate single association
                if (!$this->isValidEntity($value, $options)) {
                    $errors[] = ($options['label'] ?? 'İlişkili kayıt') . ' geçerli bir kayıt olmalıdır.';
                }
            }
        }

        return $errors;
    }

    protected function isValidEntity($entity, array $options): bool
    {
        // This would validate against the target entity
        // For now, just basic validation
        return !empty($entity);
    }

    public function processFormValue($value, array $options = [])
    {
        if ($this->isEmpty($value)) {
            return null;
        }

        $options = array_merge($this->getDefaultOptions(), $options);

        // This would typically load entities from database by ID
        // For now, return the value as-is
        return $value;
    }

    /**
     * Create association field for specific entity
     */
    public static function createFor(string $targetEntity, array $options = []): self
    {
        $field = new self();
        $options['target_entity'] = $targetEntity;
        
        return $field;
    }

    /**
     * Set query builder for custom filtering
     */
    public function setQueryBuilder(callable $queryBuilder): self
    {
        $this->supportedOptions[] = 'query_builder';
        return $this;
    }
}
