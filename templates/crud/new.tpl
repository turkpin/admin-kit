{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Yeni {$entity_config.title}</h1>
                <p class="text-gray-600 mt-1">Yeni kayıt oluşturmak için formu doldurun</p>
            </div>
            
            <!-- Help Button -->
            <button onclick="toggleHelp()" class="btn btn-secondary">
                {icon name="question-mark-circle" class="w-4 h-4 mr-2"}
                Yardım
            </button>
        </div>
    </div>

    <!-- Help Panel -->
    <div id="helpPanel" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                {icon name="information-circle" class="w-5 h-5 text-blue-400 mt-0.5"}
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Form Doldurma İpuçları</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li>Kırmızı yıldız (*) ile işaretli alanlar zorunludur</li>
                        <li>Form otomatik olarak kaydedilir, taslak özelliği yoktur</li>
                        <li>Dosya yüklerken maksimum boyut sınırlarına dikkat edin</li>
                        <li>Tarih alanlarında gün/ay/yıl formatını kullanın</li>
                        <li>E-posta alanları otomatik olarak doğrulanır</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900">Kayıt Bilgileri</h3>
            <p class="text-sm text-gray-600 mt-1">
                Tüm gerekli alanları doldurun ve kaydet butonuna tıklayın.
            </p>
        </div>
        
        {include file="crud/_form.tpl"}
    </div>

    <!-- Form Tips -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">💡 Hızlı İpuçları</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
            <div>
                <strong>Klavye Kısayolları:</strong>
                <ul class="mt-1 space-y-1">
                    <li><kbd>Ctrl + S</kbd> - Kaydet</li>
                    <li><kbd>Esc</kbd> - İptal et</li>
                    <li><kbd>Tab</kbd> - Sonraki alan</li>
                </ul>
            </div>
            <div>
                <strong>Validation:</strong>
                <ul class="mt-1 space-y-1">
                    <li>Alanlar gerçek zamanlı doğrulanır</li>
                    <li>Hatalar kırmızı renkte gösterilir</li>
                    <li>Geçerli veriler yeşil işaret alır</li>
                </ul>
            </div>
        </div>
    </div>
</div>
{/block}

{block name="scripts"}
<script>
// Help panel toggle
function toggleHelp() {
    const helpPanel = document.getElementById('helpPanel');
    helpPanel.classList.toggle('hidden');
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + S to save
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        document.querySelector('form').submit();
    }
    
    // Esc to go back
    if (e.key === 'Escape') {
        if (confirm('Değişiklikleri kaydetmeden çıkmak istediğinizden emin misiniz?')) {
            window.location.href = '{url route=$entity_name}';
        }
    }
});

// Auto-save draft feature (optional)
let autoSaveTimer;
function scheduleAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(function() {
        saveDraft();
    }, 30000); // 30 seconds
}

function saveDraft() {
    const formData = new FormData(document.querySelector('form'));
    const draftData = {};
    
    for (let [key, value] of formData.entries()) {
        if (key !== '_token') { // Exclude CSRF token
            draftData[key] = value;
        }
    }
    
    localStorage.setItem('adminkit_draft_{$entity_name}', JSON.stringify({
        data: draftData,
        timestamp: Date.now()
    }));
    
    // Show subtle notification
    showNotification('Taslak kaydedildi', 'info', 2000);
}

function loadDraft() {
    const draftKey = 'adminkit_draft_{$entity_name}';
    const draft = localStorage.getItem(draftKey);
    
    if (draft) {
        try {
            const draftData = JSON.parse(draft);
            const age = Date.now() - draftData.timestamp;
            
            // If draft is less than 1 hour old
            if (age < 3600000) {
                if (confirm('Daha önce kaydedilmiş bir taslak bulundu. Yüklemek ister misiniz?')) {
                    Object.keys(draftData.data).forEach(key => {
                        const field = document.querySelector(`[name="${key}"]`);
                        if (field) {
                            if (field.type === 'checkbox') {
                                field.checked = draftData.data[key] === 'on';
                            } else {
                                field.value = draftData.data[key];
                            }
                        }
                    });
                    
                    showNotification('Taslak yüklendi', 'success');
                } else {
                    localStorage.removeItem(draftKey);
                }
            } else {
                // Remove old draft
                localStorage.removeItem(draftKey);
            }
        } catch (e) {
            localStorage.removeItem(draftKey);
        }
    }
}

// Clear draft on successful submit
document.querySelector('form')?.addEventListener('submit', function() {
    localStorage.removeItem('adminkit_draft_{$entity_name}');
});

// Initialize draft functionality
document.addEventListener('DOMContentLoaded', function() {
    loadDraft();
    
    // Schedule auto-save on form changes
    document.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('input', scheduleAutoSave);
        field.addEventListener('change', scheduleAutoSave);
    });
});

// Show notification function
function showNotification(message, type = 'info', duration = 5000) {
    // Remove existing notifications
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification fixed top-4 right-4 z-50 max-w-sm p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
    
    const colors = {
        success: 'bg-green-100 border-green-400 text-green-700',
        error: 'bg-red-100 border-red-400 text-red-700',
        warning: 'bg-yellow-100 border-yellow-400 text-yellow-700',
        info: 'bg-blue-100 border-blue-400 text-blue-700'
    };
    
    notification.className += ` ${colors[type] || colors.info}`;
    notification.innerHTML = `
        <div class="flex items-center">
            <span class="flex-1">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-lg leading-none">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove
    setTimeout(() => {
        if (notification.parentNode) {
            notification.classList.add('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        }
    }, duration);
}
</script>
{/block}
