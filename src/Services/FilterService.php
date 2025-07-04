<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Turkpin\AdminKit\Services\CacheService;

class FilterService
{
    private CacheService $cacheService;
    private array $config;
    private array $operators;
    private array $fieldTypes;

    public function __construct(CacheService $cacheService, array $config = [])
    {
        $this->cacheService = $cacheService;
        $this->config = array_merge([
            'max_saved_filters' => 50,
            'cache_ttl' => 3600,
            'enable_sql_preview' => true,
            'enable_saved_filters' => true
        ], $config);
        
        $this->registerOperators();
        $this->registerFieldTypes();
    }

    /**
     * Register filter operators
     */
    private function registerOperators(): void
    {
        $this->operators = [
            'equals' => [
                'label' => 'Equals',
                'symbol' => '=',
                'types' => ['string', 'number', 'date', 'boolean'],
                'inputs' => 1
            ],
            'not_equals' => [
                'label' => 'Not Equals',
                'symbol' => '!=',
                'types' => ['string', 'number', 'date', 'boolean'],
                'inputs' => 1
            ],
            'contains' => [
                'label' => 'Contains',
                'symbol' => 'LIKE',
                'types' => ['string'],
                'inputs' => 1
            ],
            'not_contains' => [
                'label' => 'Does Not Contain',
                'symbol' => 'NOT LIKE',
                'types' => ['string'],
                'inputs' => 1
            ],
            'starts_with' => [
                'label' => 'Starts With',
                'symbol' => 'LIKE',
                'types' => ['string'],
                'inputs' => 1
            ],
            'ends_with' => [
                'label' => 'Ends With',
                'symbol' => 'LIKE',
                'types' => ['string'],
                'inputs' => 1
            ],
            'greater_than' => [
                'label' => 'Greater Than',
                'symbol' => '>',
                'types' => ['number', 'date'],
                'inputs' => 1
            ],
            'greater_equal' => [
                'label' => 'Greater Than or Equal',
                'symbol' => '>=',
                'types' => ['number', 'date'],
                'inputs' => 1
            ],
            'less_than' => [
                'label' => 'Less Than',
                'symbol' => '<',
                'types' => ['number', 'date'],
                'inputs' => 1
            ],
            'less_equal' => [
                'label' => 'Less Than or Equal',
                'symbol' => '<=',
                'types' => ['number', 'date'],
                'inputs' => 1
            ],
            'between' => [
                'label' => 'Between',
                'symbol' => 'BETWEEN',
                'types' => ['number', 'date'],
                'inputs' => 2
            ],
            'in' => [
                'label' => 'In List',
                'symbol' => 'IN',
                'types' => ['string', 'number'],
                'inputs' => 'multiple'
            ],
            'not_in' => [
                'label' => 'Not In List',
                'symbol' => 'NOT IN',
                'types' => ['string', 'number'],
                'inputs' => 'multiple'
            ],
            'is_null' => [
                'label' => 'Is Empty',
                'symbol' => 'IS NULL',
                'types' => ['string', 'number', 'date'],
                'inputs' => 0
            ],
            'is_not_null' => [
                'label' => 'Is Not Empty',
                'symbol' => 'IS NOT NULL',
                'types' => ['string', 'number', 'date'],
                'inputs' => 0
            ],
            'regex' => [
                'label' => 'Matches Pattern',
                'symbol' => 'REGEXP',
                'types' => ['string'],
                'inputs' => 1
            ]
        ];
    }

