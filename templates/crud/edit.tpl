{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{$entity_config.title} Düzenle</h1>
                <p class="text-gray-600 mt-1">
                    Kayıt #{$entity->getId()} düzenleniyor
                    {if method_exists($entity, 'getUpdatedAt') && $entity->getUpdatedAt()}
                        <span class="text-gray-400">
                            • Son güncelleme: {$entity->getUpdatedAt()|date_format:'d.m.Y H:i'}
                        </span>
                    {/if}
                </p>
            </div>
            
            <!-- Actions -->
            <div class="flex space-x-3">
                {if in_array('show', $entity_config.actions)}
                <a href="{url route="{$entity_name}/{$entity->getId()}"}" class="btn btn-secondary">
                    {icon name="eye" class="w-4 h-4 mr-2"}
                    Görüntüle
                </a>
                {/if}
                
                <button onclick="toggleHistory()" class="btn btn-secondary">
                    {icon name="clock" class="w-4 h-4 mr-2"}
                    Geçmiş
                </button>
            </div>
        </div>
    </div>

    <!-- Change History Panel -->
    <div id="historyPanel" class="hidden bg-gray-50 border border-gray-200 rounded-lg p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                {icon name="clock" class="w-5 h-5 text-gray-400 mt-0.5"}
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-gray-900">Değişiklik Geçmişi</h3>
                <div class="mt-2 text-sm text-gray-600">
                    {if method_exists($entity, 'getCreatedAt') && $entity->getCreatedAt()}
                        <div class="flex items-center justify-between py-2 border-b border-gray-200">
                            <span>Kayıt oluşturuldu</span>
                            <span class="text-gray-500">{$entity->getCreatedAt()|date_format:'d.m.Y H:i'}</span>
                        </div>
                    {/if}
                    
                    {if method_exists($entity, 'getUpdatedAt') && $entity->getUpdatedAt()}
                        <div class="flex items-center justify-between py-2">
                            <span>Son güncelleme</span>
                            <span class="text-gray-500">{$entity->getUpdatedAt()|date_format:'d.m.Y H:i'}</span>
                        </div>
                    {/if}
                    
                    {* Bu kısım ileride audit log sistemi ile geliştirilecek *}
                    <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                        <p class="text-blue-700 text-xs">
                            💡 Detaylı değişiklik geçmişi ileride audit log sistemi ile eklenecektir.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparison Panel (if there are unsaved changes) -->
    <div id="comparisonPanel" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                {icon name="exclamation-triangle" class="w-5 h-5 text-yellow-400 mt-0.5"}
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Kaydedilmemiş Değişiklikler</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>Formda kaydedilmemiş değişiklikler var. Sayfayı kapatmadan önce kaydetmeyi unutmayın.</p>
                    <div class="mt-3 flex space-x-3">
                        <button onclick="saveChanges()" class="text-yellow-800 hover:text-yellow-900 font-medium">
                            Şimdi Kaydet
                        </button>
                        <button onclick="discardChanges()" class="text-yellow-600 hover:text-yellow-700">
                            Değişiklikleri İptal Et
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900">Kayıt Bilgilerini Düzenle</h3>
            <p class="text-sm text-gray-600 mt-1">
                Değiştirmek istediğiniz alanları güncelleyin ve kaydet butonuna tıklayın.
            </p>
        </div>
        
        {include file="crud/_form.tpl"}
    </div>

    <!-- Danger Zone -->
    {if in_array('delete', $entity_config.actions)}
    <div class="bg-white rounded-lg shadow border-l-4 border-red-400 p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                {icon name="exclamation-triangle" class="w-5 h-5 text-red-400 mt-0.5"}
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-red-800">Tehlikeli İşlemler</h3>
                <div class="mt-2 text-sm text-red-700">
                    <p>Bu bölümdeki işlemler geri alınamaz. Dikkatli olun.</p>
                </div>
                <div class="mt-4">
                    <button onclick="deleteEntity()" class="btn btn-danger btn-sm">
                        {icon name="delete" class="w-4 h-4 mr-2"}
                        Kaydı Sil
                    </button>
                </div>
            </div>
        </div>
    </div>
    {/if}

    <!-- Quick Actions -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">⚡ Hızlı İşlemler</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <h5 class="font-medium text-gray-700 mb-2">Klavye Kısayolları</h5>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li><kbd>Ctrl + S</kbd> - Kaydet</li>
                    <li><kbd>Ctrl + Z</kbd> - Geri al</li>
                    <li><kbd>Esc</kbd> - İptal et</li>
                </ul>
            </div>
            <div>
                <h5 class="font-medium text-gray-700 mb-2">Eylemler</h5>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li><button onclick="duplicateEntity()" class="text-indigo-600 hover:text-indigo-500">📋 Kopyala</button></li>
                    <li><button onclick="exportEntity()" class="text-indigo-600 hover:text-indigo-500">📤 Dışa Aktar</button></li>
                    <li><button onclick="printEntity()" class="text-indigo-600 hover:text-indigo-500">🖨️ Yazdır</button></li>
                </ul>
            </div>
            <div>
                <h5 class="font-medium text-gray-700 mb-2">Son Aktivite</h5>
                <div class="text-sm text-gray-600">
                    {if method_exists($entity, 'getUpdatedAt') && $entity->getUpdatedAt()}
                        <p>Son güncelleme: {$entity->getUpdatedAt()|date_format:'d.m.Y H:i'}</p>
                    {else}
                        <p>Henüz güncellenmemiş</p>
                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>
{/block}

{block name="scripts"}
<script>
let originalFormData = new FormData(document.querySelector('form'));
let hasUnsavedChanges = false;

// History panel toggle
function toggleHistory() {
    const historyPanel = document.getElementById('historyPanel');
    historyPanel.classList.toggle('hidden');
}

// Track form changes
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    // Store original values
    const originalValues = {};
    inputs.forEach(input => {
        if (input.type === 'checkbox') {
            originalValues[input.name] = input.checked;
        } else {
            originalValues[input.name] = input.value;
        }
    });
    
    // Monitor changes
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            checkForChanges(this, originalValues);
        });
        
        input.addEventListener('change', function() {
            checkForChanges(this, originalValues);
        });
    });
    
    function checkForChanges(changedInput, originalValues) {
        let currentValue;
        if (changedInput.type === 'checkbox') {
            currentValue = changedInput.checked;
        } else {
            currentValue = changedInput.value;
        }
        
        const hasChanged = currentValue !== originalValues[changedInput.name];
        
        if (hasChanged && !hasUnsavedChanges) {
            hasUnsavedChanges = true;
            showComparisonPanel();
        }
        
        // Update change indicator
        const formGroup = changedInput.closest('.form-group');
        if (hasChanged) {
            formGroup.classList.add('has-changes');
            changedInput.classList.add('border-yellow-400');
        } else {
            formGroup.classList.remove('has-changes');
            changedInput.classList.remove('border-yellow-400');
        }
    }
});

