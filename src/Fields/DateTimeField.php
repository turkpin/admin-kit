<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

class DateTimeField extends AbstractFieldType
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
        'format',
        'display_format',
        'min_date',
        'max_date',
        'timezone',
        'show_timezone',
        'timezone_options',
        'show_seconds',
        'step',
        'separate_fields',
        'time_format'
    ];

    public function getTypeName(): string
    {
        return 'datetime';
    }

    public function getDefaultOptions(): array
    {
        return array_merge(parent::getDefaultOptions(), [
            'format' => 'Y-m-d H:i:s', // Storage format
            'display_format' => 'd.m.Y H:i', // Display format
            'min_date' => null,
            'max_date' => null,
            'timezone' => 'Europe/Istanbul',
            'show_timezone' => false,
            'timezone_options' => [
                'Europe/Istanbul' => 'Türkiye',
                'UTC' => 'UTC',
                'Europe/London' => 'Londra',
                'America/New_York' => 'New York'
            ],
            'show_seconds' => false,
            'step' => 60, // seconds
            'separate_fields' => false,
            'time_format' => '24' // 12 or 24
        ]);
    }

    public function renderDisplay($value, array $options = []): string
    {
        if ($this->isEmpty($value)) {
            return '<span class="text-gray-400">-</span>';
        }

        $options = array_merge($this->getDefaultOptions(), $options);
        
        try {
            if ($value instanceof \DateTime) {
                $date = $value;
            } else {
                $date = new \DateTime($value);
            }
            
            // Set timezone if specified
            if ($options['timezone']) {
                $date->setTimezone(new \DateTimeZone($options['timezone']));
            }
            
            $formattedDate = $date->format($options['display_format']);
            
            // Add relative time
            $now = new \DateTime();
            $diff = $now->diff($date);
            $relativeTime = $this->getRelativeTime($diff, $date < $now);
            
            $html = '<time datetime="' . $date->format('c') . '" title="' . $relativeTime . '">';
            $html .= $this->escapeValue($formattedDate);
            $html .= '</time>';
            
            // Show timezone if enabled
            if ($options['show_timezone']) {
                $html .= ' <span class="text-gray-400 text-sm">(' . $date->format('T') . ')</span>';
            }
            
            return $html;
                   
        } catch (\Exception $e) {
            return '<span class="text-red-500">Geçersiz tarih/saat</span>';
        }
    }

    public function renderFormInput(string $name, $value, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        
        if ($options['separate_fields']) {
            return $this->renderSeparateFields($name, $value, $options);
        } else {
            return $this->renderSingleField($name, $value, $options);
        }
    }

    protected function renderSingleField(string $name, $value, array $options): string
    {
        $attributes = $this->renderAttributes($options);
        $datetimeId = $this->generateId($name);
        
        // Convert value to proper format for input
        $inputValue = '';
        if (!$this->isEmpty($value)) {
            try {
                if ($value instanceof \DateTime) {
                    $inputValue = $value->format('Y-m-d\TH:i' . ($options['show_seconds'] ? ':s' : ''));
                } else {
                    $date = new \DateTime($value);
                    $inputValue = $date->format('Y-m-d\TH:i' . ($options['show_seconds'] ? ':s' : ''));
                }
            } catch (\Exception $e) {
                // Invalid date, leave empty
            }
        }

        // Add datetime-specific attributes
        if ($options['min_date'] !== null) {
            if ($options['min_date'] instanceof \DateTime) {
                $attributes .= ' min="' . $options['min_date']->format('Y-m-d\TH:i') . '"';
            } else {
                $attributes .= ' min="' . $options['min_date'] . '"';
            }
        }

        if ($options['max_date'] !== null) {
            if ($options['max_date'] instanceof \DateTime) {
                $attributes .= ' max="' . $options['max_date']->format('Y-m-d\TH:i') . '"';
            } else {
                $attributes .= ' max="' . $options['max_date'] . '"';
            }
        }

        if ($options['step'] !== null) {
            $attributes .= ' step="' . $options['step'] . '"';
        }

        $html = $this->renderLabel($name, $options);
        $html .= $this->renderHelpText($options);
        
        $html .= '<div class="datetime-field-container">';
        $html .= '<input type="datetime-local" name="' . $this->escapeValue($name) . '" ';
        $html .= 'id="' . $datetimeId . '" ';
        $html .= 'value="' . $this->escapeValue($inputValue) . '" ';
        $html .= $attributes . '>';

        // Timezone selector
        if ($options['show_timezone']) {
            $html .= $this->renderTimezoneSelector($name . '_timezone', $options);
        }

        // Quick datetime buttons
        $html .= $this->renderQuickButtons($datetimeId, $options);
        
        $html .= '</div>';

        // Add JavaScript for enhanced functionality
        $html .= $this->renderJavaScript($datetimeId, $options);

        return $html;
    }

    protected function renderSeparateFields(string $name, $value, array $options): string
    {
        $dateValue = '';
        $timeValue = '';
        
        if (!$this->isEmpty($value)) {
            try {
                if ($value instanceof \DateTime) {
                    $date = $value;
                } else {
                    $date = new \DateTime($value);
                }
                $dateValue = $date->format('Y-m-d');
                $timeValue = $date->format('H:i' . ($options['show_seconds'] ? ':s' : ''));
            } catch (\Exception $e) {
                // Invalid date, leave empty
            }
        }

        $dateId = $this->generateId($name . '_date');
        $timeId = $this->generateId($name . '_time');

        $html = $this->renderLabel($name, $options);
        $html .= $this->renderHelpText($options);
        
        $html .= '<div class="datetime-separate-fields grid grid-cols-2 gap-4">';
        
        // Date field
        $html .= '<div>';
        $html .= '<label for="' . $dateId . '" class="block text-sm font-medium text-gray-700 mb-1">Tarih</label>';
        $html .= '<input type="date" name="' . $this->escapeValue($name . '_date') . '" ';
        $html .= 'id="' . $dateId . '" ';
        $html .= 'value="' . $this->escapeValue($dateValue) . '" ';
        $html .= 'class="' . ($options['css_class'] ?? 'form-input') . '">';
        $html .= '</div>';
        
        // Time field
        $html .= '<div>';
        $html .= '<label for="' . $timeId . '" class="block text-sm font-medium text-gray-700 mb-1">Saat</label>';
        $html .= '<input type="time" name="' . $this->escapeValue($name . '_time') . '" ';
        $html .= 'id="' . $timeId . '" ';
        $html .= 'value="' . $this->escapeValue($timeValue) . '" ';
        
        if ($options['step'] !== null) {
            $html .= 'step="' . $options['step'] . '" ';
        }
        
        $html .= 'class="' . ($options['css_class'] ?? 'form-input') . '">';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Hidden field to store combined value
        $html .= '<input type="hidden" name="' . $this->escapeValue($name) . '" id="' . $this->generateId($name) . '">';
        
        // JavaScript to combine values
        $html .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const dateField = document.getElementById("' . $dateId . '");
            const timeField = document.getElementById("' . $timeId . '");
            const hiddenField = document.getElementById("' . $this->generateId($name) . '");
            
            function updateHiddenField() {
                if (dateField.value && timeField.value) {
                    hiddenField.value = dateField.value + " " + timeField.value;
                } else {
                    hiddenField.value = "";
                }
            }
            
            dateField.addEventListener("change", updateHiddenField);
            timeField.addEventListener("change", updateHiddenField);
            
            // Initialize
            updateHiddenField();
        });
        </script>';

        return $html;
    }

    protected function renderTimezoneSelector(string $name, array $options): string
    {
        $html = '<div class="mt-2">';
        $html .= '<label class="block text-sm font-medium text-gray-700 mb-1">Saat Dilimi</label>';
        $html .= '<select name="' . $this->escapeValue($name) . '" class="form-select">';
        
        foreach ($options['timezone_options'] as $timezone => $label) {
            $selected = $timezone === $options['timezone'] ? 'selected' : '';
            $html .= '<option value="' . $this->escapeValue($timezone) . '" ' . $selected . '>';
            $html .= $this->escapeValue($label);
            $html .= '</option>';
        }
        
        $html .= '</select>';
        $html .= '</div>';
        
        return $html;
    }

    protected function renderQuickButtons(string $datetimeId, array $options): string
    {
        if ($options['readonly'] || $options['disabled']) {
            return '';
        }

        $html = '<div class="flex flex-wrap gap-1 mt-2">';
        
        // Now button
        $html .= '<button type="button" onclick="setQuickDateTime(\'' . $datetimeId . '\', \'now\')" ';
        $html .= 'class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">Şimdi</button>';
        
        // Start of day
        $html .= '<button type="button" onclick="setQuickDateTime(\'' . $datetimeId . '\', \'start_of_day\')" ';
        $html .= 'class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">Gün Başı</button>';
        
        // End of day
        $html .= '<button type="button" onclick="setQuickDateTime(\'' . $datetimeId . '\', \'end_of_day\')" ';
        $html .= 'class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">Gün Sonu</button>';
        
        // Clear button
        $html .= '<button type="button" onclick="setQuickDateTime(\'' . $datetimeId . '\', \'clear\')" ';
        $html .= 'class="px-2 py-1 text-xs bg-red-100 hover:bg-red-200 text-red-700 rounded">Temizle</button>';
        
        $html .= '</div>';
        
        return $html;
    }

    protected function renderJavaScript(string $datetimeId, array $options): string
    {
        return '<script>
        function setQuickDateTime(fieldId, type) {
            const field = document.getElementById(fieldId);
            const now = new Date();
            let targetDate;
            
            switch(type) {
                case "now":
                    targetDate = now;
                    break;
                case "start_of_day":
                    targetDate = new Date(now);
                    targetDate.setHours(0, 0, 0, 0);
                    break;
                case "end_of_day":
                    targetDate = new Date(now);
                    targetDate.setHours(23, 59, 59, 999);
                    break;
                case "clear":
                    field.value = "";
                    field.dispatchEvent(new Event("change"));
                    return;
            }
            
            if (targetDate) {
                // Format for datetime-local input
                const year = targetDate.getFullYear();
                const month = String(targetDate.getMonth() + 1).padStart(2, "0");
                const day = String(targetDate.getDate()).padStart(2, "0");
                const hours = String(targetDate.getHours()).padStart(2, "0");
                const minutes = String(targetDate.getMinutes()).padStart(2, "0");
                
                field.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                field.dispatchEvent(new Event("change"));
            }
        }
        
        // Format datetime display
        function formatDateTime(dateTimeString, format) {
            if (!dateTimeString) return "";
            
            const date = new Date(dateTimeString);
            const options = {
                year: "numeric",
                month: "2-digit", 
                day: "2-digit",
                hour: "2-digit",
                minute: "2-digit"
            };
            
            return date.toLocaleDateString("tr-TR", options);
        }
        </script>';
    }

    public function validate($value, array $options = []): array
    {
        $errors = parent::validate($value, $options);
        $options = array_merge($this->getDefaultOptions(), $options);

        if (!$this->isEmpty($value)) {
            try {
                if ($value instanceof \DateTime) {
                    $date = $value;
                } else {
                    $date = new \DateTime($value);
                }
                
                // Min date validation
                if ($options['min_date'] !== null) {
                    $minDate = $options['min_date'] instanceof \DateTime 
                        ? $options['min_date'] 
                        : new \DateTime($options['min_date']);
                        
                    if ($date < $minDate) {
                        $errors[] = ($options['label'] ?? 'Tarih/Saat') . ' ' . $minDate->format($options['display_format']) . ' tarihinden sonra olmalıdır.';
                    }
                }
                
                // Max date validation
                if ($options['max_date'] !== null) {
                    $maxDate = $options['max_date'] instanceof \DateTime 
                        ? $options['max_date'] 
                        : new \DateTime($options['max_date']);
                        
                    if ($date > $maxDate) {
                        $errors[] = ($options['label'] ?? 'Tarih/Saat') . ' ' . $maxDate->format($options['display_format']) . ' tarihinden önce olmalıdır.';
                    }
                }
                
            } catch (\Exception $e) {
                $errors[] = ($options['label'] ?? 'Tarih/Saat') . ' geçerli bir tarih/saat olmalıdır.';
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

        try {
            if ($value instanceof \DateTime) {
                $date = $value;
            } else {
                $date = new \DateTime($value);
            }
            
            // Set timezone if specified
            if ($options['timezone']) {
                $date->setTimezone(new \DateTimeZone($options['timezone']));
            }
            
            return $date;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getRelativeTime(\DateInterval $diff, bool $isPast): string
    {
        if ($diff->days === 0) {
            if ($diff->h === 0) {
                if ($diff->i === 0) {
                    return 'Şimdi';
                } else {
                    return $isPast 
                        ? $diff->i . ' dakika önce' 
                        : $diff->i . ' dakika sonra';
                }
            } else {
                return $isPast 
                    ? $diff->h . ' saat önce' 
                    : $diff->h . ' saat sonra';
            }
        } elseif ($diff->days === 1) {
            return $isPast ? 'Dün' : 'Yarın';
        } elseif ($diff->days < 7) {
            return $isPast 
                ? $diff->days . ' gün önce' 
                : $diff->days . ' gün sonra';
        } elseif ($diff->days < 30) {
            $weeks = floor($diff->days / 7);
            return $isPast 
                ? $weeks . ' hafta önce' 
                : $weeks . ' hafta sonra';
        } elseif ($diff->days < 365) {
            $months = floor($diff->days / 30);
            return $isPast 
                ? $months . ' ay önce' 
                : $months . ' ay sonra';
        } else {
            $years = floor($diff->days / 365);
            return $isPast 
                ? $years . ' yıl önce' 
                : $years . ' yıl sonra';
        }
    }

    /**
     * Convert between timezones
     */
    public function convertTimezone(\DateTime $date, string $fromTimezone, string $toTimezone): \DateTime
    {
        $date->setTimezone(new \DateTimeZone($fromTimezone));
        $date->setTimezone(new \DateTimeZone($toTimezone));
        return $date;
    }

    /**
     * Get business hours check
     */
    public function isBusinessHours(\DateTime $date, array $businessHours = []): bool
    {
        $defaultBusinessHours = [
            'start' => '09:00',
            'end' => '17:00',
            'days' => [1, 2, 3, 4, 5] // Monday to Friday
        ];
        
        $hours = array_merge($defaultBusinessHours, $businessHours);
        
        // Check if it's a business day
        $dayOfWeek = (int)$date->format('N');
        if (!in_array($dayOfWeek, $hours['days'])) {
            return false;
        }
        
        // Check if it's within business hours
        $time = $date->format('H:i');
        return $time >= $hours['start'] && $time <= $hours['end'];
    }

    /**
     * Round to nearest interval
     */
    public function roundToInterval(\DateTime $date, int $intervalMinutes): \DateTime
    {
        $minutes = (int)$date->format('i');
        $roundedMinutes = round($minutes / $intervalMinutes) * $intervalMinutes;
        
        $newDate = clone $date;
        $newDate->setTime((int)$date->format('H'), $roundedMinutes, 0);
        
        return $newDate;
    }
}
