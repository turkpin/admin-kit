<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

class ValidationService
{
    private array $rules = [];
    private array $messages = [];
    private array $errors = [];
    private array $customValidators = [];
    private string $currentField = '';

    public function __construct()
    {
        $this->registerDefaultValidators();
        $this->loadDefaultMessages();
    }

    /**
     * Set validation rules
     */
    public function setRules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * Add single rule
     */
    public function addRule(string $field, string $rule): self
    {
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        
        $this->rules[$field][] = $rule;
        return $this;
    }

    /**
     * Set custom error messages
     */
    public function setMessages(array $messages): self
    {
        $this->messages = array_merge($this->messages, $messages);
        return $this;
    }

    /**
     * Validate data against rules
     */
    public function validate(array $data): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $this->currentField = $field;
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                if (!$this->validateRule($field, $value, $rule, $data)) {
                    // Stop on first error for this field
                    break;
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for specific field
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get current field being validated
     */
    protected function getCurrentField(): string
    {
        return $this->currentField;
    }

    /**
     * Validate single rule
     */
    protected function validateRule(string $field, $value, string $rule, array $data): bool
    {
        // Parse rule and parameters
        [$ruleName, $parameters] = $this->parseRule($rule);

        // Check if custom validator exists
        if (isset($this->customValidators[$ruleName])) {
            $result = call_user_func($this->customValidators[$ruleName], $value, $parameters, $data);
            if (!$result) {
                $this->addError($field, $ruleName, $parameters);
            }
            return $result;
        }

        // Built-in validators
        $method = 'validate' . ucfirst($ruleName);
        if (method_exists($this, $method)) {
            $result = $this->$method($value, $parameters, $data);
            if (!$result) {
                $this->addError($field, $ruleName, $parameters);
            }
            return $result;
        }

        throw new \InvalidArgumentException("Validation rule '{$ruleName}' does not exist.");
    }

    /**
     * Parse rule string
     */
    protected function parseRule(string $rule): array
    {
        if (strpos($rule, ':') === false) {
            return [$rule, []];
        }

        [$name, $params] = explode(':', $rule, 2);
        $parameters = explode(',', $params);
        
        return [$name, array_map('trim', $parameters)];
    }

    /**
     * Add validation error
     */
    protected function addError(string $field, string $rule, array $parameters): void
    {
        $message = $this->getMessage($field, $rule, $parameters);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }

    /**
     * Get error message
     */
    protected function getMessage(string $field, string $rule, array $parameters): string
    {
        $key = "{$field}.{$rule}";
        
        // Check for field-specific message
        if (isset($this->messages[$key])) {
            return $this->formatMessage($this->messages[$key], $field, $parameters);
        }
        
        // Check for general rule message
        if (isset($this->messages[$rule])) {
            return $this->formatMessage($this->messages[$rule], $field, $parameters);
        }
        
        // Default message
        return ucfirst($field) . " alanı geçerli değil.";
    }

    /**
     * Format error message with placeholders
     */
    protected function formatMessage(string $message, string $field, array $parameters): string
    {
        $replacements = [
            ':field' => $field,
            ':value' => $parameters[0] ?? '',
            ':min' => $parameters[0] ?? '',
            ':max' => $parameters[0] ?? '',
            ':size' => $parameters[0] ?? '',
            ':other' => $parameters[0] ?? ''
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    // Built-in validation methods

    protected function validateRequired($value): bool
    {
        return !$this->isEmpty($value);
    }

    protected function validateString($value): bool
    {
        return is_string($value);
    }

    protected function validateInteger($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateNumeric($value): bool
    {
        return is_numeric($value);
    }

    protected function validateFloat($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }

    protected function validateBoolean($value): bool
    {
        return is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true);
    }

    protected function validateEmail($value): bool
    {
        if ($this->isEmpty($value)) return true;
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateUrl($value): bool
    {
        if ($this->isEmpty($value)) return true;
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    protected function validateDate($value): bool
    {
        if ($this->isEmpty($value)) return true;
        return strtotime($value) !== false;
    }

    protected function validateMin($value, array $parameters): bool
    {
        if ($this->isEmpty($value)) return true;
        
        $min = (float)$parameters[0];
        
        if (is_numeric($value)) {
            return (float)$value >= $min;
        }
        
        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }
        
        if (is_array($value)) {
            return count($value) >= $min;
        }
        
        return false;
    }

    protected function validateMax($value, array $parameters): bool
    {
        if ($this->isEmpty($value)) return true;
        
        $max = (float)$parameters[0];
        
        if (is_numeric($value)) {
            return (float)$value <= $max;
        }
        
        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }
        
        if (is_array($value)) {
            return count($value) <= $max;
        }
        
        return false;
    }

    protected function validateBetween($value, array $parameters): bool
    {
        if ($this->isEmpty($value)) return true;
        
        $min = (float)$parameters[0];
        $max = (float)$parameters[1];
        
        return $this->validateMin($value, [$min]) && $this->validateMax($value, [$max]);
    }

    protected function validateSize($value, array $parameters): bool
    {
        if ($this->isEmpty($value)) return true;
        
        $size = (float)$parameters[0];
        
        if (is_numeric($value)) {
            return (float)$value == $size;
        }
        
        if (is_string($value)) {
            return mb_strlen($value) == $size;
        }
        
        if (is_array($value)) {
            return count($value) == $size;
        }
        
        return false;
    }

    protected function validateIn($value, array $parameters): bool
    {
        if ($this->isEmpty($value)) return true;
        return in_array($value, $parameters, true);
    }

    protected function validateNotIn($value, array $parameters): bool
    {
        if ($this->isEmpty($value)) return true;
        return !in_array($value, $parameters, true);
    }

    protected function validateRegex($value, array $parameters): bool
    {
        if ($this->isEmpty($value)) return true;
        $pattern = $parameters[0];
        return preg_match($pattern, $value) === 1;
    }

    protected function validateAlpha($value): bool
    {
        if ($this->isEmpty($value)) return true;
        return preg_match('/^[a-zA-ZğüşıöçĞÜŞİÖÇ\s]+$/', $value) === 1;
    }

    protected function validateAlphaNum($value): bool
    {
        if ($this->isEmpty($value)) return true;
        return preg_match('/^[a-zA-Z0-9ğüşıöçĞÜŞİÖÇ\s]+$/', $value) === 1;
    }

    protected function validateAlphaDash($value): bool
    {
        if ($this->isEmpty($value)) return true;
        return preg_match('/^[a-zA-Z0-9ğüşıöçĞÜŞİÖÇ\s_-]+$/', $value) === 1;
    }

    protected function validateUnique($value, array $parameters, array $data): bool
    {
        if ($this->isEmpty($value)) return true;
        
        // This would typically check database for uniqueness
        // For now, just return true
        return true;
    }

    protected function validateExists($value, array $parameters, array $data): bool
    {
        if ($this->isEmpty($value)) return true;
        
        // This would typically check if value exists in database
        // For now, just return true
        return true;
    }

    protected function validateSame($value, array $parameters, array $data): bool
    {
        $otherField = $parameters[0];
        $otherValue = $data[$otherField] ?? null;
        
        return $value === $otherValue;
    }

    protected function validateDifferent($value, array $parameters, array $data): bool
    {
        $otherField = $parameters[0];
        $otherValue = $data[$otherField] ?? null;
        
        return $value !== $otherValue;
    }

    protected function validateConfirmed($value, array $parameters, array $data): bool
    {
        $confirmationField = $parameters[0] ?? ($this->getCurrentField() . '_confirmation');
        $confirmationValue = $data[$confirmationField] ?? null;
        
        return $value === $confirmationValue;
    }

    protected function validateImage($value): bool
    {
        if ($this->isEmpty($value)) return true;
        
        if (is_array($value) && isset($value['tmp_name'])) {
            // File upload array
            $imageInfo = getimagesize($value['tmp_name']);
            return $imageInfo !== false;
        }
        
        if (is_string($value) && file_exists($value)) {
            $imageInfo = getimagesize($value);
            return $imageInfo !== false;
        }
        
        return false;
    }

    protected function validateMimes($value, array $parameters): bool
    {
        if ($this->isEmpty($value)) return true;
        
        if (is_array($value) && isset($value['name'])) {
            $extension = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));
            return in_array($extension, $parameters);
        }
        
        return false;
    }

    protected function validateFile($value): bool
    {
        if ($this->isEmpty($value)) return true;
        
        return is_array($value) && 
               isset($value['tmp_name']) && 
               isset($value['error']) && 
               $value['error'] === UPLOAD_ERR_OK;
    }

    protected function validateJson($value): bool
    {
        if ($this->isEmpty($value)) return true;
        
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Check if value is empty
     */
    protected function isEmpty($value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * Register custom validator
     */
    public function registerValidator(string $name, callable $callback): self
    {
        $this->customValidators[$name] = $callback;
        return $this;
    }

    /**
     * Register default validators
     */
    protected function registerDefaultValidators(): void
    {
        // Turkish ID number validator
        $this->registerValidator('tc_kimlik', function ($value) {
            if (strlen($value) !== 11 || !ctype_digit($value)) {
                return false;
            }
            
            $digits = str_split($value);
            $sum1 = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
            $sum2 = $digits[1] + $digits[3] + $digits[5] + $digits[7];
            
            $check1 = (($sum1 * 7) - $sum2) % 10;
            $check2 = ($sum1 + $sum2 + $digits[9]) % 10;
            
            return $check1 == $digits[9] && $check2 == $digits[10];
        });

        // Turkish phone number validator
        $this->registerValidator('turkish_phone', function ($value) {
            $pattern = '/^(\+90|0)?[0-9]{10}$/';
            return preg_match($pattern, $value) === 1;
        });

        // Strong password validator
        $this->registerValidator('strong_password', function ($value) {
            // At least 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char
            $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
            return preg_match($pattern, $value) === 1;
        });
    }

    /**
     * Load default error messages
     */
    protected function loadDefaultMessages(): void
    {
        $this->messages = [
            'required' => ':field alanı gereklidir.',
            'string' => ':field alanı metin olmalıdır.',
            'integer' => ':field alanı tam sayı olmalıdır.',
            'numeric' => ':field alanı sayısal olmalıdır.',
            'float' => ':field alanı ondalık sayı olmalıdır.',
            'boolean' => ':field alanı doğru/yanlış değeri olmalıdır.',
            'email' => ':field alanı geçerli bir e-posta adresi olmalıdır.',
            'url' => ':field alanı geçerli bir URL olmalıdır.',
            'date' => ':field alanı geçerli bir tarih olmalıdır.',
            'min' => ':field alanı en az :min karakter/değer olmalıdır.',
            'max' => ':field alanı en fazla :max karakter/değer olmalıdır.',
            'between' => ':field alanı :min ile :max arasında olmalıdır.',
            'size' => ':field alanı :size karakter/değer olmalıdır.',
            'in' => ':field alanı geçerli değerlerden biri olmalıdır.',
            'not_in' => ':field alanı geçersiz bir değer içeriyor.',
            'regex' => ':field alanı geçerli formatta olmalıdır.',
            'alpha' => ':field alanı sadece harf içermelidir.',
            'alpha_num' => ':field alanı sadece harf ve rakam içermelidir.',
            'alpha_dash' => ':field alanı sadece harf, rakam, tire ve alt çizgi içermelidir.',
            'unique' => ':field alanı zaten kullanımda.',
            'exists' => ':field alanı geçerli değil.',
            'same' => ':field alanı :other alanı ile aynı olmalıdır.',
            'different' => ':field alanı :other alanından farklı olmalıdır.',
            'confirmed' => ':field alanı onaylanmalıdır.',
            'image' => ':field alanı geçerli bir resim olmalıdır.',
            'mimes' => ':field alanı geçerli dosya türünde olmalıdır.',
            'file' => ':field alanı geçerli bir dosya olmalıdır.',
            'json' => ':field alanı geçerli JSON formatında olmalıdır.',
            'tc_kimlik' => ':field alanı geçerli bir TC kimlik numarası olmalıdır.',
            'turkish_phone' => ':field alanı geçerli bir Türkiye telefon numarası olmalıdır.',
            'strong_password' => ':field alanı en az 8 karakter, 1 büyük harf, 1 küçük harf, 1 rakam ve 1 özel karakter içermelidir.'
        ];
    }

    /**
     * Static validation helper
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        $validator = new self();
        $validator->setRules($rules);
        $validator->setMessages($messages);
        $validator->validate($data);
        
        return $validator;
    }

    /**
     * Quick validation
     */
    public static function quick(array $data, array $rules): bool
    {
        return self::make($data, $rules)->passes();
    }
}
