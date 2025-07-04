{* Shared Form Template for Create/Edit *}

<form method="{$form_method|default:'POST'}" action="{$form_action}" enctype="multipart/form-data" 
      class="space-y-6" data-validate="true">
    
    {* CSRF Protection *}
    {if $config.csrf_protection|default:true}
        <input type="hidden" name="_token" value="{$csrf_token|default:''}">
    {/if}

    {* Error Messages *}
    {if isset($errors) && count($errors) > 0}
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <div class="font-medium">Aşağıdaki hatalar düzeltilmelidir:</div>
            <ul class="mt-2 list-disc list-inside text-sm">
                {foreach $errors as $error}
                    <li>{$error}</li>
                {/foreach}
            </ul>
        </div>
    {/if}

    {* Form Fields *}
    <div class="grid grid-cols-1 gap-6">
        {foreach $entity_config.fields as $field_name => $field_config}
            {if !($field_config.form_hidden|default:false)}
                <div class="form-group {if $field_config.required|default:false}required{/if}">
                    {* Field Label *}
                    <label for="{$field_name}" class="form-label">
                        {$field_config.label|default:$field_name}
                        {if $field_config.required|default:false}
                            <span class="text-red-500 ml-1">*</span>
                        {/if}
                    </label>

                    {* Field Help Text *}
                    {if isset($field_config.help)}
                        <p class="text-sm text-gray-500 mb-1">{$field_config.help}</p>
                    {/if}

                    {* Get current value *}
                    {if $entity}
                        {assign var="getter" value="get{$field_name|ucfirst}"}
                        {assign var="current_value" value=$entity->$getter()}
                    {else}
                        {assign var="current_value" value=$form_data[$field_name]|default:''}
                    {/if}

                    {* Render field based on type *}
                    {if $field_config.type == 'text'}
                        <input type="text" 
                               id="{$field_name}" 
                               name="{$field_name}" 
                               value="{$current_value|escape}" 
                               class="form-input"
                               {if $field_config.required|default:false}required{/if}
                               {if isset($field_config.maxlength)}maxlength="{$field_config.maxlength}"{/if}
                               {if isset($field_config.placeholder)}placeholder="{$field_config.placeholder}"{/if}>

                    {elseif $field_config.type == 'email'}
                        <input type="email" 
                               id="{$field_name}" 
                               name="{$field_name}" 
                               value="{$current_value|escape}" 
                               class="form-input"
                               {if $field_config.required|default:false}required{/if}
                               placeholder="{$field_config.placeholder|default:'ornek@email.com'}">

                    {elseif $field_config.type == 'password'}
                        <input type="password" 
                               id="{$field_name}" 
                               name="{$field_name}" 
                               class="form-input"
                               {if $field_config.required|default:false}required{/if}
                               {if isset($field_config.minlength)}minlength="{$field_config.minlength}"{/if}
                               placeholder="{$field_config.placeholder|default:'Şifre girin'}">
                        {if $field_name == 'password' && $entity}
                            <p class="text-sm text-gray-500 mt-1">Değiştirmek istemiyorsanız boş bırakın</p>
                        {/if}

                    {elseif $field_config.type == 'number'}
                        <input type="number" 
                               id="{$field_name}" 
                               name="{$field_name}" 
                               value="{$current_value}" 
                               class="form-input"
                               {if $field_config.required|default:false}required{/if}
                               {if isset($field_config.min)}min="{$field_config.min}"{/if}
                               {if isset($field_config.max)}max="{$field_config.max}"{/if}
                               {if isset($field_config.step)}step="{$field_config.step}"{/if}>

                    {elseif $field_config.type == 'textarea'}
                        <textarea id="{$field_name}" 
                                  name="{$field_name}" 
                                  class="form-textarea"
                                  rows="{$field_config.rows|default:4}"
                                  {if $field_config.required|default:false}required{/if}
                                  {if isset($field_config.placeholder)}placeholder="{$field_config.placeholder}"{/if}>{$current_value|escape}</textarea>

                    {elseif $field_config.type == 'boolean'}
                        <div class="flex items-center">
                            <label class="toggle-switch {if $current_value}checked{/if}">
                                <input type="checkbox" 
                                       id="{$field_name}" 
                                       name="{$field_name}" 
                                       value="1"
                                       {if $current_value}checked{/if}
                                       class="sr-only">
                                <span class="toggle-switch-thumb"></span>
                            </label>
                            <span class="ml-3 text-sm text-gray-700">
                                {$field_config.label|default:$field_name} aktif
                            </span>
                        </div>

                    {elseif $field_config.type == 'choice'}
                        <select id="{$field_name}" 
                                name="{$field_name}{if $field_config.multiple|default:false}[]{/if}" 
                                class="form-select"
                                {if $field_config.multiple|default:false}multiple{/if}
                                {if $field_config.required|default:false}required{/if}>
                            {if !($field_config.multiple|default:false) && !($field_config.required|default:false)}
                                <option value="">Seçiniz...</option>
                            {/if}
                            {foreach $field_config.choices as $choice_value => $choice_label}
                                <option value="{$choice_value}" 
                                        {if $field_config.multiple|default:false}
                                            {if is_array($current_value) && in_array($choice_value, $current_value)}selected{/if}
                                        {else}
                                            {if $current_value == $choice_value}selected{/if}
                                        {/if}>
                                    {$choice_label}
                                </option>
                            {/foreach}
                        </select>

                    {elseif $field_config.type == 'date'}
                        <input type="date" 
                               id="{$field_name}" 
                               name="{$field_name}" 
                               value="{if $current_value}{$current_value|date_format:'Y-m-d'}{/if}" 
                               class="form-input"
                               {if $field_config.required|default:false}required{/if}>

                    {elseif $field_config.type == 'datetime'}
                        <input type="datetime-local" 
                               id="{$field_name}" 
                               name="{$field_name}" 
                               value="{if $current_value}{$current_value|date_format:'Y-m-d\TH:i'}{/if}" 
                               class="form-input"
                               {if $field_config.required|default:false}required{/if}>

                    {elseif $field_config.type == 'image'}
                        <div class="space-y-3">
                            {if $current_value}
                                <div class="flex items-center space-x-4">
                                    <img src="{$current_value}" alt="Current image" class="w-20 h-20 object-cover rounded">
                                    <div>
                                        <p class="text-sm text-gray-600">Mevcut resim</p>
                                        <label class="inline-flex items-center mt-1">
                                            <input type="checkbox" name="remove_{$field_name}" value="1" class="form-checkbox">
                                            <span class="ml-2 text-sm text-red-600">Resmi sil</span>
                                        </label>
                                    </div>
                                </div>
                            {/if}
                            
                            <div class="file-upload-area" onclick="document.getElementById('{$field_name}').click()">
                                <input type="file" 
                                       id="{$field_name}" 
                                       name="{$field_name}" 
                                       accept="image/*"
                                       class="hidden"
                                       {if $field_config.required|default:false && !$current_value}required{/if}>
                                <div class="text-center">
                                    {icon name="image" class="w-8 h-8 mx-auto text-gray-400"}
                                    <p class="mt-2 text-sm text-gray-600">
                                        Resim seçmek için tıklayın veya sürükleyip bırakın
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        PNG, JPG, GIF desteklenir
                                    </p>
                                </div>
                                <div class="file-preview mt-3"></div>
                            </div>
                        </div>

                    {elseif $field_config.type == 'file'}
                        <div class="space-y-3">
                            {if $current_value}
                                <div class="flex items-center space-x-4">
                                    <div class="flex items-center space-x-2">
                                        {icon name="document" class="w-5 h-5 text-gray-400"}
                                        <a href="{$current_value}" target="_blank" class="text-indigo-600 hover:text-indigo-500">
                                            Mevcut dosya
                                        </a>
                                    </div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="remove_{$field_name}" value="1" class="form-checkbox">
                                        <span class="ml-2 text-sm text-red-600">Dosyayı sil</span>
                                    </label>
                                </div>
                            {/if}
                            
                            <div class="file-upload-area" onclick="document.getElementById('{$field_name}').click()">
                                <input type="file" 
                                       id="{$field_name}" 
                                       name="{$field_name}" 
                                       class="hidden"
                                       {if isset($field_config.accept)}accept="{$field_config.accept}"{/if}
                                       {if $field_config.required|default:false && !$current_value}required{/if}>
                                <div class="text-center">
                                    {icon name="document" class="w-8 h-8 mx-auto text-gray-400"}
                                    <p class="mt-2 text-sm text-gray-600">
                                        Dosya seçmek için tıklayın veya sürükleyip bırakın
                                    </p>
                                    {if isset($field_config.max_size)}
                                        <p class="text-xs text-gray-500 mt-1">
                                            Maksimum dosya boyutu: {$field_config.max_size}
                                        </p>
                                    {/if}
                                </div>
                                <div class="file-preview mt-3"></div>
                            </div>
                        </div>

                    {elseif $field_config.type == 'money'}
                        <div class="relative">
                            <input type="number" 
                                   id="{$field_name}" 
                                   name="{$field_name}" 
                                   value="{$current_value}" 
                                   class="form-input pr-12"
                                   step="0.01"
                                   {if $field_config.required|default:false}required{/if}
                                   {if isset($field_config.min)}min="{$field_config.min}"{/if}>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">
                                    {$field_config.currency|default:'TL'}
                                </span>
                            </div>
                        </div>

                    {elseif $field_config.type == 'association'}
                        {* Bu kısım ileride geliştirilecek - entity ilişkileri için *}
                        <select id="{$field_name}" 
                                name="{$field_name}{if $field_config.multiple|default:false}[]{/if}" 
                                class="form-select"
                                {if $field_config.multiple|default:false}multiple{/if}
                                {if $field_config.required|default:false}required{/if}>
                            {if !($field_config.multiple|default:false) && !($field_config.required|default:false)}
                                <option value="">İlişki seçiniz...</option>
                            {/if}
                            {* Association options will be populated by controller *}
                            {if isset($field_config.options)}
                                {foreach $field_config.options as $option_value => $option_label}
                                    <option value="{$option_value}" 
                                            {if $field_config.multiple|default:false}
                                                {if is_array($current_value) && in_array($option_value, $current_value)}selected{/if}
                                            {else}
                                                {if $current_value && $current_value->getId() == $option_value}selected{/if}
                                            {/if}>
                                        {$option_label}
                                    </option>
                                {/foreach}
                            {/if}
                        </select>

                    {else}
                        {* Default to text input *}
                        <input type="text" 
                               id="{$field_name}" 
                               name="{$field_name}" 
                               value="{$current_value|escape}" 
                               class="form-input"
                               {if $field_config.required|default:false}required{/if}
                               {if isset($field_config.placeholder)}placeholder="{$field_config.placeholder}"{/if}>
                    {/if}

                    {* Field Error Display *}
                    <div class="field-error-container"></div>
                </div>
            {/if}
        {/foreach}
    </div>

    {* Form Actions *}
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
        <a href="{url route=$entity_name}" class="btn btn-secondary">
            {icon name="arrow-left" class="w-4 h-4 mr-2"}
            Geri Dön
        </a>
        
        <div class="flex space-x-3">
            <button type="button" onclick="resetForm()" class="btn btn-secondary">
                Sıfırla
            </button>
            
            <button type="submit" class="btn btn-primary">
                {icon name="save" class="w-4 h-4 mr-2"}
                {if $entity}Güncelle{else}Kaydet{/if}
            </button>
        </div>
    </div>
