<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Turkpin\AdminKit\Services\CacheService;

class DynamicFormService
{
    private CacheService $cacheService;
    private array $config;
    private array $forms;
    private array $conditions;
    private array $dependencies;

    public function __construct(CacheService $cacheService, array $config = [])
    {
        $this->cacheService = $cacheService;
        $this->config = array_merge([
            'cache_enabled' => true,
            'cache_ttl' => 3600,
            'enable_validation' => true,
            'enable_conditional_logic' => true,
            'enable_multi_step' => true,
            'enable_dynamic_options' => true
        ], $config);
        
        $this->forms = [];
        $this->conditions = [];
        $this->dependencies = [];
    }

    /**
     * Register a dynamic form
     */
    public function registerForm(string $formId, array $config): void
    {
        $this->forms[$formId] = array_merge([
            'title' => 'Dynamic Form',
            'description' => '',
            'steps' => [],
            'fields' => [],
            'conditions' => [],
            'validations' => [],
            'ajax_validation' => false,
            'auto_save' => false,
            'progress_indicator' => true
        ], $config);
    }

    /**
     * Add conditional field logic
     */
    public function addCondition(string $formId, string $fieldName, array $condition): void
    {
        if (!isset($this->conditions[$formId])) {
            $this->conditions[$formId] = [];
        }

        $this->conditions[$formId][$fieldName] = array_merge([
            'dependsOn' => '',
            'operator' => 'equals',
            'value' => '',
            'action' => 'show', // show, hide, enable, disable, require
            'animation' => 'fade'
        ], $condition);
    }

    /**
     * Add field dependency
     */
    public function addDependency(string $formId, string $fieldName, array $dependency): void
    {
        if (!isset($this->dependencies[$formId])) {
            $this->dependencies[$formId] = [];
        }

        $this->dependencies[$formId][$fieldName] = array_merge([
            'sourceField' => '',
            'sourceValue' => '',
            'targetAction' => 'loadOptions', // loadOptions, setValue, clear
            'endpoint' => '',
            'parameters' => []
        ], $dependency);
    }

    /**
     * Render dynamic form
     */
    public function renderForm(string $formId, array $data = []): string
    {
        if (!isset($this->forms[$formId])) {
            return '<div class="text-red-500">Form not found: ' . htmlspecialchars($formId) . '</div>';
        }

        $form = $this->forms[$formId];
        $conditions = $this->conditions[$formId] ?? [];
        $dependencies = $this->dependencies[$formId] ?? [];

        $html = '<div class="dynamic-form" data-form-id="' . htmlspecialchars($formId) . '">';
        
        // Form header
        $html .= '<div class="form-header mb-6">';
        $html .= '<h2 class="text-xl font-bold text-gray-900">' . htmlspecialchars($form['title']) . '</h2>';
        if ($form['description']) {
            $html .= '<p class="text-gray-600 mt-2">' . htmlspecialchars($form['description']) . '</p>';
        }
        $html .= '</div>';

        // Multi-step progress indicator
        if (!empty($form['steps']) && $form['progress_indicator']) {
            $html .= $this->renderProgressIndicator($form['steps']);
        }

        $html .= '<form id="dynamic-form-' . $formId . '" class="space-y-6">';

        // Render steps or fields
        if (!empty($form['steps'])) {
            $html .= $this->renderSteps($formId, $form['steps'], $data);
        } else {
            $html .= $this->renderFields($formId, $form['fields'], $data);
        }

        // Form actions
        $html .= '<div class="form-actions flex justify-between mt-8">';
        
        if (!empty($form['steps'])) {
            $html .= '<button type="button" id="prev-step" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400" style="display: none;">Previous</button>';
            $html .= '<button type="button" id="next-step" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Next</button>';
            $html .= '<button type="submit" id="submit-form" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700" style="display: none;">Submit</button>';
        } else {
            $html .= '<div></div>'; // Spacer
            $html .= '<button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Submit</button>';
        }
        
        $html .= '</div>';

        $html .= '</form>';

        // Render JavaScript
        $html .= $this->renderFormScript($formId, $conditions, $dependencies, $form);

        $html .= '</div>';

        return $html;
    }

