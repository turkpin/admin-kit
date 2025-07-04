<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

class EmailField extends AbstractFieldType
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
        'multiple',
        'domain_validation'
    ];

    public function getTypeName(): string
    {
        return 'email';
    }

    public function getDefaultOptions(): array
    {
        return array_merge(parent::getDefaultOptions(), [
            'placeholder' => 'ornek@domain.com',
            'multiple' => false,
            'domain_validation' => []
        ]);
    }

    public function renderDisplay($value, array $options = []): string
    {
        if ($this->isEmpty($value)) {
            return '<span class="text-gray-400">-</span>';
        }

        $options = array_merge($this->getDefaultOptions(), $options);
        
        if ($options['multiple']) {
            $emails = is_array($value) ? $value : explode(',', $value);
            $html = '<div class="space-y-1">';
            
            foreach ($emails as $email) {
                $email = trim($email);
                if (!empty($email)) {
                    $html .= '<div>';
                    $html .= '<a href="mailto:' . $this->escapeValue($email) . '" ';
                    $html .= 'class="text-indigo-600 hover:text-indigo-500 text-sm">';
                    $html .= $this->escapeValue($email);
                    $html .= '</a>';
                    $html .= '</div>';
                }
            }
            
            $html .= '</div>';
            return $html;
        } else {
            return '<a href="mailto:' . $this->escapeValue($value) . '" '
                   . 'class="text-indigo-600 hover:text-indigo-500">'
                   . $this->escapeValue($value) . '</a>';
        }
    }

    public function renderFormInput(string $name, $value, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        $attributes = $this->renderAttributes($options);

        if ($options['multiple']) {
            $attributes .= ' multiple';
            $displayValue = is_array($value) ? implode(', ', $value) : $value;
        } else {
            $displayValue = $value;
        }

        $html = $this->renderLabel($name, $options);
        $html .= $this->renderHelpText($options);
        $html .= '<input type="email" name="' . $this->escapeValue($name) . '" ';
        $html .= 'id="' . $this->escapeValue($name) . '" ';
        $html .= 'value="' . $this->escapeValue($displayValue ?? $options['default_value'] ?? '') . '" ';
        $html .= $attributes . '>';

        // Add validation hint
        if ($options['multiple']) {
            $html .= '<p class="text-xs text-gray-500 mt-1">';
            $html .= 'Birden fazla e-posta adresi için virgül ile ayırın';
            $html .= '</p>';
        }

        return $html;
    }

    public function validate($value, array $options = []): array
    {
        $errors = parent::validate($value, $options);
        $options = array_merge($this->getDefaultOptions(), $options);

        if (!$this->isEmpty($value)) {
            if ($options['multiple']) {
                $emails = is_array($value) ? $value : explode(',', $value);
                
                foreach ($emails as $email) {
                    $email = trim($email);
                    if (!empty($email)) {
                        $emailErrors = $this->validateSingleEmail($email, $options);
                        $errors = array_merge($errors, $emailErrors);
                    }
                }
            } else {
                $emailErrors = $this->validateSingleEmail($value, $options);
                $errors = array_merge($errors, $emailErrors);
            }
        }

        return $errors;
    }

    protected function validateSingleEmail(string $email, array $options): array
    {
        $errors = [];
        $label = $options['label'] ?? 'E-posta';

        // Basic email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = $label . ' geçerli bir e-posta adresi olmalıdır: ' . $email;
            return $errors; // Don't continue with other validations if basic format is wrong
        }

        // Domain validation
        if (!empty($options['domain_validation'])) {
            $domain = substr(strrchr($email, '@'), 1);
            $allowedDomains = $options['domain_validation'];
            
            if (!in_array($domain, $allowedDomains)) {
                $errors[] = $label . ' sadece şu domainlerden olabilir: ' . implode(', ', $allowedDomains);
            }
        }

        // Check if domain exists (DNS validation)
        if ($options['check_mx_record'] ?? false) {
            $domain = substr(strrchr($email, '@'), 1);
            if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
                $errors[] = $label . ' domaine ulaşılamıyor: ' . $domain;
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

        if ($options['multiple']) {
            // Handle multiple emails
            $emails = is_array($value) ? $value : explode(',', $value);
            $cleanEmails = [];
            
            foreach ($emails as $email) {
                $email = trim(strtolower($email));
                if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $cleanEmails[] = $email;
                }
            }
            
            return array_unique($cleanEmails);
        } else {
            // Single email
            return trim(strtolower($value));
        }
    }

    /**
     * Get email gravatar URL
     */
    public function getGravatarUrl(string $email, int $size = 80): string
    {
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=identicon";
    }

    /**
     * Mask email for privacy (e.g., for GDPR compliance)
     */
    public function maskEmail(string $email): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        $parts = explode('@', $email);
        $username = $parts[0];
        $domain = $parts[1];

        // Mask username but keep first and last characters
        if (strlen($username) <= 2) {
            $maskedUsername = str_repeat('*', strlen($username));
        } else {
            $maskedUsername = $username[0] . str_repeat('*', strlen($username) - 2) . substr($username, -1);
        }

        return $maskedUsername . '@' . $domain;
    }
}
