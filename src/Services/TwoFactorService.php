<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Turkpin\AdminKit\Services\CacheService;
use Turkpin\AdminKit\Services\AuthService;

class TwoFactorService
{
    private CacheService $cacheService;
    private AuthService $authService;
    private array $config;

    public function __construct(
        CacheService $cacheService,
        AuthService $authService,
        array $config = []
    ) {
        $this->cacheService = $cacheService;
        $this->authService = $authService;
        $this->config = array_merge([
            'issuer' => 'AdminKit',
            'qr_code_size' => 200,
            'backup_codes_count' => 8,
            'code_length' => 6,
            'time_step' => 30,
            'max_attempts' => 3,
            'lockout_time' => 300, // 5 minutes
            'sms_enabled' => false,
            'email_enabled' => true
        ], $config);
    }

    /**
     * Generate TOTP secret for user
     */
    public function generateSecret(): string
    {
        $secret = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $secret;
    }

    /**
     * Generate QR code URL for TOTP setup
     */
    public function generateQRCodeUrl(string $email, string $secret): string
    {
        $issuer = urlencode($this->config['issuer']);
        $email = urlencode($email);
        $secret = urlencode($secret);
        
        $otpauthUrl = "otpauth://totp/{$issuer}:{$email}?secret={$secret}&issuer={$issuer}";
        
        return "https://api.qrserver.com/v1/create-qr-code/?" . http_build_query([
            'size' => $this->config['qr_code_size'] . 'x' . $this->config['qr_code_size'],
            'data' => $otpauthUrl
        ]);
    }

