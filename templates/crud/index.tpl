{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{$entity_config.title}</h1>
                <p class="text-gray-600 mt-1">Toplam {$pagination.total_items} kayıt</p>
            </div>
            
            <!-- Actions -->
            <div class="flex space-x-3">
                {if in_array('new', $entity_config.actions)}
                <a href="{url route="{$entity_name}/new"}" class="btn btn-primary">
                    {icon name="plus" class="w-4 h-4 mr-2"}
                    Yeni Ekle
                </a>
                {/if}
                
                <button class="btn btn-secondary" onclick="window.print()">
                    {icon name="printer" class="w-4 h-4 mr-2"}
                    Yazdır
                </button>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                {if $entity_config.searchable}
                <div>
                    <label class="form-label">Arama</label>
                    <input type="text" name="search" value="{$search}" 
                           placeholder="Ara..." class="form-input">
                </div>
                {/if}
                
                <!-- Filters -->
                {foreach $entity_config.filters as $filter_field}
                    {assign var="field_config" value=$entity_config.fields[$filter_field]}
                    <div>
                        <label class="form-label">{$field_config.label|default:$filter_field}</label>
                        {if $field_config.type == 'boolean'}
                            <select name="{$filter_field}" class="form-select">
                                <option value="">Tümü</option>
                                <option value="1" {if $filters[$filter_field] == '1'}selected{/if}>Aktif</option>
                                <option value="0" {if $filters[$filter_field] == '0'}selected{/if}>Pasif</option>
                            </select>
                        {elseif $field_config.type == 'choice'}
                            <select name="{$filter_field}" class="form-select">
                                <option value="">Tümü</option>
                                {foreach $field_config.choices as $choice_value => $choice_label}
                                    <option value="{$choice_value}" {if $filters[$filter_field] == $choice_value}selected{/if}>
                                        {$choice_label}
                                    </option>
                                {/foreach}
                            </select>
                        {else}
                            <input type="text" name="{$filter_field}" value="{$filters[$filter_field]}" 
                                   class="form-input" placeholder="Filtrele...">
                        {/if}
                    </div>
                {/foreach}
                
                <!-- Filter Actions -->
                <div class="flex items-end space-x-2">
                    <button type="submit" class="btn btn-primary">
                        Filtrele
                    </button>
                    <a href="{url route=$entity_name}" class="btn btn-secondary">
                        Temizle
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Success/Error Messages -->
    {if isset($smarty.get.success)}
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {if $smarty.get.success == 'created'}
                Kayıt başarıyla oluşturuldu.
            {elseif $smarty.get.success == 'updated'}
                Kayıt başarıyla güncellendi.
            {elseif $smarty.get.success == 'deleted'}
                Kayıt başarıyla silindi.
            {/if}
        </div>
    {/if}

    {if isset($smarty.get.error)}
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {if $smarty.get.error == 'delete_failed'}
                Kayıt silinirken bir hata oluştu.
            {else}
                Bir hata oluştu.
            {/if}
        </div>
    {/if}

    <!-- Data Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="admin-table" data-sortable="true">
                <thead>
                    <tr>
                        <!-- Batch Selection -->
                        <th class="w-8">
                            <input type="checkbox" id="selectAll" class="form-checkbox">
                        </th>
                        
                        <!-- Table Headers -->
                        {foreach $entity_config.fields as $field_name => $field_config}
                            {if !($field_config.list_hidden|default:false)}
                            <th data-sortable="{$field_name}" class="cursor-pointer hover:bg-gray-50">
                                <div class="flex items-center space-x-1">
                                    <span>{$field_config.label|default:$field_name}</span>
                                    <span class="sort-indicator text-gray-400">↕️</span>
                                </div>
                            </th>
                            {/if}
                        {/foreach}
                        
                        <!-- Actions -->
                        <th class="w-32">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $entities as $entity}
                    <tr class="hover:bg-gray-50">
                        <!-- Batch Selection -->
                        <td>
                            <input type="checkbox" name="selected[]" value="{$entity->getId()}" class="form-checkbox">
                        </td>
                        
                        <!-- Data Cells -->
                        {foreach $entity_config.fields as $field_name => $field_config}
                            {if !($field_config.list_hidden|default:false)}
                            <td>
                                {assign var="getter" value="get{$field_name|ucfirst}"}
                                {assign var="field_value" value=$entity->$getter()}
                                
                                {if $field_config.type == 'boolean'}
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {if $field_value}bg-green-100 text-green-800{else}bg-red-100 text-red-800{/if}">
                                        {$field_value|bool_text}
                                    </span>
                                {elseif $field_config.type == 'date'}
                                    {$field_value|date_format:'d.m.Y'}
                                {elseif $field_config.type == 'datetime'}
                                    {$field_value|date_format:'d.m.Y H:i'}
                                {elseif $field_config.type == 'image'}
                                    {if $field_value}
                                        <img src="{$field_value}" alt="Image" class="w-12 h-12 object-cover rounded">
                                    {else}
                                        <span class="text-gray-400">Resim yok</span>
                                    {/if}
                                {elseif $field_config.type == 'money'}
                                    {$field_value|money}
                                {else}
                                    {$field_value|truncate:50}
                                {/if}
                            </td>
                            {/if}
                        {/foreach}
                        
                        <!-- Action Buttons -->
                        <td>
                            <div class="flex space-x-1">
                                {if in_array('show', $entity_config.actions)}
                                <a href="{url route="{$entity_name}/{$entity->getId()}"}" 
                                   class="btn btn-sm btn-secondary" title="Görüntüle">
                                    {icon name="eye" class="w-3 h-3"}
                                </a>
                                {/if}
                                
                                {if in_array('edit', $entity_config.actions)}
                                <a href="{url route="{$entity_name}/{$entity->getId()}/edit"}" 
                                   class="btn btn-sm btn-warning" title="Düzenle">
                                    {icon name="edit" class="w-3 h-3"}
                                </a>
                                {/if}
                                
                                {if in_array('delete', $entity_config.actions)}
                                <button onclick="deleteEntity('{$entity->getId()}')" 
                                        class="btn btn-sm btn-danger" title="Sil">
                                    {icon name="delete" class="w-3 h-3"}
                                </button>
                                {/if}
                            </div>
                        </td>
                    </tr>
                    {foreachelse}
                    <tr>
                        <td colspan="{count($entity_config.fields) + 2}" class="text-center py-8 text-gray-500">
                            Henüz kayıt bulunmuyor.
                            {if in_array('new', $entity_config.actions)}
                                <a href="{url route="{$entity_name}/new"}" class="text-indigo-600 hover:text-indigo-500 ml-2">
                                    İlk kaydı oluştur
                                </a>
                            {/if}
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        {if $pagination.total_pages > 1}
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    {if $pagination.has_prev}
                    <a href="?page={$pagination.prev_page}{if $search}&search={$search}{/if}" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Önceki
                    </a>
                    {/if}
                    {if $pagination.has_next}
                    <a href="?page={$pagination.next_page}{if $search}&search={$search}{/if}" 
                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Sonraki
                    </a>
                    {/if}
                </div>
                
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            <span class="font-medium">{($pagination.current_page - 1) * $pagination.per_page + 1}</span>
                            -
                            <span class="font-medium">{min($pagination.current_page * $pagination.per_page, $pagination.total_items)}</span>
                            arası, toplam
                            <span class="font-medium">{$pagination.total_items}</span>
                            kayıt
                        </p>
                    </div>
                    
                    <div>
                        <nav class="pagination-links">
                            {if $pagination.has_prev}
                            <a href="?page={$pagination.prev_page}{if $search}&search={$search}{/if}" 
                               class="pagination-link">Önceki</a>
                            {/if}
                            
                            {for $i=max(1, $pagination.current_page-2) to min($pagination.total_pages, $pagination.current_page+2)}
                                <a href="?page={$i}{if $search}&search={$search}{/if}" 
                                   class="pagination-link {if $i == $pagination.current_page}active{/if}">
                                    {$i}
                                </a>
                            {/for}
                            
                            {if $pagination.has_next}
                            <a href="?page={$pagination.next_page}{if $search}&search={$search}{/if}" 
                               class="pagination-link">Sonraki</a>
                            {/if}
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        {/if}
    </div>

    <!-- Batch Actions -->
    <div id="batchActions" class="hidden bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">
                <span id="selectedCount">0</span> kayıt seçildi
            </span>
            
            <div class="flex space-x-2">
                <button onclick="exportSelected()" class="btn btn-sm btn-secondary">
                    Dışa Aktar
                </button>
                
                {if in_array('delete', $entity_config.actions)}
                <button onclick="deleteSelected()" class="btn btn-sm btn-danger">
                    Seçilenleri Sil
                </button>
                {/if}
            </div>
        </div>
    </div>
