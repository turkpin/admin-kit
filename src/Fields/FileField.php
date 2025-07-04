<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

class FileField extends AbstractFieldType
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
        'upload_dir',
        'web_path',
        'allowed_types',
        'max_file_size',
        'multiple',
        'show_preview',
        'show_file_info',
        'download_link',
        'virus_scan',
        'organize_by_date'
    ];

    public function getTypeName(): string
    {
        return 'file';
    }

    public function getDefaultOptions(): array
    {
        return array_merge(parent::getDefaultOptions(), [
            'upload_dir' => 'uploads/files/',
            'web_path' => '/uploads/files/',
            'allowed_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'],
            'max_file_size' => 10 * 1024 * 1024, // 10MB
            'multiple' => false,
            'show_preview' => true,
            'show_file_info' => true,
            'download_link' => true,
            'virus_scan' => false,
            'organize_by_date' => true
        ]);
    }

    public function renderDisplay($value, array $options = []): string
    {
        if ($this->isEmpty($value)) {
            return '<span class="text-gray-400">Dosya yok</span>';
        }

        $options = array_merge($this->getDefaultOptions(), $options);

        if ($options['multiple'] && is_array($value)) {
            return $this->renderMultipleFiles($value, $options);
        } else {
            return $this->renderSingleFile($value, $options);
        }
    }

    protected function renderSingleFile($filePath, array $options): string
    {
        $fileName = basename($filePath);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
        $webPath = $options['web_path'] . $fileName;

        $html = '<div class="file-display flex items-center space-x-3 p-3 border rounded-lg bg-gray-50">';
        
        // File icon
        $html .= '<div class="flex-shrink-0">';
        $html .= $this->getFileIcon($fileExtension);
        $html .= '</div>';
        
        // File info
        $html .= '<div class="flex-1 min-w-0">';
        $html .= '<div class="text-sm font-medium text-gray-900 truncate">' . $this->escapeValue($fileName) . '</div>';
        
        if ($options['show_file_info'] && $fileSize > 0) {
            $html .= '<div class="text-xs text-gray-500">';
            $html .= $this->formatBytes($fileSize) . ' • ' . strtoupper($fileExtension);
            $html .= '</div>';
        }
        $html .= '</div>';
        
        // Actions
        if ($options['download_link']) {
            $html .= '<div class="flex-shrink-0">';
            $html .= '<a href="' . $this->escapeValue($webPath) . '" download ';
            $html .= 'class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">İndir</a>';
            $html .= '</div>';
        }
        
        $html .= '</div>';

        return $html;
    }

    protected function renderMultipleFiles(array $filePaths, array $options): string
    {
        $html = '<div class="space-y-2">';
        
        foreach ($filePaths as $filePath) {
            $html .= $this->renderSingleFile($filePath, $options);
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

        // Show current file(s) if exists
        if (!$this->isEmpty($value)) {
            $html .= '<div class="mb-4">';
            $html .= '<label class="block text-sm font-medium text-gray-700 mb-2">Mevcut Dosya</label>';
            $html .= $this->renderDisplay($value, $options);
            
            // Add remove option
            $html .= '<div class="mt-2">';
            $html .= '<label class="inline-flex items-center">';
            $html .= '<input type="checkbox" name="remove_' . $this->escapeValue($name) . '" value="1" class="form-checkbox">';
            $html .= '<span class="ml-2 text-sm text-red-600">Dosyayı sil</span>';
            $html .= '</label>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // File upload area
        $html .= '<div class="file-upload-area border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors" ';
        $html .= 'ondrop="handleFileDrop(event, \'' . $fieldId . '\')" ';
        $html .= 'ondragover="handleDragOver(event)" ';
        $html .= 'ondragleave="handleDragLeave(event)" ';
        $html .= 'onclick="document.getElementById(\'' . $fieldId . '\').click()">';

        $html .= '<input type="file" ';
        $html .= 'id="' . $fieldId . '" ';
        $html .= 'name="' . $this->escapeValue($name) . ($options['multiple'] ? '[]' : '') . '" ';
        $html .= 'accept="' . $this->getAcceptString($options['allowed_types']) . '" ';
        $html .= ($options['multiple'] ? 'multiple' : '') . ' ';
        $html .= 'class="hidden" ';
        $html .= 'onchange="handleFileSelection(event, \'' . $fieldId . '\')">';

        // Upload area content
        $html .= '<div class="upload-content">';
        $html .= '<svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>';
        $html .= '</svg>';
        $html .= '<p class="text-gray-600 mb-2">Dosya yüklemek için tıklayın veya sürükleyip bırakın</p>';
        $html .= '<p class="text-xs text-gray-500">';
        $html .= 'Desteklenen formatlar: ' . implode(', ', array_map('strtoupper', $options['allowed_types']));
        $html .= ' • Maksimum boyut: ' . $this->formatBytes($options['max_file_size']);
        $html .= '</p>';
        $html .= '</div>';

        // Preview area
        $html .= '<div class="preview-area mt-4 hidden">';
        $html .= '<div class="preview-files space-y-2"></div>';
        $html .= '</div>';

        $html .= '</div>';

        // Progress bar for uploads
        $html .= '<div id="upload_progress_' . $fieldId . '" class="hidden mt-3">';
        $html .= '<div class="bg-gray-200 rounded-full h-2">';
        $html .= '<div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>';
        $html .= '</div>';
        $html .= '<div class="text-sm text-gray-600 mt-1">Yükleniyor...</div>';
        $html .= '</div>';

        // Add JavaScript
        $html .= $this->renderJavaScript($fieldId, $options);

        return $html;
    }

    protected function getFileIcon(string $extension): string
    {
        $iconClass = 'w-8 h-8';
        
        switch ($extension) {
            case 'pdf':
                return '<svg class="' . $iconClass . ' text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M4 18h12a2 2 0 002-2V8a2 2 0 00-2-2h-5L9 4H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>';
            
            case 'doc':
            case 'docx':
                return '<svg class="' . $iconClass . ' text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M4 18h12a2 2 0 002-2V8a2 2 0 00-2-2h-5L9 4H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>';
            
            case 'xls':
            case 'xlsx':
                return '<svg class="' . $iconClass . ' text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M4 18h12a2 2 0 002-2V8a2 2 0 00-2-2h-5L9 4H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>';
            
            case 'zip':
            case 'rar':
                return '<svg class="' . $iconClass . ' text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M4 18h12a2 2 0 002-2V8a2 2 0 00-2-2h-5L9 4H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>';
            
            default:
                return '<svg class="' . $iconClass . ' text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M4 18h12a2 2 0 002-2V8a2 2 0 00-2-2h-5L9 4H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>';
        }
    }

    protected function getAcceptString(array $allowedTypes): string
    {
        $mimeTypes = [];
        foreach ($allowedTypes as $type) {
            switch (strtolower($type)) {
                case 'pdf':
                    $mimeTypes[] = 'application/pdf';
                    break;
                case 'doc':
                    $mimeTypes[] = 'application/msword';
                    break;
                case 'docx':
                    $mimeTypes[] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                    break;
                case 'xls':
                    $mimeTypes[] = 'application/vnd.ms-excel';
                    break;
                case 'xlsx':
                    $mimeTypes[] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    break;
                case 'txt':
                    $mimeTypes[] = 'text/plain';
                    break;
                case 'zip':
                    $mimeTypes[] = 'application/zip';
                    break;
                case 'rar':
                    $mimeTypes[] = 'application/x-rar-compressed';
                    break;
            }
        }
        return implode(',', array_unique($mimeTypes));
    }

    protected function renderJavaScript(string $fieldId, array $options): string
    {
        $maxFileSize = $options['max_file_size'];
        $allowedTypes = json_encode($options['allowed_types']);
        $multiple = $options['multiple'] ? 'true' : 'false';

        return '<script>
        // Drag and drop handlers for files
        function handleDragOver(e) {
            e.preventDefault();
            e.currentTarget.classList.add("border-blue-400", "bg-blue-50");
        }
        
        function handleDragLeave(e) {
            e.preventDefault();
            e.currentTarget.classList.remove("border-blue-400", "bg-blue-50");
        }
        
        function handleFileDrop(e, fieldId) {
            e.preventDefault();
            e.currentTarget.classList.remove("border-blue-400", "bg-blue-50");
            
            const files = e.dataTransfer.files;
            const input = document.getElementById(fieldId);
            input.files = files;
            
            handleFileSelection({target: input}, fieldId);
        }
        
        // File selection handler
        function handleFileSelection(e, fieldId) {
            const files = e.target.files;
            const allowedTypes = ' . $allowedTypes . ';
            const maxFileSize = ' . $maxFileSize . ';
            const multiple = ' . $multiple . ';
            
            if (!files.length) return;
            
            const validFiles = [];
            
            for (let file of files) {
                // Validate file type
                const extension = file.name.split(".").pop().toLowerCase();
                if (!allowedTypes.includes(extension)) {
                    alert(`Geçersiz dosya türü: ${file.name}. Desteklenen türler: ${allowedTypes.join(", ")}`);
                    continue;
                }
                
                // Validate file size
                if (file.size > maxFileSize) {
                    alert(`Dosya çok büyük: ${file.name}. Maksimum boyut: ${formatBytes(maxFileSize)}`);
                    continue;
                }
                
                validFiles.push(file);
            }
            
            if (validFiles.length === 0) {
                e.target.value = "";
                return;
            }
            
            // Show previews
            showFilePreviews(validFiles, fieldId);
        }
        
        // Show file previews
        function showFilePreviews(files, fieldId) {
            const previewArea = document.querySelector(`#${fieldId}`).closest(".file-upload-area").querySelector(".preview-area");
            const previewContainer = previewArea.querySelector(".preview-files");
            
            previewContainer.innerHTML = "";
            previewArea.classList.remove("hidden");
            
            files.forEach((file, index) => {
                const previewDiv = document.createElement("div");
                previewDiv.className = "flex items-center justify-between p-3 border rounded bg-white";
                
                const fileInfo = document.createElement("div");
                fileInfo.className = "flex items-center space-x-3";
                
                const icon = getFileIconSVG(file.name.split(".").pop().toLowerCase());
                fileInfo.innerHTML = `
                    <div class="flex-shrink-0">${icon}</div>
                    <div>
                        <div class="text-sm font-medium text-gray-900">${file.name}</div>
                        <div class="text-xs text-gray-500">${formatBytes(file.size)}</div>
                    </div>
                `;
                
                const removeBtn = document.createElement("button");
                removeBtn.type = "button";
                removeBtn.className = "text-red-600 hover:text-red-800 text-sm";
                removeBtn.innerHTML = "Kaldır";
                removeBtn.onclick = function() {
                    previewDiv.remove();
                    if (previewContainer.children.length === 0) {
                        previewArea.classList.add("hidden");
                    }
                };
                
                previewDiv.appendChild(fileInfo);
                previewDiv.appendChild(removeBtn);
                previewContainer.appendChild(previewDiv);
            });
        }
        
        // Get file icon SVG
        function getFileIconSVG(extension) {
            const iconClass = "w-6 h-6";
            
            switch (extension) {
                case "pdf":
                    return `<svg class="${iconClass} text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 18h12a2 2 0 002-2V8a2 2 0 00-2-2h-5L9 4H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>`;
                
                case "doc":
                case "docx":
                    return `<svg class="${iconClass} text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 18h12a2 2 0 002-2V8a2 2 0 00-2-2h-5L9 4H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>`;
                
                case "xls":
                case "xlsx":
                    return `<svg class="${iconClass} text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 18h12a2 2 0 002-2V8a2 2 0 00-2-2h-5L9 4H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>`;
                
                default:
                    return `<svg class="${iconClass} text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 18h12a2 2 0 002-2V8a2 2 0 00-2-2h-5L9 4H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>`;
            }
        }
        
        // Utility function for formatting bytes
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return "0 Bytes";
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ["Bytes", "KB", "MB", "GB"];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i];
        }
        </script>';
    }

    public function validate($value, array $options = []): array
    {
        $errors = parent::validate($value, $options);
        $options = array_merge($this->getDefaultOptions(), $options);

        // Note: File validation would typically happen in a separate upload handler
        if (!$this->isEmpty($value)) {
            if ($options['multiple']) {
                if (!is_array($value)) {
                    $errors[] = ($options['label'] ?? 'Dosya') . ' geçerli bir dosya listesi olmalıdır.';
                }
            } else {
                if (!is_string($value)) {
                    $errors[] = ($options['label'] ?? 'Dosya') . ' geçerli bir dosya olmalıdır.';
                }
            }
        }

        return $errors;
    }

    public function processFormValue($value, array $options = [])
    {
        // This method would typically handle the uploaded file
        return $value;
    }

    /**
     * Process uploaded file
     */
    public function processUploadedFile(array $uploadedFile, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        
        // Validate uploaded file
        $this->validateUploadedFile($uploadedFile, $options);
        
        // Generate unique filename
        $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $baseName = pathinfo($uploadedFile['name'], PATHINFO_FILENAME);
        $filename = $this->generateSafeFilename($baseName) . '_' . uniqid() . '.' . $extension;
        
        // Organize by date if enabled
        $uploadDir = $options['upload_dir'];
        if ($options['organize_by_date']) {
            $datePath = date('Y/m/');
            $uploadDir .= $datePath;
        }
        
        $uploadPath = $uploadDir . $filename;
        
        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
            throw new \Exception('Dosya yüklenirken hata oluştu.');
        }
        
        // Virus scan if enabled
        if ($options['virus_scan']) {
            $this->performVirusScan($uploadPath);
        }
        
        return $uploadPath;
    }

    protected function validateUploadedFile(array $file, array $options): void
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Dosya yükleme hatası: ' . $file['error']);
        }
        
        // Check file size
        if ($file['size'] > $options['max_file_size']) {
            throw new \Exception('Dosya çok büyük. Maksimum: ' . $this->formatBytes($options['max_file_size']));
        }
        
        // Check file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $options['allowed_types'])) {
            throw new \Exception('Geçersiz dosya türü. İzin verilenler: ' . implode(', ', $options['allowed_types']));
        }
        
        // Check for malicious content
        $this->checkMaliciousContent($file['tmp_name'], $extension);
    }

    protected function generateSafeFilename(string $filename): string
    {
        // Remove special characters and spaces
        $filename = preg_replace('/[^a-zA-Z0-9ğüşıöçĞÜŞİÖÇ._-]/', '_', $filename);
        
        // Remove multiple underscores
        $filename = preg_replace('/_+/', '_', $filename);
        
        // Trim underscores from beginning and end
        return trim($filename, '_');
    }

    protected function checkMaliciousContent(string $filePath, string $extension): void
    {
        // Basic malicious content detection
        $dangerousExtensions = ['php', 'exe', 'bat', 'cmd', 'scr', 'com', 'pif', 'js', 'jar'];
        
        if (in_array($extension, $dangerousExtensions)) {
            throw new \Exception('Bu dosya türü güvenlik nedeniyle yüklenemez.');
        }
        
        // Check file contents for PHP tags (basic check)
        $content = file_get_contents($filePath, false, null, 0, 1024);
        if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false) {
            throw new \Exception('Dosya güvenlik taramasını geçemedi.');
        }
    }

    protected function performVirusScan(string $filePath): void
    {
        // This would integrate with antivirus software
        // For now, just a placeholder
        
        // Example integration with ClamAV:
        // exec("clamscan " . escapeshellarg($filePath), $output, $return_var);
        // if ($return_var !== 0) {
        //     unlink($filePath);
        //     throw new \Exception('Dosya virüs taramasını geçemedi.');
        // }
    }

    /**
     * Get file MIME type
     */
    public function getMimeType(string $filePath): string
    {
        if (function_exists('mime_content_type')) {
            return mime_content_type($filePath);
        } elseif (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            return $mimeType;
        }
        
        return 'application/octet-stream';
    }

    /**
     * Check if file is readable preview
     */
    public function isPreviewable(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $previewableTypes = ['pdf', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
        
        return in_array($extension, $previewableTypes);
    }
}