    /**
     * Render progress indicator for multi-step forms
     */
    private function renderProgressIndicator(array $steps): string
    {
        $html = '<div class="progress-indicator mb-8">';
        $html .= '<div class="flex items-center justify-between">';
        
        foreach ($steps as $index => $step) {
            $isFirst = $index === 0;
            $stepNumber = $index + 1;
            
            $html .= '<div class="flex items-center">';
            
            // Step circle
            $html .= '<div class="step-circle w-8 h-8 rounded-full border-2 border-gray-300 flex items-center justify-center text-sm font-medium" data-step="' . $index . '">';
            $html .= $stepNumber;
            $html .= '</div>';
            
            // Step label
            $html .= '<div class="ml-2 text-sm">';
            $html .= '<div class="step-title font-medium text-gray-900">' . htmlspecialchars($step['title']) . '</div>';
            if (isset($step['description'])) {
                $html .= '<div class="step-description text-gray-500">' . htmlspecialchars($step['description']) . '</div>';
            }
            $html .= '</div>';
            
            // Connector line
            if ($index < count($steps) - 1) {
                $html .= '<div class="step-connector flex-1 h-0.5 bg-gray-300 mx-4"></div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render multi-step form
     */
    private function renderSteps(string $formId, array $steps, array $data): string
    {
        $html = '';
        
        foreach ($steps as $index => $step) {
            $isFirst = $index === 0;
            $stepId = "step-{$index}";
            
            $html .= '<div class="form-step" id="' . $stepId . '" data-step="' . $index . '"';
            $html .= $isFirst ? '' : ' style="display: none;"';
            $html .= '>';
            
            // Step header
            $html .= '<div class="step-header mb-6">';
            $html .= '<h3 class="text-lg font-medium text-gray-900">' . htmlspecialchars($step['title']) . '</h3>';
            if (isset($step['description'])) {
                $html .= '<p class="text-gray-600 mt-1">' . htmlspecialchars($step['description']) . '</p>';
            }
            $html .= '</div>';
            
            // Step fields
            $html .= $this->renderFields($formId, $step['fields'] ?? [], $data);
            
            $html .= '</div>';
        }
        
        return $html;
    }

    /**
     * Render form fields
     */
    private function renderFields(string $formId, array $fields, array $data): string
    {
        $html = '';
        
        foreach ($fields as $fieldName => $field) {
            $html .= $this->renderField($formId, $fieldName, $field, $data[$fieldName] ?? null);
        }
        
        return $html;
    }

    /**
     * Render individual field
     */
    private function renderField(string $formId, string $fieldName, array $field, $value = null): string
    {
        $fieldId = "field-{$formId}-{$fieldName}";
        $conditions = $this->conditions[$formId][$fieldName] ?? null;
        
        $html = '<div class="form-field" id="' . $fieldId . '-container"';
        
        // Add conditional attributes
        if ($conditions) {
            $html .= ' data-condition="' . htmlspecialchars(json_encode($conditions)) . '"';
        }
        
        $html .= '>';
        
        // Field label
        if (isset($field['label'])) {
            $html .= '<label for="' . $fieldId . '" class="block text-sm font-medium text-gray-700 mb-2">';
            $html .= htmlspecialchars($field['label']);
            if ($field['required'] ?? false) {
                $html .= ' <span class="text-red-500">*</span>';
            }
            $html .= '</label>';
        }
        
        // Field input
        $html .= $this->renderFieldInput($fieldId, $fieldName, $field, $value);
        
        // Field help text
        if (isset($field['help'])) {
            $html .= '<p class="text-sm text-gray-500 mt-1">' . htmlspecialchars($field['help']) . '</p>';
        }
        
        // Validation message container
        $html .= '<div class="field-error text-red-500 text-sm mt-1" style="display: none;"></div>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render field input based on type
     */
    private function renderFieldInput(string $fieldId, string $fieldName, array $field, $value): string
    {
        $type = $field['type'] ?? 'text';
        $attributes = [
            'id' => $fieldId,
            'name' => $fieldName,
            'class' => 'mt-1 block w-full border-gray-300 rounded-md shadow-sm'
        ];

        if ($field['required'] ?? false) {
            $attributes['required'] = 'required';
        }

        if (isset($field['placeholder'])) {
            $attributes['placeholder'] = $field['placeholder'];
        }

        $attributeString = $this->buildAttributeString($attributes);

        switch ($type) {
            case 'text':
            case 'email':
            case 'password':
            case 'url':
            case 'tel':
                return '<input type="' . $type . '" value="' . htmlspecialchars($value ?? '') . '" ' . $attributeString . '>';

            case 'textarea':
                $rows = $field['rows'] ?? 4;
                return '<textarea rows="' . $rows . '" ' . $attributeString . '>' . htmlspecialchars($value ?? '') . '</textarea>';

            case 'select':
                $html = '<select ' . $attributeString . '>';
                if (isset($field['placeholder'])) {
                    $html .= '<option value="">' . htmlspecialchars($field['placeholder']) . '</option>';
                }
                foreach ($field['options'] ?? [] as $optionValue => $optionLabel) {
                    $selected = ($value == $optionValue) ? ' selected' : '';
                    $html .= '<option value="' . htmlspecialchars($optionValue) . '"' . $selected . '>';
                    $html .= htmlspecialchars($optionLabel);
                    $html .= '</option>';
                }
                $html .= '</select>';
                return $html;

            case 'checkbox':
                $checked = $value ? ' checked' : '';
                return '<input type="checkbox" value="1"' . $checked . ' ' . $attributeString . '>';

            case 'radio':
                $html = '';
                foreach ($field['options'] ?? [] as $optionValue => $optionLabel) {
                    $checked = ($value == $optionValue) ? ' checked' : '';
                    $radioId = $fieldId . '-' . $optionValue;
                    $html .= '<div class="flex items-center">';
                    $html .= '<input type="radio" id="' . $radioId . '" name="' . $fieldName . '" value="' . htmlspecialchars($optionValue) . '"' . $checked . ' class="mr-2">';
                    $html .= '<label for="' . $radioId . '" class="text-sm text-gray-700">' . htmlspecialchars($optionLabel) . '</label>';
                    $html .= '</div>';
                }
                return $html;

            case 'number':
                $min = isset($field['min']) ? ' min="' . $field['min'] . '"' : '';
                $max = isset($field['max']) ? ' max="' . $field['max'] . '"' : '';
                $step = isset($field['step']) ? ' step="' . $field['step'] . '"' : '';
                return '<input type="number" value="' . htmlspecialchars($value ?? '') . '"' . $min . $max . $step . ' ' . $attributeString . '>';

            case 'date':
            case 'datetime-local':
            case 'time':
                return '<input type="' . $type . '" value="' . htmlspecialchars($value ?? '') . '" ' . $attributeString . '>';

            case 'file':
                $accept = isset($field['accept']) ? ' accept="' . htmlspecialchars($field['accept']) . '"' : '';
                $multiple = ($field['multiple'] ?? false) ? ' multiple' : '';
                return '<input type="file"' . $accept . $multiple . ' ' . $attributeString . '>';

            default:
                return '<input type="text" value="' . htmlspecialchars($value ?? '') . '" ' . $attributeString . '>';
        }
    }

    /**
     * Build HTML attribute string
     */
    private function buildAttributeString(array $attributes): string
    {
        $parts = [];
        
        foreach ($attributes as $name => $value) {
            if ($value === true || $value === $name) {
                $parts[] = $name;
            } else {
                $parts[] = $name . '="' . htmlspecialchars($value) . '"';
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Render form JavaScript
     */
    private function renderFormScript(string $formId, array $conditions, array $dependencies, array $form): string
    {
        $config = [
            'formId' => $formId,
            'conditions' => $conditions,
            'dependencies' => $dependencies,
            'ajaxValidation' => $form['ajax_validation'] ?? false,
            'autoSave' => $form['auto_save'] ?? false,
            'steps' => $form['steps'] ?? []
        ];

        return '
        <script>
        (function() {
            const config = ' . json_encode($config) . ';
            
            class DynamicForm {
                constructor(config) {
                    this.config = config;
                    this.currentStep = 0;
                    this.formElement = document.getElementById("dynamic-form-" + config.formId);
                    this.init();
                }
                
                init() {
                    this.setupConditionalLogic();
                    this.setupDependencies();
                    this.setupMultiStep();
                    this.setupValidation();
                    this.setupAutoSave();
                }
                
                setupConditionalLogic() {
                    Object.entries(this.config.conditions).forEach(([fieldName, condition]) => {
                        const sourceField = document.querySelector(`[name="${condition.dependsOn}"]`);
                        const targetContainer = document.getElementById(`field-${this.config.formId}-${fieldName}-container`);
                        
                        if (sourceField && targetContainer) {
                            sourceField.addEventListener("change", () => {
                                this.evaluateCondition(sourceField, targetContainer, condition);
                            });
                            
                            // Initial evaluation
                            this.evaluateCondition(sourceField, targetContainer, condition);
                        }
                    });
                }
                
                evaluateCondition(sourceField, targetContainer, condition) {
                    const sourceValue = this.getFieldValue(sourceField);
                    const conditionMet = this.checkCondition(sourceValue, condition.operator, condition.value);
                    
                    this.applyAction(targetContainer, condition.action, conditionMet, condition.animation);
                }
                
                checkCondition(sourceValue, operator, targetValue) {
                    switch (operator) {
                        case "equals":
                            return sourceValue == targetValue;
                        case "not_equals":
                            return sourceValue != targetValue;
                        case "greater_than":
                            return parseFloat(sourceValue) > parseFloat(targetValue);
                        case "less_than":
                            return parseFloat(sourceValue) < parseFloat(targetValue);
                        case "contains":
                            return sourceValue.includes(targetValue);
                        case "empty":
                            return !sourceValue || sourceValue === "";
                        case "not_empty":
                            return sourceValue && sourceValue !== "";
                        default:
                            return false;
                    }
                }
                
                applyAction(targetContainer, action, conditionMet, animation) {
                    const targetField = targetContainer.querySelector("input, select, textarea");
                    
                    switch (action) {
                        case "show":
                            if (conditionMet) {
                                this.showElement(targetContainer, animation);
                            } else {
                                this.hideElement(targetContainer, animation);
                            }
                            break;
                        case "hide":
                            if (conditionMet) {
                                this.hideElement(targetContainer, animation);
                            } else {
                                this.showElement(targetContainer, animation);
                            }
                            break;
                        case "enable":
                            if (targetField) {
                                targetField.disabled = !conditionMet;
                            }
                            break;
                        case "disable":
                            if (targetField) {
                                targetField.disabled = conditionMet;
                            }
                            break;
                        case "require":
                            if (targetField) {
                                if (conditionMet) {
                                    targetField.setAttribute("required", "required");
                                } else {
                                    targetField.removeAttribute("required");
                                }
                            }
                            break;
                    }
                }
                
                showElement(element, animation) {
                    if (animation === "fade") {
                        element.style.opacity = "0";
                        element.style.display = "block";
                        element.style.transition = "opacity 0.3s";
                        setTimeout(() => {
                            element.style.opacity = "1";
                        }, 10);
                    } else {
                        element.style.display = "block";
                    }
                }
                
                hideElement(element, animation) {
                    if (animation === "fade") {
                        element.style.transition = "opacity 0.3s";
                        element.style.opacity = "0";
                        setTimeout(() => {
                            element.style.display = "none";
                        }, 300);
                    } else {
                        element.style.display = "none";
                    }
                }
                
                setupDependencies() {
                    Object.entries(this.config.dependencies).forEach(([fieldName, dependency]) => {
                        const sourceField = document.querySelector(`[name="${dependency.sourceField}"]`);
                        const targetField = document.querySelector(`[name="${fieldName}"]`);
                        
                        if (sourceField && targetField) {
                            sourceField.addEventListener("change", () => {
                                this.handleDependency(sourceField, targetField, dependency);
                            });
                        }
                    });
                }
                
                handleDependency(sourceField, targetField, dependency) {
                    const sourceValue = this.getFieldValue(sourceField);
                    
                    if (sourceValue === dependency.sourceValue) {
                        switch (dependency.targetAction) {
                            case "loadOptions":
                                this.loadDynamicOptions(targetField, dependency.endpoint, {
                                    ...dependency.parameters,
                                    [dependency.sourceField]: sourceValue
                                });
                                break;
                            case "setValue":
                                this.setFieldValue(targetField, dependency.value);
                                break;
                            case "clear":
                                this.setFieldValue(targetField, "");
                                break;
                        }
                    }
                }
                
                loadDynamicOptions(selectField, endpoint, parameters) {
                    const url = new URL(endpoint, window.location.origin);
                    Object.entries(parameters).forEach(([key, value]) => {
                        url.searchParams.append(key, value);
                    });
                    
                    fetch(url)
                        .then(response => response.json())
                        .then(options => {
                            selectField.innerHTML = "";
                            if (selectField.hasAttribute("placeholder")) {
                                const placeholder = selectField.getAttribute("placeholder");
                                selectField.innerHTML = `<option value="">${placeholder}</option>`;
                            }
                            
                            Object.entries(options).forEach(([value, label]) => {
                                const option = document.createElement("option");
                                option.value = value;
                                option.textContent = label;
                                selectField.appendChild(option);
                            });
                        })
                        .catch(error => {
                            console.error("Failed to load dynamic options:", error);
                        });
                }
                
                setupMultiStep() {
                    if (this.config.steps.length === 0) return;
                    
                    const nextBtn = document.getElementById("next-step");
                    const prevBtn = document.getElementById("prev-step");
                    const submitBtn = document.getElementById("submit-form");
                    
                    if (nextBtn) {
                        nextBtn.addEventListener("click", () => this.nextStep());
                    }
                    
                    if (prevBtn) {
                        prevBtn.addEventListener("click", () => this.prevStep());
                    }
                    
                    this.updateStepDisplay();
                }
                
                nextStep() {
                    if (this.validateCurrentStep()) {
                        this.currentStep++;
                        this.updateStepDisplay();
                    }
                }
                
                prevStep() {
                    this.currentStep--;
                    this.updateStepDisplay();
                }
                
                updateStepDisplay() {
                    const steps = document.querySelectorAll(".form-step");
                    const circles = document.querySelectorAll(".step-circle");
                    const nextBtn = document.getElementById("next-step");
                    const prevBtn = document.getElementById("prev-step");
                    const submitBtn = document.getElementById("submit-form");
                    
                    steps.forEach((step, index) => {
                        step.style.display = index === this.currentStep ? "block" : "none";
                    });
                    
                    circles.forEach((circle, index) => {
                        if (index < this.currentStep) {
                            circle.classList.add("bg-green-500", "text-white");
                            circle.classList.remove("border-gray-300");
                        } else if (index === this.currentStep) {
                            circle.classList.add("bg-blue-500", "text-white");
                            circle.classList.remove("border-gray-300");
                        } else {
                            circle.classList.remove("bg-green-500", "bg-blue-500", "text-white");
                            circle.classList.add("border-gray-300");
                        }
                    });
                    
                    if (prevBtn) {
                        prevBtn.style.display = this.currentStep > 0 ? "block" : "none";
                    }
                    
                    if (nextBtn && submitBtn) {
                        const isLastStep = this.currentStep >= this.config.steps.length - 1;
                        nextBtn.style.display = isLastStep ? "none" : "block";
                        submitBtn.style.display = isLastStep ? "block" : "none";
                    }
                }
                
                validateCurrentStep() {
                    const currentStepElement = document.querySelector(`[data-step="${this.currentStep}"]`);
                    if (!currentStepElement) return true;
                    
                    const fields = currentStepElement.querySelectorAll("input[required], select[required], textarea[required]");
                    let isValid = true;
                    
                    fields.forEach(field => {
                        if (!field.value.trim()) {
                            this.showFieldError(field, "This field is required");
                            isValid = false;
                        } else {
                            this.clearFieldError(field);
                        }
                    });
                    
                    return isValid;
                }
                
                setupValidation() {
                    if (!this.config.ajaxValidation) return;
                    
                    const fields = this.formElement.querySelectorAll("input, select, textarea");
                    fields.forEach(field => {
                        field.addEventListener("blur", () => {
                            this.validateField(field);
                        });
                    });
                }
                
                validateField(field) {
                    fetch("/admin/forms/validate", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({
                            field: field.name,
                            value: field.value,
                            form: this.config.formId
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.valid) {
                            this.clearFieldError(field);
                        } else {
                            this.showFieldError(field, result.message);
                        }
                    })
                    .catch(error => {
                        console.error("Validation error:", error);
                    });
                }
                
                setupAutoSave() {
                    if (!this.config.autoSave) return;
                    
                    const fields = this.formElement.querySelectorAll("input, select, textarea");
                    fields.forEach(field => {
                        field.addEventListener("input", this.debounce(() => {
                            this.autoSave();
                        }, 1000));
                    });
                }
                
                autoSave() {
                    const formData = new FormData(this.formElement);
                    
                    fetch("/admin/forms/autosave", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            this.showAutoSaveIndicator();
                        }
                    })
                    .catch(error => {
                        console.error("Auto-save error:", error);
                    });
                }
                
                getFieldValue(field) {
                    if (field.type === "checkbox") {
                        return field.checked ? field.value : "";
                    } else if (field.type === "radio") {
                        const checked = field.form.querySelector(`[name="${field.name}"]:checked`);
                        return checked ? checked.value : "";
                    } else {
                        return field.value;
                    }
                }
                
                setFieldValue(field, value) {
                    if (field.type === "checkbox") {
                        field.checked = Boolean(value);
                    } else if (field.type === "radio") {
                        const radio = field.form.querySelector(`[name="${field.name}"][value="${value}"]`);
                        if (radio) radio.checked = true;
                    } else {
                        field.value = value;
                    }
                }
                
                showFieldError(field, message) {
                    const container = field.closest(".form-field");
                    const errorElement = container.querySelector(".field-error");
                    if (errorElement) {
                        errorElement.textContent = message;
                        errorElement.style.display = "block";
                    }
                    field.classList.add("border-red-500");
                }
                
                clearFieldError(field) {
                    const container = field.closest(".form-field");
                    const errorElement = container.querySelector(".field-error");
                    if (errorElement) {
                        errorElement.style.display = "none";
                    }
                    field.classList.remove("border-red-500");
                }
                
                showAutoSaveIndicator() {
                    // Implementation for auto-save indicator
                    console.log("Auto-saved");
                }
                
                debounce(func, wait) {
                    let timeout;
                    return function executedFunction(...args) {
                        const later = () => {
                            clearTimeout(timeout);
                            func(...args);
                        };
                        clearTimeout(timeout);
                        timeout = setTimeout(later, wait);
                    };
                }
            }
            
            // Initialize the form
            new DynamicForm(config);
        })();
        </script>';
    }

    /**
     * Process form submission
     */
    public function processSubmission(string $formId, array $data): array
    {
        if (!isset($this->forms[$formId])) {
            return ['success' => false, 'message' => 'Form not found'];
        }

        $form = $this->forms[$formId];
        $errors = [];

        // Validate submitted data
        $errors = $this->validateFormData($formId, $data);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Process the form data
        // This would typically save to database, send emails, etc.

        return ['success' => true, 'message' => 'Form submitted successfully'];
    }

    /**
     * Validate form data
     */
    private function validateFormData(string $formId, array $data): array
    {
        $form = $this->forms[$formId];
        $errors = [];

        // Get all fields from all steps
        $allFields = [];
        if (isset($form['steps'])) {
            foreach ($form['steps'] as $step) {
                $allFields = array_merge($allFields, $step['fields'] ?? []);
            }
        } else {
            $allFields = $form['fields'];
        }

        foreach ($allFields as $fieldName => $field) {
            $value = $data[$fieldName] ?? null;

            // Required field validation
            if (($field['required'] ?? false) && empty($value)) {
                $errors[$fieldName] = 'This field is required';
                continue;
            }

            // Type-specific validation
            switch ($field['type'] ?? 'text') {
                case 'email':
                    if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$fieldName] = 'Please enter a valid email address';
                    }
                    break;

                case 'number':
                    if ($value !== null && !is_numeric($value)) {
                        $errors[$fieldName] = 'Please enter a valid number';
                    }
                    if (isset($field['min']) && $value < $field['min']) {
                        $errors[$fieldName] = "Value must be at least {$field['min']}";
                    }
                    if (isset($field['max']) && $value > $field['max']) {
                        $errors[$fieldName] = "Value must be at most {$field['max']}";
                    }
                    break;

                case 'url':
                    if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                        $errors[$fieldName] = 'Please enter a valid URL';
                    }
                    break;
            }

            // Custom validation rules
            if (isset($field['validation'])) {
                foreach ($field['validation'] as $rule => $ruleValue) {
                    switch ($rule) {
                        case 'min_length':
                            if (strlen($value) < $ruleValue) {
                                $errors[$fieldName] = "Must be at least {$ruleValue} characters";
                            }
                            break;
                        case 'max_length':
                            if (strlen($value) > $ruleValue) {
                                $errors[$fieldName] = "Must be at most {$ruleValue} characters";
                            }
                            break;
                        case 'pattern':
                            if (!preg_match($ruleValue, $value)) {
                                $errors[$fieldName] = 'Invalid format';
                            }
                            break;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Get form configuration
     */
    public function getForm(string $formId): ?array
    {
        return $this->forms[$formId] ?? null;
    }

    /**
     * Get all registered forms
     */
    public function getAllForms(): array
    {
        return $this->forms;
    }
}