</div>
{/block}

{block name="scripts"}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Batch selection
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="selected[]"]');
    const batchActions = document.getElementById('batchActions');
    const selectedCount = document.getElementById('selectedCount');
    
    selectAll?.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBatchActions();
    });
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBatchActions);
    });
    
    function updateBatchActions() {
        const selected = document.querySelectorAll('input[name="selected[]"]:checked');
        const count = selected.length;
        
        if (selectedCount) selectedCount.textContent = count;
        
        if (count > 0) {
            batchActions?.classList.remove('hidden');
        } else {
            batchActions?.classList.add('hidden');
        }
        
        if (selectAll) {
            selectAll.checked = count === checkboxes.length && count > 0;
            selectAll.indeterminate = count > 0 && count < checkboxes.length;
        }
    }
});

function deleteEntity(id) {
    if (confirm('Bu kaydı silmek istediğinizden emin misiniz?')) {
        fetch(`{url route=$entity_name}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(response => {
            if (response.ok) {
                window.location.reload();
            } else {
                alert('Silme işlemi başarısız oldu.');
            }
        }).catch(error => {
            alert('Bir hata oluştu.');
        });
    }
}

function deleteSelected() {
    const selected = document.querySelectorAll('input[name="selected[]"]:checked');
    if (selected.length === 0) return;
    
    if (confirm(`${selected.length} kaydı silmek istediğinizden emin misiniz?`)) {
        const ids = Array.from(selected).map(cb => cb.value);
        
        Promise.all(ids.map(id => 
            fetch(`{url route=$entity_name}/${id}`, {
                method: 'DELETE',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
        )).then(() => {
            window.location.reload();
        }).catch(() => {
            alert('Bazı kayıtlar silinemedi.');
        });
    }
}

function exportSelected() {
    const selected = document.querySelectorAll('input[name="selected[]"]:checked');
    const ids = Array.from(selected).map(cb => cb.value);
    
    if (ids.length === 0) {
        alert('Lütfen dışa aktarılacak kayıtları seçin.');
        return;
    }
    
    window.location.href = `{url route=$entity_name}/export?ids=${ids.join(',')}`;
}
</script>
{/block}