</form>

<script>
function resetForm() {
    if (confirm('Formdaki tüm değişiklikleri sıfırlamak istediğinizden emin misiniz?')) {
        document.querySelector('form').reset();
        
        // Toggle switches'leri sıfırla
        document.querySelectorAll('.toggle-switch').forEach(toggle => {
            const checkbox = toggle.querySelector('input[type="checkbox"]');
            toggle.classList.toggle('checked', checkbox.checked);
        });
        
        // File previews'ları temizle
        document.querySelectorAll('.file-preview').forEach(preview => {
            preview.innerHTML = '';
        });
    }
}

// Form validation enhancement
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[data-validate="true"]');
    if (!form) return;
    
    // Real-time validation
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            // Clear previous errors
            clearFieldError(this);
        });
    });
    
    function validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        let error = null;
        
        // Required validation
        if (field.hasAttribute('required') && !value) {
            error = 'Bu alan gereklidir.';
        }
        
        // Email validation
        else if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                error = 'Geçerli bir e-posta adresi girin.';
            }
        }
        
        // Number validation
        else if (field.type === 'number' && value) {
            const min = field.getAttribute('min');
            const max = field.getAttribute('max');
            const numValue = parseFloat(value);
            
            if (min && numValue < parseFloat(min)) {
                error = `Değer ${min} değerinden küçük olamaz.`;
            } else if (max && numValue > parseFloat(max)) {
                error = `Değer ${max} değerinden büyük olamaz.`;
            }
        }
        
        if (error) {
            showFieldError(field, error);
            return false;
        } else {
            clearFieldError(field);
            return true;
        }
    }
    
    function showFieldError(field, message) {
        clearFieldError(field);
        
        field.classList.add('border-red-500', 'ring-red-500');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error text-red-600 text-sm mt-1';
        errorDiv.textContent = message;
        
        const container = field.closest('.form-group').querySelector('.field-error-container');
        container.appendChild(errorDiv);
    }
    
    function clearFieldError(field) {
        field.classList.remove('border-red-500', 'ring-red-500');
        
        const container = field.closest('.form-group').querySelector('.field-error-container');
        container.innerHTML = '';
    }
    
    // Form submit validation
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            const firstError = form.querySelector('.field-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
});
</script>
