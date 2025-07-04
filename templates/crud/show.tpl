{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{$entity_config.title} Detayı</h1>
                <p class="text-gray-600 mt-1">Kayıt bilgilerini görüntülüyorsunuz</p>
            </div>
            
            <!-- Actions -->
            <div class="flex space-x-3">
                <a href="{url route=$entity_name}" class="btn btn-secondary">
                    {icon name="arrow-left" class="w-4 h-4 mr-2"}
                    Geri Dön
                </a>
                
                {if in_array('edit', $entity_config.actions)}
                <a href="{url route="{$entity_name}/{$entity->getId()}/edit"}" class="btn btn-warning">
                    {icon name="edit" class="w-4 h-4 mr-2"}
                    Düzenle
                </a>
                {/if}
                
                {if in_array('delete', $entity_config.actions)}
                <button onclick="deleteEntity()" class="btn btn-danger">
                    {icon name="delete" class="w-4 h-4 mr-2"}
                    Sil
                </button>
                {/if}
            </div>
        </div>
    </div>

    <!-- Entity Details -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Kayıt Bilgileri</h3>
        </div>
        
        <div class="px-6 py-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                {foreach $entity_config.fields as $field_name => $field_config}
                    {if !($field_config.show_hidden|default:false)}
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500 mb-1">
                            {$field_config.label|default:$field_name}
                        </dt>
                        <dd class="text-sm text-gray-900">
                            {assign var="getter" value="get{$field_name|ucfirst}"}
                            {assign var="field_value" value=$entity->$getter()}
                            
                            {if $field_config.type == 'boolean'}
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {if $field_value}bg-green-100 text-green-800{else}bg-red-100 text-red-800{/if}">
                                    {$field_value|bool_text}
                                </span>
                            {elseif $field_config.type == 'date'}
                                {if $field_value}
                                    <time datetime="{$field_value|date_format:'Y-m-d'}">
                                        {$field_value|date_format:'d.m.Y'}
                                    </time>
                                {else}
                                    <span class="text-gray-400">Belirtilmemiş</span>
                                {/if}
                            {elseif $field_config.type == 'datetime'}
                                {if $field_value}
                                    <time datetime="{$field_value|date_format:'Y-m-d H:i:s'}">
                                        {$field_value|date_format:'d.m.Y H:i'}
                                    </time>
                                {else}
                                    <span class="text-gray-400">Belirtilmemiş</span>
                                {/if}
                            {elseif $field_config.type == 'image'}
                                {if $field_value}
                                    <div class="mt-2">
                                        <img src="{$field_value}" alt="{$field_config.label|default:$field_name}" 
                                             class="max-w-xs h-auto rounded-lg shadow-md cursor-pointer"
                                             onclick="openImageModal(this.src)">
                                    </div>
                                {else}
                                    <span class="text-gray-400">Resim yüklenmemiş</span>
                                {/if}
                            {elseif $field_config.type == 'file'}
                                {if $field_value}
                                    <a href="{$field_value}" target="_blank" 
                                       class="inline-flex items-center text-indigo-600 hover:text-indigo-500">
                                        {icon name="document" class="w-4 h-4 mr-1"}
                                        Dosyayı İndir
                                    </a>
                                {else}
                                    <span class="text-gray-400">Dosya yüklenmemiş</span>
                                {/if}
                            {elseif $field_config.type == 'email'}
                                {if $field_value}
                                    <a href="mailto:{$field_value}" class="text-indigo-600 hover:text-indigo-500">
                                        {$field_value}
                                    </a>
                                {else}
                                    <span class="text-gray-400">E-posta belirtilmemiş</span>
                                {/if}
                            {elseif $field_config.type == 'url'}
                                {if $field_value}
                                    <a href="{$field_value}" target="_blank" class="text-indigo-600 hover:text-indigo-500">
                                        {$field_value|truncate:50}
                                        {icon name="external-link" class="w-3 h-3 ml-1 inline"}
                                    </a>
                                {else}
                                    <span class="text-gray-400">URL belirtilmemiş</span>
                                {/if}
                            {elseif $field_config.type == 'money'}
                                {if $field_value !== null}
                                    <span class="font-medium">
                                        {$field_value|money:($field_config.currency|default:'TL')}
                                    </span>
                                {else}
                                    <span class="text-gray-400">Belirtilmemiş</span>
                                {/if}
                            {elseif $field_config.type == 'textarea'}
                                {if $field_value}
                                    <div class="prose prose-sm max-w-none">
                                        {$field_value|nl2br}
                                    </div>
                                {else}
                                    <span class="text-gray-400">İçerik girilmemiş</span>
                                {/if}
                            {elseif $field_config.type == 'choice'}
                                {if $field_value}
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {$field_config.choices[$field_value]|default:$field_value}
                                    </span>
                                {else}
                                    <span class="text-gray-400">Seçim yapılmamış</span>
                                {/if}
                            {elseif $field_config.type == 'association'}
                                {if $field_value}
                                    {if is_array($field_value) || $field_value instanceof Traversable}
                                        {* Multiple association *}
                                        <div class="space-y-1">
                                            {foreach $field_value as $related_entity}
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    {if method_exists($related_entity, 'getName')}
                                                        {$related_entity->getName()}
                                                    {elseif method_exists($related_entity, 'getTitle')}
                                                        {$related_entity->getTitle()}
                                                    {else}
                                                        #{$related_entity->getId()}
                                                    {/if}
                                                </span>
                                            {/foreach}
                                        </div>
                                    {else}
                                        {* Single association *}
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {if method_exists($field_value, 'getName')}
                                                {$field_value->getName()}
                                            {elseif method_exists($field_value, 'getTitle')}
                                                {$field_value->getTitle()}
                                            {else}
                                                #{$field_value->getId()}
                                            {/if}
                                        </span>
                                    {/if}
                                {else}
                                    <span class="text-gray-400">İlişki kurulmamış</span>
                                {/if}
                            {elseif $field_config.type == 'password'}
                                <span class="text-gray-400">••••••••</span>
                            {else}
                                {* Default text display *}
                                {if $field_value}
                                    <span class="break-words">{$field_value}</span>
                                {else}
                                    <span class="text-gray-400">Belirtilmemiş</span>
                                {/if}
                            {/if}
                        </dd>
                    </div>
                    {/if}
                {/foreach}
            </dl>
        </div>
    </div>

    <!-- Metadata -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Sistem Bilgileri</h3>
        </div>
        
        <div class="px-6 py-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500 mb-1">Kayıt ID</dt>
                    <dd class="text-sm text-gray-900 font-mono">#{$entity->getId()}</dd>
                </div>
                
                {if method_exists($entity, 'getCreatedAt')}
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500 mb-1">Oluşturulma Tarihi</dt>
                    <dd class="text-sm text-gray-900">
                        {assign var="created_at" value=$entity->getCreatedAt()}
                        {if $created_at}
                            <time datetime="{$created_at|date_format:'Y-m-d H:i:s'}">
                                {$created_at|date_format:'d.m.Y H:i'}
                            </time>
                        {else}
                            <span class="text-gray-400">Bilinmiyor</span>
                        {/if}
                    </dd>
                </div>
                {/if}
                
                {if method_exists($entity, 'getUpdatedAt')}
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500 mb-1">Son Güncellenme</dt>
                    <dd class="text-sm text-gray-900">
                        {assign var="updated_at" value=$entity->getUpdatedAt()}
                        {if $updated_at}
                            <time datetime="{$updated_at|date_format:'Y-m-d H:i:s'}">
                                {$updated_at|date_format:'d.m.Y H:i'}
                            </time>
                        {else}
                            <span class="text-gray-400">Hiç güncellenmemiş</span>
                        {/if}
                    </dd>
                </div>
                {/if}
            </dl>
        </div>
    </div>

    <!-- Related Records -->
    {* Bu bölüm ileride ilişkili kayıtları göstermek için kullanılacak *}
    {if false}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">İlişkili Kayıtlar</h3>
        </div>
        
        <div class="px-6 py-6">
            <p class="text-gray-500">İlişkili kayıtlar burada gösterilecek.</p>
        </div>
    </div>
    {/if}
</div>

<!-- Image Modal -->
<div id="imageModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Resim Görüntüleyici</h3>
            <button onclick="closeImageModal()" class="text-gray-400 hover:text-gray-600">
                {icon name="x" class="w-6 h-6"}
            </button>
        </div>
        <div class="text-center">
            <img id="modalImage" src="" alt="Preview" class="max-w-full h-auto rounded-lg">
        </div>
    </div>
</div>
{/block}

{block name="scripts"}
<script>
function deleteEntity() {
    if (confirm('Bu kaydı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
        fetch(`{url route=$entity_name}/{$entity->getId()}`, {
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

function openImageModal(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    
    modalImage.src = imageSrc;
    modal.classList.remove('hidden');
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
}

// Close modal with ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});

// Close modal when clicking outside
document.getElementById('imageModal')?.addEventListener('click', function(event) {
    if (event.target === this) {
        closeImageModal();
    }
});
</script>
{/block}