    /**
     * Verify TOTP code
     */
    public function verifyTOTP(string $secret, string $code): bool
    {
        if (strlen($code) !== $this->config['code_length']) {
            return false;
        }

        $time = floor(time() / $this->config['time_step']);
        
        // Check current time window and ±1 windows for clock drift
        for ($i = -1; $i <= 1; $i++) {
            $calculatedCode = $this->generateTOTP($secret, $time + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate TOTP code for given time
     */
    private function generateTOTP(string $secret, int $time): string
    {
        // Convert secret from base32
        $secret = $this->base32Decode($secret);
        
        // Pack time as 8-byte big-endian
        $timeHex = pack('N*', 0) . pack('N*', $time);
        
        // HMAC-SHA1
        $hash = hash_hmac('sha1', $timeHex, $secret, true);
        
        // Dynamic truncation
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, $this->config['code_length']);
        
        return str_pad((string)$code, $this->config['code_length'], '0', STR_PAD_LEFT);
    }

    /**
     * Base32 decode
     */
    private function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $decoded = '';
        
        for ($i = 0; $i < strlen($secret); $i += 8) {
            $chunk = substr($secret, $i, 8);
            $chunk = str_pad($chunk, 8, '=');
            
            $binary = '';
            for ($j = 0; $j < 8; $j++) {
                if ($chunk[$j] !== '=') {
                    $pos = strpos($alphabet, $chunk[$j]);
                    $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
                }
            }
            
            for ($j = 0; $j < strlen($binary); $j += 8) {
                if (strlen($binary) - $j >= 8) {
                    $decoded .= chr(bindec(substr($binary, $j, 8)));
                }
            }
        }
        
        return $decoded;
    }

    /**
     * Generate backup codes
     */
    public function generateBackupCodes(): array
    {
        $codes = [];
        
        for ($i = 0; $i < $this->config['backup_codes_count']; $i++) {
            $codes[] = bin2hex(random_bytes(4)); // 8 character hex codes
        }
        
        return $codes;
    }

    /**
     * Enable 2FA for user
     */
    public function enableTwoFactor(int $userId, string $secret, array $backupCodes): bool
    {
        try {
            // Store in cache (in real implementation, store in database)
            $this->cacheService->set("2fa_secret:{$userId}", $secret, 86400 * 365);
            $this->cacheService->set("2fa_backup_codes:{$userId}", $backupCodes, 86400 * 365);
            $this->cacheService->set("2fa_enabled:{$userId}", true, 86400 * 365);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Disable 2FA for user
     */
    public function disableTwoFactor(int $userId): bool
    {
        try {
            $this->cacheService->delete("2fa_secret:{$userId}");
            $this->cacheService->delete("2fa_backup_codes:{$userId}");
            $this->cacheService->delete("2fa_enabled:{$userId}");
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if 2FA is enabled for user
     */
    public function isTwoFactorEnabled(int $userId): bool
    {
        return (bool)$this->cacheService->get("2fa_enabled:{$userId}", fn() => false);
    }

    /**
     * Verify 2FA code (TOTP or backup)
     */
    public function verifyCode(int $userId, string $code): bool
    {
        if (!$this->isTwoFactorEnabled($userId)) {
            return true; // 2FA not enabled
        }

        // Check rate limiting
        if (!$this->checkRateLimit($userId)) {
            return false;
        }

        // Get user's secret and backup codes
        $secret = $this->cacheService->get("2fa_secret:{$userId}");
        $backupCodes = $this->cacheService->get("2fa_backup_codes:{$userId}", fn() => []);

        if (!$secret) {
            return false;
        }

        // Try TOTP first
        if ($this->verifyTOTP($secret, $code)) {
            $this->resetRateLimit($userId);
            return true;
        }

        // Try backup codes
        if (in_array($code, $backupCodes)) {
            // Remove used backup code
            $backupCodes = array_filter($backupCodes, fn($c) => $c !== $code);
            $this->cacheService->set("2fa_backup_codes:{$userId}", $backupCodes, 86400 * 365);
            
            $this->resetRateLimit($userId);
            return true;
        }

        // Failed attempt
        $this->recordFailedAttempt($userId);
        return false;
    }

    /**
     * Check rate limiting for 2FA attempts
     */
    private function checkRateLimit(int $userId): bool
    {
        $key = "2fa_attempts:{$userId}";
        $attempts = (int)$this->cacheService->get($key, fn() => 0);
        
        if ($attempts >= $this->config['max_attempts']) {
            return false;
        }
        
        return true;
    }

    /**
     * Record failed 2FA attempt
     */
    private function recordFailedAttempt(int $userId): void
    {
        $key = "2fa_attempts:{$userId}";
        $attempts = (int)$this->cacheService->get($key, fn() => 0);
        $attempts++;
        
        $this->cacheService->set($key, $attempts, $this->config['lockout_time']);
    }

    /**
     * Reset rate limit after successful verification
     */
    private function resetRateLimit(int $userId): void
    {
        $this->cacheService->delete("2fa_attempts:{$userId}");
    }

    /**
     * Send 2FA code via email
     */
    public function sendEmailCode(int $userId, string $email): bool
    {
        if (!$this->config['email_enabled']) {
            return false;
        }

        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store code for 5 minutes
        $this->cacheService->set("2fa_email_code:{$userId}", $code, 300);
        
        // In real implementation, send email here
        error_log("2FA Email code for user {$userId}: {$code}");
        
        return true;
    }

    /**
     * Verify email 2FA code
     */
    public function verifyEmailCode(int $userId, string $code): bool
    {
        $storedCode = $this->cacheService->get("2fa_email_code:{$userId}");
        
        if (!$storedCode || !hash_equals($storedCode, $code)) {
            return false;
        }
        
        // Remove used code
        $this->cacheService->delete("2fa_email_code:{$userId}");
        
        return true;
    }

    /**
     * Get 2FA status for user
     */
    public function getTwoFactorStatus(int $userId): array
    {
        $enabled = $this->isTwoFactorEnabled($userId);
        $backupCodes = $this->cacheService->get("2fa_backup_codes:{$userId}", fn() => []);
        $attempts = (int)$this->cacheService->get("2fa_attempts:{$userId}", fn() => 0);
        
        return [
            'enabled' => $enabled,
            'backup_codes_remaining' => count($backupCodes),
            'failed_attempts' => $attempts,
            'locked_out' => $attempts >= $this->config['max_attempts'],
            'methods' => [
                'totp' => $enabled,
                'email' => $this->config['email_enabled'],
                'sms' => $this->config['sms_enabled']
            ]
        ];
    }

    /**
     * Render 2FA setup form
     */
    public function renderSetupForm(string $email, string $secret, string $qrCodeUrl): string
    {
        $backupCodes = $this->generateBackupCodes();
        
        return '
        <div class="2fa-setup max-w-md mx-auto bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Two-Factor Authentication Setup</h3>
            
            <div class="space-y-6">
                <!-- Step 1: Install App -->
                <div class="step">
                    <h4 class="font-medium text-gray-800 mb-2">1. Install Authenticator App</h4>
                    <p class="text-sm text-gray-600 mb-3">
                        Install Google Authenticator, Authy, or similar app on your phone.
                    </p>
                </div>
                
                <!-- Step 2: Scan QR Code -->
                <div class="step">
                    <h4 class="font-medium text-gray-800 mb-2">2. Scan QR Code</h4>
                    <div class="text-center mb-3">
                        <img src="' . htmlspecialchars($qrCodeUrl) . '" alt="QR Code" class="mx-auto">
                    </div>
                    <p class="text-xs text-gray-500 mb-2">Or enter this code manually:</p>
                    <code class="bg-gray-100 px-2 py-1 rounded text-sm font-mono">' . htmlspecialchars($secret) . '</code>
                </div>
                
                <!-- Step 3: Verify -->
                <div class="step">
                    <h4 class="font-medium text-gray-800 mb-2">3. Verify Setup</h4>
                    <form onsubmit="verify2FA(event)">
                        <input type="hidden" name="secret" value="' . htmlspecialchars($secret) . '">
                        <input type="text" 
                               name="verification_code" 
                               placeholder="Enter 6-digit code" 
                               maxlength="6" 
                               pattern="[0-9]{6}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                               required>
                        <button type="submit" class="w-full mt-3 bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">
                            Verify & Enable 2FA
                        </button>
                    </form>
                </div>
                
                <!-- Backup Codes -->
                <div class="backup-codes bg-yellow-50 border border-yellow-200 rounded p-4">
                    <h4 class="font-medium text-yellow-800 mb-2">⚠️ Backup Codes</h4>
                    <p class="text-sm text-yellow-700 mb-3">
                        Save these backup codes in a safe place. Each can only be used once.
                    </p>
                    <div class="grid grid-cols-2 gap-2 text-sm font-mono">
                        ' . implode('', array_map(fn($code) => '<code class="bg-white px-2 py-1 rounded">' . $code . '</code>', $backupCodes)) . '
                    </div>
                    <button onclick="downloadBackupCodes()" class="mt-3 text-sm text-yellow-700 underline">
                        Download as text file
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        function verify2FA(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            
            fetch("/admin/2fa/verify-setup", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("2FA enabled successfully!");
                    window.location.reload();
                } else {
                    alert("Verification failed: " + (data.message || "Invalid code"));
                }
            })
            .catch(error => {
                alert("Error: " + error.message);
            });
        }
        
        function downloadBackupCodes() {
            const codes = [' . implode(',', array_map(fn($code) => '"' . $code . '"', $backupCodes)) . '];
            const content = "AdminKit 2FA Backup Codes\\n\\n" + codes.join("\\n") + "\\n\\nKeep these codes safe!";
            const blob = new Blob([content], { type: "text/plain" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = "adminkit-backup-codes.txt";
            a.click();
            URL.revokeObjectURL(url);
        }
        </script>';
    }

    /**
     * Render 2FA verification form
     */
    public function renderVerificationForm(): string
    {
        return '
        <div class="2fa-verification max-w-sm mx-auto bg-white rounded-lg shadow p-6">
            <div class="text-center mb-6">
                <div class="mx-auto w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Two-Factor Authentication</h3>
                <p class="text-sm text-gray-600">Enter the code from your authenticator app</p>
            </div>
            
            <form onsubmit="submit2FA(event)">
                <div class="mb-4">
                    <input type="text" 
                           name="2fa_code" 
                           placeholder="6-digit code" 
                           maxlength="6" 
                           pattern="[0-9]{6}"
                           class="w-full px-3 py-2 text-center text-lg border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                           autofocus
                           required>
                </div>
                
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 mb-4">
                    Verify
                </button>
                
                <div class="text-center">
                    <button type="button" onclick="toggleBackupCode()" class="text-sm text-gray-600 underline">
                        Use backup code instead
                    </button>
                </div>
                
                <!-- Backup code input (hidden by default) -->
                <div id="backup-code-input" class="hidden mt-4">
                    <input type="text" 
                           name="backup_code" 
                           placeholder="Enter backup code" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="text-xs text-gray-500 mt-1">Backup codes are 8 characters long</p>
                </div>
            </form>
        </div>
        
        <script>
        function submit2FA(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            
            fetch("/admin/2fa/verify", {
                method: "POST", 
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect || "/admin/dashboard";
                } else {
                    alert("Verification failed: " + (data.message || "Invalid code"));
                }
            })
            .catch(error => {
                alert("Error: " + error.message);
            });
        }
        
        function toggleBackupCode() {
            const backupInput = document.getElementById("backup-code-input");
            const totpInput = document.querySelector("input[name=\'2fa_code\']");
            
            if (backupInput.classList.contains("hidden")) {
                backupInput.classList.remove("hidden");
                totpInput.disabled = true;
                backupInput.querySelector("input").focus();
            } else {
                backupInput.classList.add("hidden");
                totpInput.disabled = false;
                totpInput.focus();
            }
        }
        </script>';
    }
}
