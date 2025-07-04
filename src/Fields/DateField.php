<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

class DateField extends AbstractFieldType
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
        'locale',
        'first_day_of_week',
        'show_week_numbers',
        'disable_weekends',
        'disabled_dates',
        'allowed_dates'
    ];

    public function getTypeName(): string
    {
        return 'date';
    }

    public function getDefaultOptions(): array
    {
        return array_merge(parent::getDefaultOptions(), [
            'format' => 'Y-m-d', // Storage format
            'display_format' => 'd.m.Y', // Display format
            'min_date' => null,
            'max_date' => null,
            'locale' => 'tr',
            'first_day_of_week' => 1, // Monday
            'show_week_numbers' => false,
            'disable_weekends' => false,
            'disabled_dates' => [],
            'allowed_dates' => []
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
            
            $formattedDate = $date->format($options['display_format']);
            
            // Add relative time
            $now = new \DateTime();
            $diff = $now->diff($date);
            $relativeTime = $this->getRelativeTime($diff, $date < $now);
            
            return '<time datetime="' . $date->format('Y-m-d') . '" title="' . $relativeTime . '">'
                   . $this->escapeValue($formattedDate) . '</time>';
                   
        } catch (\Exception $e) {
            return '<span class="text-red-500">Geçersiz tarih</span>';
        }
    }

    public function renderFormInput(string $name, $value, array $options = []): string
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        $attributes = $this->renderAttributes($options);
        
        // Convert value to proper format for input
        $inputValue = '';
        if (!$this->isEmpty($value)) {
            try {
                if ($value instanceof \DateTime) {
                    $inputValue = $value->format('Y-m-d');
                } else {
                    $date = new \DateTime($value);
                    $inputValue = $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Invalid date, leave empty
            }
        }

        // Add date-specific attributes
        if ($options['min_date'] !== null) {
            if ($options['min_date'] instanceof \DateTime) {
                $attributes .= ' min="' . $options['min_date']->format('Y-m-d') . '"';
            } else {
                $attributes .= ' min="' . $options['min_date'] . '"';
            }
        }

        if ($options['max_date'] !== null) {
            if ($options['max_date'] instanceof \DateTime) {
                $attributes .= ' max="' . $options['max_date']->format('Y-m-d') . '"';
            } else {
                $attributes .= ' max="' . $options['max_date'] . '"';
            }
        }

        $dateId = $this->generateId($name);
        
        $html = $this->renderLabel($name, $options);
        $html .= $this->renderHelpText($options);
        
        $html .= '<div class="relative">';
        $html .= '<input type="date" name="' . $this->escapeValue($name) . '" ';
        $html .= 'id="' . $dateId . '" ';
        $html .= 'value="' . $this->escapeValue($inputValue) . '" ';
        $html .= $attributes . '>';
        
        // Add calendar icon
        $html .= '<div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">';
        $html .= '<svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>';
        $html .= '</svg>';
        $html .= '</div>';
        $html .= '</div>';

        // Add quick date buttons
        $html .= $this->renderQuickDateButtons($dateId, $options);
        
        // Add JavaScript for enhanced functionality
        $html .= $this->renderJavaScript($dateId, $options);

        return $html;
    }

    protected function renderQuickDateButtons(string $dateId, array $options): string
    {
        if ($options['readonly'] || $options['disabled']) {
            return '';
        }

        $html = '<div class="flex flex-wrap gap-1 mt-2">';
        
        // Today button
        $html .= '<button type="button" onclick="setQuickDate(\'' . $dateId . '\', \'today\')" ';
        $html .= 'class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">Bugün</button>';
        
        // Tomorrow button
        $html .= '<button type="button" onclick="setQuickDate(\'' . $dateId . '\', \'tomorrow\')" ';
        $html .= 'class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">Yarın</button>';
        
        // Next week button
        $html .= '<button type="button" onclick="setQuickDate(\'' . $dateId . '\', \'next_week\')" ';
        $html .= 'class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">Gelecek hafta</button>';
        
        // Clear button
        $html .= '<button type="button" onclick="setQuickDate(\'' . $dateId . '\', \'clear\')" ';
        $html .= 'class="px-2 py-1 text-xs bg-red-100 hover:bg-red-200 text-red-700 rounded">Temizle</button>';
        
        $html .= '</div>';
        
        return $html;
    }

    protected function renderJavaScript(string $dateId, array $options): string
    {
        $js = '<script>';
        
        // Quick date function
        $js .= '
        function setQuickDate(fieldId, type) {
            const field = document.getElementById(fieldId);
            const today = new Date();
            let targetDate;
            
            switch(type) {
                case "today":
                    targetDate = today;
                    break;
                case "tomorrow":
                    targetDate = new Date(today);
                    targetDate.setDate(today.getDate() + 1);
                    break;
                case "next_week":
                    targetDate = new Date(today);
                    targetDate.setDate(today.getDate() + 7);
                    break;
                case "clear":
                    field.value = "";
                    field.dispatchEvent(new Event("change"));
                    return;
            }
            
            if (targetDate) {
                field.value = targetDate.toISOString().split("T")[0];
                field.dispatchEvent(new Event("change"));
            }
        }';
        
        // Validation for disabled dates
        if (!empty($options['disabled_dates']) || $options['disable_weekends']) {
            $js .= '
            document.getElementById("' . $dateId . '").addEventListener("change", function() {
                const selectedDate = new Date(this.value);
                const dayOfWeek = selectedDate.getDay();
                
                // Check weekends
                if (' . ($options['disable_weekends'] ? 'true' : 'false') . ' && (dayOfWeek === 0 || dayOfWeek === 6)) {
                    alert("Hafta sonları seçilemez.");
                    this.value = "";
                    return;
                }
                
                // Check disabled dates
                const disabledDates = ' . json_encode($options['disabled_dates']) . ';
                const selectedDateStr = this.value;
                if (disabledDates.includes(selectedDateStr)) {
                    alert("Bu tarih seçilemez.");
                    this.value = "";
                    return;
                }
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
                        $errors[] = ($options['label'] ?? 'Tarih') . ' ' . $minDate->format($options['display_format']) . ' tarihinden sonra olmalıdır.';
                    }
                }
                
                // Max date validation
                if ($options['max_date'] !== null) {
                    $maxDate = $options['max_date'] instanceof \DateTime 
                        ? $options['max_date'] 
                        : new \DateTime($options['max_date']);
                        
                    if ($date > $maxDate) {
                        $errors[] = ($options['label'] ?? 'Tarih') . ' ' . $maxDate->format($options['display_format']) . ' tarihinden önce olmalıdır.';
                    }
                }
                
                // Weekend validation
                if ($options['disable_weekends']) {
                    $dayOfWeek = (int)$date->format('w');
                    if ($dayOfWeek === 0 || $dayOfWeek === 6) {
                        $errors[] = ($options['label'] ?? 'Tarih') . ' hafta sonu olamaz.';
                    }
                }
                
                // Disabled dates validation
                if (!empty($options['disabled_dates'])) {
                    $dateStr = $date->format('Y-m-d');
                    if (in_array($dateStr, $options['disabled_dates'])) {
                        $errors[] = ($options['label'] ?? 'Tarih') . ' seçilemez: ' . $date->format($options['display_format']);
                    }
                }
                
                // Allowed dates validation (if specified, only these dates are allowed)
                if (!empty($options['allowed_dates'])) {
                    $dateStr = $date->format('Y-m-d');
                    if (!in_array($dateStr, $options['allowed_dates'])) {
                        $errors[] = ($options['label'] ?? 'Tarih') . ' geçerli tarihlerden biri olmalıdır.';
                    }
                }
                
            } catch (\Exception $e) {
                $errors[] = ($options['label'] ?? 'Tarih') . ' geçerli bir tarih olmalıdır.';
            }
        }

        return $errors;
    }

    public function processFormValue($value, array $options = [])
    {
        if ($this->isEmpty($value)) {
            return null;
        }

        try {
            if ($value instanceof \DateTime) {
                return $value;
            } else {
                return new \DateTime($value);
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getRelativeTime(\DateInterval $diff, bool $isPast): string
    {
        if ($diff->days === 0) {
            return 'Bugün';
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
     * Get Turkish day name
     */
    public function getTurkishDayName(\DateTime $date): string
    {
        $days = [
            'Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 
            'Perşembe', 'Cuma', 'Cumartesi'
        ];
        
        return $days[(int)$date->format('w')];
    }

    /**
     * Get Turkish month name
     */
    public function getTurkishMonthName(\DateTime $date): string
    {
        $months = [
            'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
            'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'
        ];
        
        return $months[(int)$date->format('n') - 1];
    }

    /**
     * Check if date is a holiday (Turkish national holidays)
     */
    public function isHoliday(\DateTime $date): bool
    {
        $holidays = [
            '01-01', // New Year
            '04-23', // National Sovereignty Day
            '05-01', // Labor Day
            '05-19', // Commemoration of Atatürk
            '07-15', // Democracy Day
            '08-30', // Victory Day
            '10-29', // Republic Day
        ];
        
        return in_array($date->format('m-d'), $holidays);
    }
}
