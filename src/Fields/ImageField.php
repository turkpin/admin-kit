<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

class ImageField extends AbstractFieldType
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
        'max_width',
        'max_height',
        'min_width',
        'min_height',
        'generate_thumbnails',
        'thumbnail_sizes',
        'quality',
        'maintain_aspect_ratio',
        'allow_crop',
        'show_preview',
        'preview_width',
        'preview_height',
        'multiple'
    ];

    public function getTypeName(): string
    {
        return 'image';
    }

    public function getDefaultOptions(): array
    {
        return array_merge(parent::getDefaultOptions(), [
            'upload_dir' => 'uploads/images/',
            'web_path' => '/uploads/images/',
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'max_file_size' => 5 * 1024 * 1024, // 5MB
            'max_width' => 2048,
            'max_height' => 2048,
            'min_width' => null,
            'min_height' => null,
            'generate_thumbnails' => true,
            'thumbnail_sizes' => [
                'small' => [150, 150],
                'medium' => [300, 300],
                'large' => [600, 600]
            ],
            'quality' => 85,
            'maintain_aspect_ratio' => true,
            'allow_crop' => false,
            'show_preview' => true,
            'preview_width' => 150,
            'preview_height' => 150,
            'multiple' => false
        ]);
    }

    public function renderDisplay($value, array $options = []): string
    {
        if ($this->isEmpty($value)) {
            return '<span class="text-gray-400">Resim yok</span>';
        }

        $options = array_merge($this->getDefaultOptions(), $options);

        if ($options['multiple'] && is_array($value)) {
            return $this->renderMultipleImages($value, $options);
        } else {
            return $this->renderSingleImage($value, $options);
        }
    }

    protected function renderSingleImage($imagePath, array $options): string
    {
        $webPath = $options['web_path'] . basename($imagePath);
        $previewWidth = $options['preview_width'];
        $previewHeight = $options['preview_height'];

        return '<div class="relative inline-block">'
               . '<img src="' . $this->escapeValue($webPath) . '" '
               . 'alt="Image" '
               . 'class="w-' . $previewWidth . ' h-' . $previewHeight . ' object-cover rounded-lg shadow-sm cursor-pointer" '
               . 'onclick="openImageModal(\'' . $this->escapeValue($webPath) . '\')">'
               . '</div>';
    }

    protected function renderMultipleImages(array $imagePaths, array $options): string
    {
        $html = '<div class="flex flex-wrap gap-2">';
        
        foreach ($imagePaths as $imagePath) {
            $html .= $this->renderSingleImage($imagePath, $options);
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

        // Show current image(s) if exists
        if (!$this->isEmpty($value)) {
            $html .= '<div class="mb-4">';
            $html .= '<label class="block text-sm font-medium text-gray-700 mb-2">Mevcut Resim</label>';
            $html .= $this->renderDisplay($value, $options);
            
            // Add remove option
            $html .= '<div class="mt-2">';
            $html .= '<label class="inline-flex items-center">';
            $html .= '<input type="checkbox" name="remove_' . $this->escapeValue($name) . '" value="1" class="form-checkbox">';
            $html .= '<span class="ml-2 text-sm text-red-600">Resmi sil</span>';
            $html .= '</label>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // File upload area
        $html .= '<div class="file-upload-area border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors" '
               . 'ondrop="handleDrop(event, \'' . $fieldId . '\')" '
               . 'ondragover="handleDragOver(event)" '
               . 'ondragleave="handleDragLeave(event)" '
               . 'onclick="document.getElementById(\'' . $fieldId . '\').click()">';

        $html .= '<input type="file" '
               . 'id="' . $fieldId . '" '
               . 'name="' . $this->escapeValue($name) . ($options['multiple'] ? '[]' : '') . '" '
               . 'accept="' . $this->getAcceptString($options['allowed_types']) . '" '
               . ($options['multiple'] ? 'multiple' : '') . ' '
               . 'class="hidden" '
               . 'onchange="handleFileSelect(event, \'' . $fieldId . '\')">';

        // Upload area content
        $html .= '<div class="upload-content">';
        $html .= '<svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>';
        $html .= '</svg>';
        $html .= '<p class="text-gray-600 mb-2">Resim yüklemek için tıklayın veya sürükleyip bırakın</p>';
        $html .= '<p class="text-xs text-gray-500">';
        $html .= 'Desteklenen formatlar: ' . implode(', ', $options['allowed_types']);
        $html .= ' • Maksimum boyut: ' . $this->formatBytes($options['max_file_size']);
        $html .= '</p>';
        $html .= '</div>';

        // Preview area
        $html .= '<div class="preview-area mt-4 hidden">';
        $html .= '<div class="preview-images flex flex-wrap gap-2"></div>';
        $html .= '</div>';

        $html .= '</div>';

        // Image cropper modal (if crop is enabled)
        if ($options['allow_crop']) {
            $html .= $this->renderCropModal($fieldId);
        }

        // Add JavaScript
        $html .= $this->renderJavaScript($fieldId, $options);

        return $html;
    }

    protected function renderCropModal(string $fieldId): string
    {
        return '<div id="cropModal_' . $fieldId . '" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Resmi Kırp</h3>
                    <button onclick="closeCropModal(\'' . $fieldId . '\')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="crop-container">
                    <img id="cropImage_' . $fieldId . '" src="" alt="Crop" style="max-width: 100%;">
                </div>
                <div class="flex justify-end space-x-2 mt-4">
                    <button onclick="closeCropModal(\'' . $fieldId . '\')" class="btn btn-secondary">İptal</button>
                    <button onclick="applyCrop(\'' . $fieldId . '\')" class="btn btn-primary">Uygula</button>
                </div>
            </div>
        </div>';
    }

    protected function renderJavaScript(string $fieldId, array $options): string
    {
        $maxFileSize = $options['max_file_size'];
        $allowedTypes = json_encode($options['allowed_types']);
        $allowCrop = $options['allow_crop'] ? 'true' : 'false';
        $multiple = $options['multiple'] ? 'true' : 'false';

        return '<script>
        // Drag and drop handlers
        function handleDragOver(e) {
            e.preventDefault();
            e.currentTarget.classList.add("border-blue-400", "bg-blue-50");
        }
        
        function handleDragLeave(e) {
            e.preventDefault();
            e.currentTarget.classList.remove("border-blue-400", "bg-blue-50");
        }
        
        function handleDrop(e, fieldId) {
            e.preventDefault();
            e.currentTarget.classList.remove("border-blue-400", "bg-blue-50");
            
            const files = e.dataTransfer.files;
            const input = document.getElementById(fieldId);
            input.files = files;
            
            handleFileSelect({target: input}, fieldId);
        }
        
        // File selection handler
        function handleFileSelect(e, fieldId) {
            const files = e.target.files;
            const allowedTypes = ' . $allowedTypes . ';
            const maxFileSize = ' . $maxFileSize . ';
            const allowCrop = ' . $allowCrop . ';
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
            showImagePreviews(validFiles, fieldId, allowCrop);
        }
        
        // Show image previews
        function showImagePreviews(files, fieldId, allowCrop) {
            const previewArea = document.querySelector(`#${fieldId}`).closest(".file-upload-area").querySelector(".preview-area");
            const previewContainer = previewArea.querySelector(".preview-images");
            
            previewContainer.innerHTML = "";
            previewArea.classList.remove("hidden");
            
            files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewDiv = document.createElement("div");
                    previewDiv.className = "relative inline-block";
                    
                    const img = document.createElement("img");
                    img.src = e.target.result;
                    img.className = "w-24 h-24 object-cover rounded border";
                    
                    const removeBtn = document.createElement("button");
                    removeBtn.type = "button";
                    removeBtn.className = "absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full text-xs hover:bg-red-600";
                    removeBtn.innerHTML = "×";
                    removeBtn.onclick = function() {
                        previewDiv.remove();
                        if (previewContainer.children.length === 0) {
                            previewArea.classList.add("hidden");
                        }
                    };
                    
                    previewDiv.appendChild(img);
                    previewDiv.appendChild(removeBtn);
                    
                    if (allowCrop) {
                        const cropBtn = document.createElement("button");
                        cropBtn.type = "button";
                        cropBtn.className = "absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs py-1";
                        cropBtn.innerHTML = "Kırp";
                        cropBtn.onclick = function() {
                            openCropModal(fieldId, e.target.result);
                        };
                        previewDiv.appendChild(cropBtn);
                    }
                    
                    previewContainer.appendChild(previewDiv);
                };
                reader.readAsDataURL(file);
            });
        }
        
        // Crop modal functions
        function openCropModal(fieldId, imageSrc) {
            const modal = document.getElementById(`cropModal_${fieldId}`);
            const cropImage = document.getElementById(`cropImage_${fieldId}`);
            
            cropImage.src = imageSrc;
            modal.classList.remove("hidden");
            
            // Initialize cropper (would need Cropper.js library)
            // This is a simplified version
        }
        
        function closeCropModal(fieldId) {
            const modal = document.getElementById(`cropModal_${fieldId}`);
            modal.classList.add("hidden");
        }
        
        function applyCrop(fieldId) {
            // Apply crop logic here
            // This would integrate with Cropper.js
            closeCropModal(fieldId);
        }
        
        // Utility function
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return "0 Bytes";
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ["Bytes", "KB", "MB", "GB"];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i];
        }
        
        // Image modal for viewing
        function openImageModal(imageSrc) {
            const modal = document.createElement("div");
            modal.className = "fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50";
            modal.onclick = function() { modal.remove(); };
            
            const img = document.createElement("img");
            img.src = imageSrc;
            img.className = "max-w-full max-h-full object-contain";
            img.onclick = function(e) { e.stopPropagation(); };
            
            modal.appendChild(img);
            document.body.appendChild(modal);
        }
        </script>';
    }

    protected function getAcceptString(array $allowedTypes): string
    {
        $mimeTypes = [];
        foreach ($allowedTypes as $type) {
            switch (strtolower($type)) {
                case 'jpg':
                case 'jpeg':
                    $mimeTypes[] = 'image/jpeg';
                    break;
                case 'png':
                    $mimeTypes[] = 'image/png';
                    break;
                case 'gif':
                    $mimeTypes[] = 'image/gif';
                    break;
                case 'webp':
                    $mimeTypes[] = 'image/webp';
                    break;
            }
        }
        return implode(',', array_unique($mimeTypes));
    }

    public function validate($value, array $options = []): array
    {
        $errors = parent::validate($value, $options);
        $options = array_merge($this->getDefaultOptions(), $options);

        // Note: File validation would typically happen in a separate upload handler
        // This is placeholder validation for the stored file path

        if (!$this->isEmpty($value)) {
            if ($options['multiple']) {
                if (!is_array($value)) {
                    $errors[] = ($options['label'] ?? 'Resim') . ' geçerli bir resim listesi olmalıdır.';
                }
            } else {
                // Validate single image path
                if (!is_string($value)) {
                    $errors[] = ($options['label'] ?? 'Resim') . ' geçerli bir resim olmalıdır.';
                }
            }
        }

        return $errors;
    }

    public function processFormValue($value, array $options = [])
    {
        // This method would typically handle the uploaded file
        // For now, return the value as-is
        return $value;
    }

    /**
     * Process uploaded file and generate thumbnails
     */
    public function processUploadedFile(array $uploadedFile, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        
        // Validate uploaded file
        $this->validateUploadedFile($uploadedFile, $options);
        
        // Generate unique filename
        $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '.' . $extension;
        $uploadPath = $options['upload_dir'] . $filename;
        
        // Create upload directory if it doesn't exist
        if (!is_dir($options['upload_dir'])) {
            mkdir($options['upload_dir'], 0755, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
            throw new \Exception('Dosya yüklenirken hata oluştu.');
        }
        
        // Process image (resize, optimize)
        $this->processImage($uploadPath, $options);
        
        // Generate thumbnails
        if ($options['generate_thumbnails']) {
            $this->generateThumbnails($uploadPath, $options);
        }
        
        return $filename;
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
        
        // Validate image
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            throw new \Exception('Geçersiz resim dosyası.');
        }
        
        // Check dimensions
        if ($options['min_width'] && $imageInfo[0] < $options['min_width']) {
            throw new \Exception('Resim çok küçük. Minimum genişlik: ' . $options['min_width'] . 'px');
        }
        
        if ($options['min_height'] && $imageInfo[1] < $options['min_height']) {
            throw new \Exception('Resim çok küçük. Minimum yükseklik: ' . $options['min_height'] . 'px');
        }
    }

    protected function processImage(string $imagePath, array $options): void
    {
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) return;
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];
        
        // Check if resize is needed
        if ($width <= $options['max_width'] && $height <= $options['max_height']) {
            return;
        }
        
        // Calculate new dimensions
        if ($options['maintain_aspect_ratio']) {
            $ratio = min($options['max_width'] / $width, $options['max_height'] / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);
        } else {
            $newWidth = $options['max_width'];
            $newHeight = $options['max_height'];
        }
        
        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Load source image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($imagePath);
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($imagePath);
                break;
            default:
                return;
        }
        
        // Resize
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save resized image
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $imagePath, $options['quality']);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $imagePath);
                break;
            case IMAGETYPE_GIF:
                imagegif($newImage, $imagePath);
                break;
        }
        
        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($newImage);
    }

    protected function generateThumbnails(string $imagePath, array $options): void
    {
        $pathInfo = pathinfo($imagePath);
        $baseName = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        $directory = $pathInfo['dirname'];
        
        foreach ($options['thumbnail_sizes'] as $sizeName => $dimensions) {
            $thumbnailPath = $directory . '/' . $baseName . '_' . $sizeName . '.' . $extension;
            
            $thumbnailOptions = array_merge($options, [
                'max_width' => $dimensions[0],
                'max_height' => $dimensions[1]
            ]);
            
            copy($imagePath, $thumbnailPath);
            $this->processImage($thumbnailPath, $thumbnailOptions);
        }
    }
}