    /**
     * Register field types
     */
    private function registerFieldTypes(): void
    {
        $this->fieldTypes = [
            'string' => [
                'label' => 'Text',
                'operators' => ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'in', 'not_in', 'is_null', 'is_not_null', 'regex'],
                'input_type' => 'text'
            ],
            'number' => [
                'label' => 'Number',
                'operators' => ['equals', 'not_equals', 'greater_than', 'greater_equal', 'less_than', 'less_equal', 'between', 'in', 'not_in', 'is_null', 'is_not_null'],
                'input_type' => 'number'
            ],
            'date' => [
                'label' => 'Date',
                'operators' => ['equals', 'not_equals', 'greater_than', 'greater_equal', 'less_than', 'less_equal', 'between', 'is_null', 'is_not_null'],
                'input_type' => 'date'
            ],
            'datetime' => [
                'label' => 'Date & Time',
                'operators' => ['equals', 'not_equals', 'greater_than', 'greater_equal', 'less_than', 'less_equal', 'between', 'is_null', 'is_not_null'],
                'input_type' => 'datetime-local'
            ],
            'boolean' => [
                'label' => 'True/False',
                'operators' => ['equals', 'not_equals'],
                'input_type' => 'select',
                'options' => ['true' => 'True', 'false' => 'False']
            ],
            'select' => [
                'label' => 'Select',
                'operators' => ['equals', 'not_equals', 'in', 'not_in', 'is_null', 'is_not_null'],
                'input_type' => 'select'
            ]
        ];
    }

    /**
     * Build SQL query from filter conditions
     */
    public function buildQuery(array $filters, string $logic = 'AND'): array
    {
        $conditions = [];
        $params = [];
        $paramIndex = 0;

        foreach ($filters as $filter) {
            if (isset($filter['field']) && isset($filter['operator'])) {
                $condition = $this->buildCondition($filter, $params, $paramIndex);
                if ($condition) {
                    $conditions[] = $condition;
                }
            }
        }

        $sql = '';
        if (!empty($conditions)) {
            $sql = '(' . implode(" {$logic} ", $conditions) . ')';
        }

        return [
            'sql' => $sql,
            'params' => $params
        ];
    }

    /**
     * Build individual condition
     */
    private function buildCondition(array $filter, array &$params, int &$paramIndex): ?string
    {
        $field = $filter['field'];
        $operator = $filter['operator'];
        $value = $filter['value'] ?? null;

        if (!isset($this->operators[$operator])) {
            return null;
        }

        $operatorConfig = $this->operators[$operator];
        $symbol = $operatorConfig['symbol'];

        switch ($operator) {
            case 'equals':
            case 'not_equals':
            case 'greater_than':
            case 'greater_equal':
            case 'less_than':
            case 'less_equal':
                $paramName = ":param{$paramIndex}";
                $params[$paramName] = $value;
                $paramIndex++;
                return "{$field} {$symbol} {$paramName}";

            case 'contains':
                $paramName = ":param{$paramIndex}";
                $params[$paramName] = "%{$value}%";
                $paramIndex++;
                return "{$field} {$symbol} {$paramName}";

            case 'not_contains':
                $paramName = ":param{$paramIndex}";
                $params[$paramName] = "%{$value}%";
                $paramIndex++;
                return "{$field} {$symbol} {$paramName}";

            case 'starts_with':
                $paramName = ":param{$paramIndex}";
                $params[$paramName] = "{$value}%";
                $paramIndex++;
                return "{$field} LIKE {$paramName}";

            case 'ends_with':
                $paramName = ":param{$paramIndex}";
                $params[$paramName] = "%{$value}";
                $paramIndex++;
                return "{$field} LIKE {$paramName}";

            case 'between':
                $values = is_array($value) ? $value : explode(',', $value);
                if (count($values) === 2) {
                    $param1 = ":param{$paramIndex}";
                    $param2 = ":param" . ($paramIndex + 1);
                    $params[$param1] = trim($values[0]);
                    $params[$param2] = trim($values[1]);
                    $paramIndex += 2;
                    return "{$field} {$symbol} {$param1} AND {$param2}";
                }
                break;

            case 'in':
            case 'not_in':
                $values = is_array($value) ? $value : explode(',', $value);
                $placeholders = [];
                foreach ($values as $val) {
                    $paramName = ":param{$paramIndex}";
                    $params[$paramName] = trim($val);
                    $placeholders[] = $paramName;
                    $paramIndex++;
                }
                if (!empty($placeholders)) {
                    return "{$field} {$symbol} (" . implode(', ', $placeholders) . ")";
                }
                break;

            case 'is_null':
            case 'is_not_null':
                return "{$field} {$symbol}";

            case 'regex':
                $paramName = ":param{$paramIndex}";
                $params[$paramName] = $value;
                $paramIndex++;
                return "{$field} {$symbol} {$paramName}";
        }

        return null;
    }

    /**
     * Save filter for user
     */
    public function saveFilter(int $userId, string $name, array $filters, string $entity): bool
    {
        if (!$this->config['enable_saved_filters']) {
            return false;
        }

        $filterId = uniqid('filter_');
        $savedFilter = [
            'id' => $filterId,
            'user_id' => $userId,
            'name' => $name,
            'entity' => $entity,
            'filters' => $filters,
            'created_at' => time(),
            'used_count' => 0,
            'last_used' => null
        ];

        // Store filter
        $this->cacheService->set("filter:{$filterId}", $savedFilter, 86400 * 365);

        // Add to user's filter list
        $userFiltersKey = "user_filters:{$userId}";
        $userFilters = $this->cacheService->get($userFiltersKey, fn() => []);
        $userFilters[] = $filterId;

        // Keep only recent filters
        if (count($userFilters) > $this->config['max_saved_filters']) {
            $userFilters = array_slice($userFilters, -$this->config['max_saved_filters']);
        }

        $this->cacheService->set($userFiltersKey, $userFilters, 86400 * 365);

        return true;
    }

    /**
     * Get saved filters for user
     */
    public function getSavedFilters(int $userId, string $entity = null): array
    {
        if (!$this->config['enable_saved_filters']) {
            return [];
        }

        $userFiltersKey = "user_filters:{$userId}";
        $filterIds = $this->cacheService->get($userFiltersKey, fn() => []);

        $filters = [];
        foreach ($filterIds as $filterId) {
            $filter = $this->cacheService->get("filter:{$filterId}");
            if ($filter && ($entity === null || $filter['entity'] === $entity)) {
                $filters[] = $filter;
            }
        }

        // Sort by last used, then by created date
        usort($filters, function($a, $b) {
            if ($a['last_used'] && $b['last_used']) {
                return $b['last_used'] - $a['last_used'];
            }
            if ($a['last_used']) return -1;
            if ($b['last_used']) return 1;
            return $b['created_at'] - $a['created_at'];
        });

        return $filters;
    }

    /**
     * Load saved filter
     */
    public function loadFilter(string $filterId, int $userId): ?array
    {
        $filter = $this->cacheService->get("filter:{$filterId}");
        
        if (!$filter || $filter['user_id'] !== $userId) {
            return null;
        }

        // Update usage stats
        $filter['used_count']++;
        $filter['last_used'] = time();
        $this->cacheService->set("filter:{$filterId}", $filter, 86400 * 365);

        return $filter;
    }

    /**
     * Delete saved filter
     */
    public function deleteFilter(string $filterId, int $userId): bool
    {
        $filter = $this->cacheService->get("filter:{$filterId}");
        
        if (!$filter || $filter['user_id'] !== $userId) {
            return false;
        }

        // Remove filter
        $this->cacheService->delete("filter:{$filterId}");

        // Remove from user's list
        $userFiltersKey = "user_filters:{$userId}";
        $userFilters = $this->cacheService->get($userFiltersKey, fn() => []);
        $userFilters = array_filter($userFilters, fn($id) => $id !== $filterId);
        $this->cacheService->set($userFiltersKey, $userFilters, 86400 * 365);

        return true;
    }

    /**
     * Get available fields for entity
     */
    public function getEntityFields(string $entity): array
    {
        // In real implementation, this would introspect entity metadata
        return [
            'id' => ['label' => 'ID', 'type' => 'number'],
            'name' => ['label' => 'Name', 'type' => 'string'],
            'email' => ['label' => 'Email', 'type' => 'string'],
            'created_at' => ['label' => 'Created At', 'type' => 'datetime'],
            'updated_at' => ['label' => 'Updated At', 'type' => 'datetime'],
            'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
            'is_verified' => ['label' => 'Verified', 'type' => 'boolean']
        ];
    }

    /**
     * Render advanced filter builder UI
     */
    public function renderFilterBuilder(string $entity, int $userId, array $currentFilters = []): string
    {
        $fields = $this->getEntityFields($entity);
        $savedFilters = $this->getSavedFilters($userId, $entity);
        
        return '
        <div class="filter-builder bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-medium text-gray-900">Advanced Filters</h3>
                <div class="flex space-x-2">
                    <button onclick="addFilterCondition()" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                        Add Condition
                    </button>
                    <button onclick="clearAllFilters()" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">
                        Clear All
                    </button>
                </div>
            </div>
            
            <!-- Saved Filters -->
            ' . ($this->config['enable_saved_filters'] && !empty($savedFilters) ? '
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Saved Filters</label>
                <div class="flex flex-wrap gap-2">
                    ' . implode('', array_map(function($filter) {
                        return '<button onclick="loadSavedFilter(\'' . $filter['id'] . '\')" 
                                       class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200">
                                    ' . htmlspecialchars($filter['name']) . '
                                    <button onclick="deleteSavedFilter(\'' . $filter['id'] . '\')" class="ml-1 text-blue-600 hover:text-blue-800">×</button>
                                </button>';
                    }, $savedFilters)) . '
                </div>
            </div>' : '') . '
            
            <!-- Filter Conditions -->
            <div id="filter-conditions" class="space-y-3">
                <!-- Conditions will be added here -->
            </div>
            
            <!-- Logic Operator -->
            <div class="mt-4 flex items-center space-x-4">
                <label class="text-sm font-medium text-gray-700">Logic:</label>
                <select id="filter-logic" class="border-gray-300 rounded text-sm">
                    <option value="AND">AND (all conditions must match)</option>
                    <option value="OR">OR (any condition can match)</option>
                </select>
            </div>
            
            <!-- Actions -->
            <div class="mt-6 flex items-center justify-between">
                <div class="flex space-x-2">
                    <button onclick="applyFilters()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Apply Filters
                    </button>
                    ' . ($this->config['enable_sql_preview'] ? '
                    <button onclick="previewSQL()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                        Preview SQL
                    </button>' : '') . '
                </div>
                
                ' . ($this->config['enable_saved_filters'] ? '
                <div class="flex items-center space-x-2">
                    <input type="text" id="filter-name" placeholder="Filter name" class="border-gray-300 rounded text-sm">
                    <button onclick="saveCurrentFilter()" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                        Save Filter
                    </button>
                </div>' : '') . '
            </div>
        </div>
        
        <!-- SQL Preview Modal -->
        <div id="sql-preview-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">SQL Preview</h3>
                    <button onclick="closeSQLPreview()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <pre id="sql-content" class="text-sm font-mono overflow-x-auto"></pre>
                </div>
                <div class="mt-4 text-sm text-gray-600">
                    <strong>Parameters:</strong>
                    <div id="sql-params" class="mt-2 bg-gray-50 p-2 rounded"></div>
                </div>
            </div>
        </div>
        
        <script>
        let filterIndex = 0;
        const fields = ' . json_encode($fields) . ';
        const operators = ' . json_encode($this->operators) . ';
        const fieldTypes = ' . json_encode($this->fieldTypes) . ';
        
        function addFilterCondition(field = "", operator = "", value = "") {
            const container = document.getElementById("filter-conditions");
            const conditionId = "condition-" + filterIndex++;
            
            const condition = document.createElement("div");
            condition.className = "flex items-center space-x-2 p-3 bg-gray-50 rounded-lg";
            condition.id = conditionId;
            
            condition.innerHTML = `
                <select onchange="updateOperators(this, \'${conditionId}\')" class="border-gray-300 rounded text-sm">
                    <option value="">Select Field</option>
                    ${Object.entries(fields).map(([key, field]) => 
                        `<option value="${key}" ${key === field ? "selected" : ""}>${field.label}</option>`
                    ).join("")}
                </select>
                
                <select id="${conditionId}-operator" class="border-gray-300 rounded text-sm">
                    <option value="">Select Operator</option>
                </select>
                
                <div id="${conditionId}-value" class="flex-1">
                    <input type="text" placeholder="Value" class="border-gray-300 rounded text-sm w-full">
                </div>
                
                <button onclick="removeCondition(\'${conditionId}\')" class="text-red-600 hover:text-red-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            `;
            
            container.appendChild(condition);
            
            if (field) {
                condition.querySelector("select").value = field;
                updateOperators(condition.querySelector("select"), conditionId);
                if (operator) {
                    document.getElementById(conditionId + "-operator").value = operator;
                    updateValueInput(document.getElementById(conditionId + "-operator"), conditionId);
                    if (value) {
                        const valueInput = document.querySelector(`#${conditionId}-value input, #${conditionId}-value select`);
                        if (valueInput) valueInput.value = value;
                    }
                }
            }
        }
        
        function updateOperators(fieldSelect, conditionId) {
            const fieldName = fieldSelect.value;
            const field = fields[fieldName];
            const operatorSelect = document.getElementById(conditionId + "-operator");
            
            operatorSelect.innerHTML = "<option value=\"\">Select Operator</option>";
            
            if (field && fieldTypes[field.type]) {
                const availableOps = fieldTypes[field.type].operators;
                availableOps.forEach(opKey => {
                    const op = operators[opKey];
                    if (op) {
                        operatorSelect.innerHTML += `<option value="${opKey}">${op.label}</option>`;
                    }
                });
            }
            
            operatorSelect.onchange = () => updateValueInput(operatorSelect, conditionId);
        }
        
        function updateValueInput(operatorSelect, conditionId) {
            const operator = operators[operatorSelect.value];
            const valueContainer = document.getElementById(conditionId + "-value");
            const fieldName = document.querySelector(`#${conditionId} select`).value;
            const field = fields[fieldName];
            
            if (!operator) {
                valueContainer.innerHTML = "<input type=\"text\" placeholder=\"Value\" class=\"border-gray-300 rounded text-sm w-full\">";
                return;
            }
            
            if (operator.inputs === 0) {
                valueContainer.innerHTML = "<span class=\"text-sm text-gray-500 italic\">No value needed</span>";
            } else if (operator.inputs === 2) {
                valueContainer.innerHTML = `
                    <div class="flex space-x-2">
                        <input type="${fieldTypes[field.type].input_type}" placeholder="From" class="border-gray-300 rounded text-sm flex-1">
                        <input type="${fieldTypes[field.type].input_type}" placeholder="To" class="border-gray-300 rounded text-sm flex-1">
                    </div>
                `;
            } else if (operator.inputs === "multiple") {
                valueContainer.innerHTML = "<input type=\"text\" placeholder=\"Comma-separated values\" class=\"border-gray-300 rounded text-sm w-full\">";
            } else if (field.type === "boolean") {
                valueContainer.innerHTML = `
                    <select class="border-gray-300 rounded text-sm w-full">
                        <option value="">Select Value</option>
                        <option value="true">True</option>
                        <option value="false">False</option>
                    </select>
                `;
            } else if (field.type === "select" && field.options) {
                let options = "<option value=\"\">Select Value</option>";
                Object.entries(field.options).forEach(([value, label]) => {
                    options += `<option value="${value}">${label}</option>`;
                });
                valueContainer.innerHTML = `<select class="border-gray-300 rounded text-sm w-full">${options}</select>`;
            } else {
                const inputType = fieldTypes[field.type]?.input_type || "text";
                valueContainer.innerHTML = `<input type="${inputType}" placeholder="Value" class="border-gray-300 rounded text-sm w-full">`;
            }
        }
        
        function removeCondition(conditionId) {
            document.getElementById(conditionId).remove();
        }
        
        function clearAllFilters() {
            document.getElementById("filter-conditions").innerHTML = "";
        }
        
        function getFilterData() {
            const conditions = [];
            const conditionElements = document.querySelectorAll("#filter-conditions > div");
            
            conditionElements.forEach(element => {
                const field = element.querySelector("select").value;
                const operator = element.querySelector("select:nth-child(2)").value;
                const valueInputs = element.querySelectorAll("#" + element.id + "-value input, #" + element.id + "-value select");
                
                if (field && operator) {
                    let value = null;
                    if (valueInputs.length === 1) {
                        value = valueInputs[0].value;
                    } else if (valueInputs.length === 2) {
                        value = [valueInputs[0].value, valueInputs[1].value];
                    }
                    
                    conditions.push({ field, operator, value });
                }
            });
            
            return {
                conditions,
                logic: document.getElementById("filter-logic").value
            };
        }
        
        function applyFilters() {
            const filterData = getFilterData();
            
            if (filterData.conditions.length === 0) {
                alert("Please add at least one filter condition");
                return;
            }
            
            // Submit filters (implement based on your form handling)
            const form = document.createElement("form");
            form.method = "GET";
            form.innerHTML = `
                <input type="hidden" name="filters" value="${encodeURIComponent(JSON.stringify(filterData))}">
                <input type="hidden" name="apply_filters" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function previewSQL() {
            const filterData = getFilterData();
            
            if (filterData.conditions.length === 0) {
                alert("Please add at least one filter condition");
                return;
            }
            
            fetch("/admin/filters/preview", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    entity: "' . $entity . '",
                    filters: filterData.conditions,
                    logic: filterData.logic
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById("sql-content").textContent = data.sql || "No SQL generated";
                document.getElementById("sql-params").textContent = JSON.stringify(data.params || {}, null, 2);
                document.getElementById("sql-preview-modal").classList.remove("hidden");
            })
            .catch(error => alert("Error: " + error.message));
        }
        
        function closeSQLPreview() {
            document.getElementById("sql-preview-modal").classList.add("hidden");
        }
        
        function saveCurrentFilter() {
            const name = document.getElementById("filter-name").value.trim();
            if (!name) {
                alert("Please enter a filter name");
                return;
            }
            
            const filterData = getFilterData();
            if (filterData.conditions.length === 0) {
                alert("Please add at least one filter condition");
                return;
            }
            
            fetch("/admin/filters/save", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    name,
                    entity: "' . $entity . '",
                    filters: filterData.conditions,
                    logic: filterData.logic
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Filter saved successfully");
                    location.reload();
                } else {
                    alert("Failed to save filter: " + (data.message || "Unknown error"));
                }
            })
            .catch(error => alert("Error: " + error.message));
        }
        
        function loadSavedFilter(filterId) {
            fetch(`/admin/filters/load/${filterId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.filter) {
                        clearAllFilters();
                        data.filter.filters.forEach(filter => {
                            addFilterCondition(filter.field, filter.operator, filter.value);
                        });
                        if (data.filter.logic) {
                            document.getElementById("filter-logic").value = data.filter.logic;
                        }
                    } else {
                        alert("Failed to load filter");
                    }
                })
                .catch(error => alert("Error: " + error.message));
        }
        
        function deleteSavedFilter(filterId) {
            if (confirm("Delete this saved filter?")) {
                fetch(`/admin/filters/delete/${filterId}`, { method: "DELETE" })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert("Failed to delete filter");
                        }
                    })
                    .catch(error => alert("Error: " + error.message));
            }
        }
        
        // Initialize with current filters
        ' . (empty($currentFilters) ? '' : 'document.addEventListener("DOMContentLoaded", () => {
            ' . implode("\n", array_map(function($filter) {
                return 'addFilterCondition("' . ($filter['field'] ?? '') . '", "' . ($filter['operator'] ?? '') . '", "' . ($filter['value'] ?? '') . '");';
            }, $currentFilters)) . '
        });') . '
        </script>';
    }
}
