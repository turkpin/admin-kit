<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

class TextareaField extends AbstractFieldType
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
        'rows',
        'cols',
        'maxlength',
        'minlength',
        'show_word_count',
        'show_char_count',
        'auto_resize',
        'strip_tags',
        'nl2br_on_display'
    ];

    public function getTypeName(): string
    {
        return 'textarea';
    }

    public function getDefaultOptions(): array
    {
        return array_merge(parent::getDefaultOptions(), [
            'rows' => 4,
            'cols' => null,
            'maxlength' => null,
            'minlength' => null,
            'show_word_count' => false,
            'show_char_count' => false,
            'auto_resize' => true,
            'strip_tags' => false,
            'nl2br_on_display' => true,
            'css_class' => 'form-textarea'
        ]);
    }

    public function renderDisplay($value, array $options = []): string
    {
        if ($this->isEmpty($value)) {
            return '<span class="text-gray-400">-</span>';
        }

        $options = array_merge($this->getDefaultOptions(), $options);
        
        // Strip tags if configured
        if ($options['strip_tags']) {
            $value = strip_tags($value);
        }

        // Apply nl2br if configured
        if ($options['nl2br_on_display']) {
            $value = nl2br($this->escapeValue($value));
        } else {
            $value = $this->escapeValue($value);
        }

        // Limit display length
        $maxDisplayLength = $options['display_max_length'] ?? 200;
        if (strlen(strip_tags($value)) > $maxDisplayLength) {
            $truncated = substr(strip_tags($value), 0, $maxDisplayLength) . '...';
            
            return '<div class="prose prose-sm max-w-none">'
                   . '<div class="text-gray-600">' . $truncated . '</div>'
                   . '<button onclick="toggleFullText(this)" class="text-indigo-600 hover:text-indigo-500 text-xs mt-1">Devamını göster</button>'
                   . '<div class="hidden full-text">' . $value . '</div>'
                   . '</div>';
        }

        return '<div class="prose prose-sm max-w-none text-gray-600">' . $value . '</div>';
    }

    public function renderFormInput(string $name, $value, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        $attributes = $this->renderAttributes($options);

        // Add textarea-specific attributes
        if ($options['rows'] !== null) {
            $attributes .= ' rows="' . (int)$options['rows'] . '"';
        }

        if ($options['cols'] !== null) {
            $attributes .= ' cols="' . (int)$options['cols'] . '"';
        }

        if ($options['maxlength'] !== null) {
            $attributes .= ' maxlength="' . (int)$options['maxlength'] . '"';
        }

        if ($options['minlength'] !== null) {
            $attributes .= ' minlength="' . (int)$options['minlength'] . '"';
        }

        $textareaId = $this->generateId($name);
        
        $html = $this->renderLabel($name, $options);
        $html .= $this->renderHelpText($options);
        
        $html .= '<div class="relative">';
        $html .= '<textarea name="' . $this->escapeValue($name) . '" ';
        $html .= 'id="' . $textareaId . '" ';
        $html .= $attributes;
        
        // Add auto-resize functionality
        if ($options['auto_resize']) {
            $html .= ' oninput="autoResize(this)"';
        }
        
        // Add character counting
        if ($options['show_char_count'] || $options['show_word_count']) {
            $html .= ' oninput="updateCounts(\'' . $textareaId . '\')"';
        }
        
        $html .= '>';
        $html .= $this->escapeValue($value ?? $options['default_value'] ?? '');
        $html .= '</textarea>';
        $html .= '</div>';

        // Add counters
        if ($options['show_char_count'] || $options['show_word_count']) {
            $html .= '<div class="flex justify-between items-center mt-1 text-xs text-gray-500">';
            
            if ($options['show_word_count']) {
                $html .= '<span id="' . $textareaId . '_word_count">0 kelime</span>';
            }
            
            if ($options['show_char_count']) {
                $charCount = strlen($value ?? '');
                $maxLength = $options['maxlength'];
                $html .= '<span id="' . $textareaId . '_char_count">';
                $html .= $charCount;
                if ($maxLength) {
                    $html .= '/' . $maxLength;
                }
                $html .= ' karakter</span>';
            }
            
            $html .= '</div>';
        }

        // Add JavaScript for functionality
        $html .= $this->renderJavaScript($textareaId, $options);

        return $html;
    }

    protected function renderJavaScript(string $textareaId, array $options): string
    {
        $js = '<script>';
        
        // Auto-resize function
        if ($options['auto_resize']) {
            $js .= '
            function autoResize(textarea) {
                textarea.style.height = "auto";
                textarea.style.height = (textarea.scrollHeight) + "px";
            }
            
            // Initialize auto-resize
            document.addEventListener("DOMContentLoaded", function() {
                const textarea = document.getElementById("' . $textareaId . '");
                if (textarea) {
                    autoResize(textarea);
                }
            });';
        }
        
        // Counter functions
        if ($options['show_char_count'] || $options['show_word_count']) {
            $js .= '
            function updateCounts(textareaId) {
                const textarea = document.getElementById(textareaId);
                const text = textarea.value;
                
                // Update character count
                const charCountEl = document.getElementById(textareaId + "_char_count");
                if (charCountEl) {
                    const maxLength = textarea.getAttribute("maxlength");
                    let charText = text.length;
                    if (maxLength) {
                        charText += "/" + maxLength;
                        
                        // Add warning if close to limit
                        if (text.length > maxLength * 0.9) {
                            charCountEl.classList.add("text-yellow-600");
                        } else {
                            charCountEl.classList.remove("text-yellow-600");
                        }
                        
                        // Add error if over limit
                        if (text.length >= maxLength) {
                            charCountEl.classList.add("text-red-600");
                        } else {
                            charCountEl.classList.remove("text-red-600");
                        }
                    }
                    charCountEl.textContent = charText + " karakter";
                }
                
                // Update word count
                const wordCountEl = document.getElementById(textareaId + "_word_count");
                if (wordCountEl) {
                    const words = text.trim() === "" ? 0 : text.trim().split(/\s+/).length;
                    wordCountEl.textContent = words + " kelime";
                }
            }
            
            // Initialize counters
            document.addEventListener("DOMContentLoaded", function() {
                updateCounts("' . $textareaId . '");
            });';
        }
        
        $js .= '</script>';
        
        return $js;
    }

    public function validate($value, array $options = []): array
    {
        $errors = parent::validate($value, $options);
        $options = array_merge($this->getDefaultOptions(), $options);

        if (!$this->isEmpty($value)) {
            // Min length validation
            if (!empty($options['minlength']) && strlen($value) < $options['minlength']) {
                $errors[] = ($options['label'] ?? 'Bu alan') . ' en az ' . $options['minlength'] . ' karakter olmalıdır.';
            }

            // Max length validation
            if (!empty($options['maxlength']) && strlen($value) > $options['maxlength']) {
                $errors[] = ($options['label'] ?? 'Bu alan') . ' en fazla ' . $options['maxlength'] . ' karakter olabilir.';
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

        // Strip tags if configured
        if ($options['strip_tags']) {
            $value = strip_tags($value);
        }

        // Trim whitespace
        $value = trim($value);

        // Apply max length if specified
        if (!empty($options['maxlength'])) {
            $value = substr($value, 0, $options['maxlength']);
        }

        return $value;
    }

    /**
     * Render with rich text editor (basic implementation)
     */
    public function renderRichTextInput(string $name, $value, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        $textareaId = $this->generateId($name);
        
        $html = $this->renderLabel($name, $options);
        $html .= $this->renderHelpText($options);
        
        // Rich text toolbar
        $html .= '<div class="border border-gray-300 rounded-t-md bg-gray-50 p-2 flex space-x-2">';
        $html .= '<button type="button" onclick="formatText(\'' . $textareaId . '\', \'bold\')" class="px-2 py-1 text-sm bg-white border rounded hover:bg-gray-100"><strong>B</strong></button>';
        $html .= '<button type="button" onclick="formatText(\'' . $textareaId . '\', \'italic\')" class="px-2 py-1 text-sm bg-white border rounded hover:bg-gray-100"><em>I</em></button>';
        $html .= '<button type="button" onclick="formatText(\'' . $textareaId . '\', \'link\')" class="px-2 py-1 text-sm bg-white border rounded hover:bg-gray-100">🔗</button>';
        $html .= '</div>';
        
        // Textarea
        $html .= '<textarea name="' . $this->escapeValue($name) . '" ';
        $html .= 'id="' . $textareaId . '" ';
        $html .= 'class="' . $options['css_class'] . ' rounded-t-none border-t-0" ';
        $html .= 'rows="' . ($options['rows'] ?? 6) . '">';
        $html .= $this->escapeValue($value ?? $options['default_value'] ?? '');
        $html .= '</textarea>';
        
        // Add rich text JavaScript
        $html .= '<script>';
        $html .= 'function formatText(textareaId, format) {';
        $html .= '  const textarea = document.getElementById(textareaId);';
        $html .= '  const start = textarea.selectionStart;';
        $html .= '  const end = textarea.selectionEnd;';
        $html .= '  const selectedText = textarea.value.substring(start, end);';
        $html .= '  let replacement = selectedText;';
        $html .= '  ';
        $html .= '  switch(format) {';
        $html .= '    case "bold":';
        $html .= '      replacement = "**" + selectedText + "**";';
        $html .= '      break;';
        $html .= '    case "italic":';
        $html .= '      replacement = "*" + selectedText + "*";';
        $html .= '      break;';
        $html .= '    case "link":';
        $html .= '      const url = prompt("URL girin:");';
        $html .= '      if (url) replacement = "[" + selectedText + "](" + url + ")";';
        $html .= '      break;';
        $html .= '  }';
        $html .= '  ';
        $html .= '  textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);';
        $html .= '  textarea.focus();';
        $html .= '}';
        $html .= '</script>';
        
        return $html;
    }

    /**
     * Get word count from text
     */
    public function getWordCount(string $text): int
    {
        $text = trim(strip_tags($text));
        return $text === '' ? 0 : count(preg_split('/\s+/', $text));
    }

    /**
     * Get reading time estimate (assuming 200 words per minute)
     */
    public function getReadingTime(string $text): string
    {
        $wordCount = $this->getWordCount($text);
        $minutes = ceil($wordCount / 200);
        
        if ($minutes < 1) {
            return '< 1 dakika';
        } elseif ($minutes == 1) {
            return '1 dakika';
        } else {
            return $minutes . ' dakika';
        }
    }
}
