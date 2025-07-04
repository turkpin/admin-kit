<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

class PasswordField extends AbstractFieldType
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
        'minlength',
        'maxlength',
        'require_confirmation',
        'confirmation_field',
        'show_strength_meter',
        'strength_requirements',
        'show_toggle',
        'generate_button',
        'hash_algorithm'
    ];

    public function getTypeName(): string
    {
        return 'password';
    }

    public function getDefaultOptions(): array
    {
        return array_merge(parent::getDefaultOptions(), [
            'minlength' => 8,
            'maxlength' => 128,
            'require_confirmation' => true,
            'confirmation_field' => null, // auto-generated if null
            'show_strength_meter' => true,
            'strength_requirements' => [
                'min_length' => 8,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_symbols' => true
            ],
            'show_toggle' => true,
            'generate_button' => true,
            'hash_algorithm' => 'bcrypt'
        ]);
    }

    public function renderDisplay($value, array $options = []): string
    {
        // Never display actual password values
        if ($this->isEmpty($value)) {
            return '<span class="text-gray-400">Şifre belirlenmemiş</span>';
        }

        return '<span class="text-gray-600">••••••••</span>';
    }

    public function renderFormInput(string $name, $value, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        $passwordId = $this->generateId($name);
        
        $html = $this->renderLabel($name, $options);
        $html .= $this->renderHelpText($options);
        
        // Main password field
        $html .= '<div class="password-field-container">';
        $html .= $this->renderPasswordInput($name, $passwordId, $options);
        
        // Password confirmation field
        if ($options['require_confirmation']) {
            $confirmationField = $options['confirmation_field'] ?? ($name . '_confirmation');
            $confirmationId = $this->generateId($confirmationField);
            $html .= $this->renderConfirmationField($confirmationField, $confirmationId, $options);
        }
        
        // Password strength meter
        if ($options['show_strength_meter']) {
            $html .= $this->renderStrengthMeter($passwordId, $options);
        }
        
        // Password requirements
        $html .= $this->renderRequirements($passwordId, $options);
        
        $html .= '</div>';
        
        // Add JavaScript functionality
        $html .= $this->renderJavaScript($passwordId, $options);
        
        return $html;
    }

    protected function renderPasswordInput(string $name, string $passwordId, array $options): string
    {
        $attributes = $this->renderAttributes($options);
        
        if ($options['minlength']) {
            $attributes .= ' minlength="' . (int)$options['minlength'] . '"';
        }
        
        if ($options['maxlength']) {
            $attributes .= ' maxlength="' . (int)$options['maxlength'] . '"';
        }
        
        $html = '<div class="relative">';
        $html .= '<input type="password" name="' . $this->escapeValue($name) . '" ';
        $html .= 'id="' . $passwordId . '" ';
        $html .= 'autocomplete="new-password" ';
        $html .= $attributes . '>';
        
        // Toggle visibility button
        if ($options['show_toggle']) {
            $html .= '<button type="button" onclick="togglePasswordVisibility(\'' . $passwordId . '\')" ';
            $html .= 'class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">';
            $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
            $html .= '</svg>';
            $html .= '</button>';
        }
        
        $html .= '</div>';
        
        // Generate password button
        if ($options['generate_button']) {
            $html .= '<div class="mt-2">';
            $html .= '<button type="button" onclick="generatePassword(\'' . $passwordId . '\')" ';
            $html .= 'class="text-sm text-indigo-600 hover:text-indigo-500">Güçlü şifre oluştur</button>';
            $html .= '</div>';
        }
        
        return $html;
    }

    protected function renderConfirmationField(string $confirmationField, string $confirmationId, array $options): string
    {
        $html = '<div class="mt-4">';
        $html .= '<label for="' . $confirmationId . '" class="form-label">Şifre Tekrarı';
        if ($options['required']) {
            $html .= ' <span class="text-red-500">*</span>';
        }
        $html .= '</label>';
        
        $html .= '<div class="relative">';
        $html .= '<input type="password" name="' . $this->escapeValue($confirmationField) . '" ';
        $html .= 'id="' . $confirmationId . '" ';
        $html .= 'autocomplete="new-password" ';
        $html .= 'class="' . ($options['css_class'] ?? 'form-input') . '">';
        
        // Match indicator
        $html .= '<div id="match_indicator_' . $confirmationId . '" class="absolute inset-y-0 right-0 pr-3 flex items-center hidden">';
        $html .= '<span class="text-green-500">✓</span>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    protected function renderStrengthMeter(string $passwordId, array $options): string
    {
        $html = '<div class="mt-2">';
        $html .= '<div class="text-sm text-gray-600 mb-1">Şifre Gücü:</div>';
        $html .= '<div class="w-full bg-gray-200 rounded-full h-2">';
        $html .= '<div id="strength_bar_' . $passwordId . '" class="h-2 rounded-full transition-all duration-300" style="width: 0%;"></div>';
        $html .= '</div>';
        $html .= '<div id="strength_text_' . $passwordId . '" class="text-xs text-gray-500 mt-1">Şifre giriniz</div>';
        $html .= '</div>';
        
        return $html;
    }

    protected function renderRequirements(string $passwordId, array $options): string
    {
        $requirements = $options['strength_requirements'];
        
        $html = '<div class="mt-3">';
        $html .= '<div class="text-sm text-gray-600 mb-2">Şifre Gereksinimleri:</div>';
        $html .= '<ul id="requirements_' . $passwordId . '" class="text-xs space-y-1">';
        
        if ($requirements['min_length']) {
            $html .= '<li data-requirement="length" class="flex items-center text-gray-500">';
            $html .= '<span class="requirement-icon mr-2">○</span>';
            $html .= 'En az ' . $requirements['min_length'] . ' karakter';
            $html .= '</li>';
        }
        
        if ($requirements['require_uppercase']) {
            $html .= '<li data-requirement="uppercase" class="flex items-center text-gray-500">';
            $html .= '<span class="requirement-icon mr-2">○</span>';
            $html .= 'En az bir büyük harf (A-Z)';
            $html .= '</li>';
        }
        
        if ($requirements['require_lowercase']) {
            $html .= '<li data-requirement="lowercase" class="flex items-center text-gray-500">';
            $html .= '<span class="requirement-icon mr-2">○</span>';
            $html .= 'En az bir küçük harf (a-z)';
            $html .= '</li>';
        }
        
        if ($requirements['require_numbers']) {
            $html .= '<li data-requirement="numbers" class="flex items-center text-gray-500">';
            $html .= '<span class="requirement-icon mr-2">○</span>';
            $html .= 'En az bir rakam (0-9)';
            $html .= '</li>';
        }
        
        if ($requirements['require_symbols']) {
            $html .= '<li data-requirement="symbols" class="flex items-center text-gray-500">';
            $html .= '<span class="requirement-icon mr-2">○</span>';
            $html .= 'En az bir özel karakter (!@#$%^&*)';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }

    protected function renderJavaScript(string $passwordId, array $options): string
    {
        $requirements = json_encode($options['strength_requirements']);
        $confirmationField = $options['require_confirmation'] ? 
            ($options['confirmation_field'] ?? ($passwordId . '_confirmation')) : null;
        
        return '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const passwordField = document.getElementById("' . $passwordId . '");
            const requirements = ' . $requirements . ';
            
            if (passwordField) {
                passwordField.addEventListener("input", function() {
                    checkPasswordStrength("' . $passwordId . '", this.value, requirements);
                    ' . ($confirmationField ? 'checkPasswordMatch("' . $passwordId . '", "' . $confirmationField . '");' : '') . '
                });
            }
            
            ' . ($confirmationField ? '
            const confirmField = document.getElementById("' . $confirmationField . '");
            if (confirmField) {
                confirmField.addEventListener("input", function() {
                    checkPasswordMatch("' . $passwordId . '", "' . $confirmationField . '");
                });
            }
            ' : '') . '
        });
        
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const type = field.getAttribute("type") === "password" ? "text" : "password";
            field.setAttribute("type", type);
        }
        
        function generatePassword(fieldId) {
            const password = generateRandomPassword();
            const field = document.getElementById(fieldId);
            field.value = password;
            field.dispatchEvent(new Event("input"));
            field.setAttribute("type", "text");
            setTimeout(() => field.setAttribute("type", "password"), 2000);
        }
        
        function generateRandomPassword() {
            const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
            let password = "";
            
            // Ensure at least one of each required type
            password += "abcdefghijklmnopqrstuvwxyz"[Math.floor(Math.random() * 26)];
            password += "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[Math.floor(Math.random() * 26)];
            password += "0123456789"[Math.floor(Math.random() * 10)];
            password += "!@#$%^&*"[Math.floor(Math.random() * 8)];
            
            // Fill the rest randomly
            for (let i = 4; i < 12; i++) {
                password += chars[Math.floor(Math.random() * chars.length)];
            }
            
            // Shuffle the password
            return password.split("").sort(() => Math.random() - 0.5).join("");
        }
        
        function checkPasswordStrength(fieldId, password, requirements) {
            const strengthBar = document.getElementById("strength_bar_" + fieldId);
            const strengthText = document.getElementById("strength_text_" + fieldId);
            const requirementsList = document.getElementById("requirements_" + fieldId);
            
            let score = 0;
            let maxScore = 0;
            
            // Check each requirement
            if (requirements.min_length) {
                maxScore++;
                const met = password.length >= requirements.min_length;
                updateRequirement(requirementsList, "length", met);
                if (met) score++;
            }
            
            if (requirements.require_uppercase) {
                maxScore++;
                const met = /[A-Z]/.test(password);
                updateRequirement(requirementsList, "uppercase", met);
                if (met) score++;
            }
            
            if (requirements.require_lowercase) {
                maxScore++;
                const met = /[a-z]/.test(password);
                updateRequirement(requirementsList, "lowercase", met);
                if (met) score++;
            }
            
            if (requirements.require_numbers) {
                maxScore++;
                const met = /[0-9]/.test(password);
                updateRequirement(requirementsList, "numbers", met);
                if (met) score++;
            }
            
            if (requirements.require_symbols) {
                maxScore++;
                const met = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                updateRequirement(requirementsList, "symbols", met);
                if (met) score++;
            }
            
            // Update strength bar
            const percentage = maxScore > 0 ? (score / maxScore) * 100 : 0;
            strengthBar.style.width = percentage + "%";
            
            // Update colors and text
            if (percentage === 0) {
                strengthBar.className = "h-2 rounded-full transition-all duration-300 bg-gray-300";
                strengthText.textContent = "Şifre giriniz";
                strengthText.className = "text-xs text-gray-500 mt-1";
            } else if (percentage < 40) {
                strengthBar.className = "h-2 rounded-full transition-all duration-300 bg-red-500";
                strengthText.textContent = "Zayıf";
                strengthText.className = "text-xs text-red-500 mt-1";
            } else if (percentage < 70) {
                strengthBar.className = "h-2 rounded-full transition-all duration-300 bg-yellow-500";
                strengthText.textContent = "Orta";
                strengthText.className = "text-xs text-yellow-600 mt-1";
            } else if (percentage < 100) {
                strengthBar.className = "h-2 rounded-full transition-all duration-300 bg-blue-500";
                strengthText.textContent = "İyi";
                strengthText.className = "text-xs text-blue-600 mt-1";
            } else {
                strengthBar.className = "h-2 rounded-full transition-all duration-300 bg-green-500";
                strengthText.textContent = "Mükemmel";
                strengthText.className = "text-xs text-green-600 mt-1";
            }
        }
        
        function updateRequirement(list, requirement, met) {
            const item = list.querySelector(`[data-requirement="${requirement}"]`);
            if (item) {
                const icon = item.querySelector(".requirement-icon");
                if (met) {
                    icon.textContent = "✓";
                    item.className = "flex items-center text-green-600";
                } else {
                    icon.textContent = "○";
                    item.className = "flex items-center text-gray-500";
                }
            }
        }
        
        function checkPasswordMatch(passwordId, confirmationId) {
            const password = document.getElementById(passwordId).value;
            const confirmation = document.getElementById(confirmationId).value;
            const indicator = document.getElementById("match_indicator_" + confirmationId);
            
            if (confirmation.length > 0) {
                if (password === confirmation) {
                    indicator.classList.remove("hidden");
                    indicator.innerHTML = \'<span class="text-green-500">✓</span>\';
                } else {
                    indicator.classList.remove("hidden");
                    indicator.innerHTML = \'<span class="text-red-500">✗</span>\';
                }
            } else {
                indicator.classList.add("hidden");
            }
        }
        </script>';
    }

    public function validate($value, array $options = []): array
    {
        $errors = parent::validate($value, $options);
        $options = array_merge($this->getDefaultOptions(), $options);

        if (!$this->isEmpty($value)) {
            $requirements = $options['strength_requirements'];
            
            // Length check
            if ($requirements['min_length'] && strlen($value) < $requirements['min_length']) {
                $errors[] = ($options['label'] ?? 'Şifre') . ' en az ' . $requirements['min_length'] . ' karakter olmalıdır.';
            }
            
            if ($options['maxlength'] && strlen($value) > $options['maxlength']) {
                $errors[] = ($options['label'] ?? 'Şifre') . ' en fazla ' . $options['maxlength'] . ' karakter olabilir.';
            }
            
            // Strength requirements
            if ($requirements['require_uppercase'] && !preg_match('/[A-Z]/', $value)) {
                $errors[] = ($options['label'] ?? 'Şifre') . ' en az bir büyük harf içermelidir.';
            }
            
            if ($requirements['require_lowercase'] && !preg_match('/[a-z]/', $value)) {
                $errors[] = ($options['label'] ?? 'Şifre') . ' en az bir küçük harf içermelidir.';
            }
            
            if ($requirements['require_numbers'] && !preg_match('/[0-9]/', $value)) {
                $errors[] = ($options['label'] ?? 'Şifre') . ' en az bir rakam içermelidir.';
            }
            
            if ($requirements['require_symbols'] && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value)) {
                $errors[] = ($options['label'] ?? 'Şifre') . ' en az bir özel karakter içermelidir.';
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
        
        // Hash the password based on algorithm
        switch ($options['hash_algorithm']) {
            case 'bcrypt':
                return password_hash($value, PASSWORD_BCRYPT);
            case 'argon2i':
                return password_hash($value, PASSWORD_ARGON2I);
            case 'argon2id':
                return password_hash($value, PASSWORD_ARGON2ID);
            default:
                return password_hash($value, PASSWORD_DEFAULT);
        }
    }

    /**
     * Verify password against hash
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if password needs rehashing
     */
    public function needsRehash(string $hash, array $options = []): bool
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        
        switch ($options['hash_algorithm']) {
            case 'bcrypt':
                return password_needs_rehash($hash, PASSWORD_BCRYPT);
            case 'argon2i':
                return password_needs_rehash($hash, PASSWORD_ARGON2I);
            case 'argon2id':
                return password_needs_rehash($hash, PASSWORD_ARGON2ID);
            default:
                return password_needs_rehash($hash, PASSWORD_DEFAULT);
        }
    }
}