function showComparisonPanel() {
    const panel = document.getElementById('comparisonPanel');
    panel.classList.remove('hidden');
}

function hideComparisonPanel() {
    const panel = document.getElementById('comparisonPanel');
    panel.classList.add('hidden');
}

function saveChanges() {
    document.querySelector('form').submit();
}

function discardChanges() {
    if (confirm('Tüm değişiklikleri iptal etmek istediğinizden emin misiniz?')) {
        location.reload();
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + S to save
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        saveChanges();
    }
    
    // Ctrl + Z to reload (undo)
    if (e.ctrlKey && e.key === 'z') {
        e.preventDefault();
        if (hasUnsavedChanges) {
            discardChanges();
        }
    }
    
    // Esc to go back
    if (e.key === 'Escape') {
        if (hasUnsavedChanges) {
            if (confirm('Kaydedilmemiş değişiklikler var. Çıkmak istediğinizden emin misiniz?')) {
                window.location.href = '{url route=$entity_name}';
            }
        } else {
            window.location.href = '{url route=$entity_name}';
        }
    }
});

// Prevent accidental page leave
window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = 'Kaydedilmemiş değişiklikler var. Sayfayı kapatmak istediğinizden emin misiniz?';
        return e.returnValue;
    }
});

// Quick actions
function deleteEntity() {
    const entityName = '{$entity_config.title}';
    const entityId = '{$entity->getId()}';
    
    if (confirm(`Bu ${entityName} kaydını silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!`)) {
        if (confirm('Son uyarı: Kayıt kalıcı olarak silinecektir. Devam etmek istediğinizden emin misiniz?')) {
            fetch(`{url route=$entity_name}/${entityId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(response => {
                if (response.ok) {
                    window.location.href = '{url route=$entity_name}?success=deleted';
                } else {
                    alert('Silme işlemi başarısız oldu.');
                }
            }).catch(error => {
                alert('Bir hata oluştu.');
            });
        }
    }
}

function duplicateEntity() {
    if (confirm('Bu kaydın bir kopyasını oluşturmak istediğinizden emin misiniz?')) {
        // Form verilerini al ve yeni kayıt sayfasına gönder
        const formData = new FormData(document.querySelector('form'));
        const params = new URLSearchParams();
        
        for (let [key, value] of formData.entries()) {
            if (key !== '_token' && key !== 'id') {
                params.append(key, value);
            }
        }
        
        window.location.href = '{url route="{$entity_name}/new"}?' + params.toString();
    }
}

function exportEntity() {
    window.location.href = '{url route="{$entity_name}/{$entity->getId()}/export"}';
}

function printEntity() {
    window.open('{url route="{$entity_name}/{$entity->getId()}"}?print=1', '_blank');
}

// Form submit handler
document.querySelector('form')?.addEventListener('submit', function() {
    hasUnsavedChanges = false;
    hideComparisonPanel();
});
</script>
{/block}
