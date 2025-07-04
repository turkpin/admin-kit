{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900">{adminkit_translate('welcome')}</h1>
        <p class="text-gray-600 mt-2">{adminkit_translate('dashboard_welcome_text')}</p>
    </div>

    <!-- Stats Grid -->
    {if isset($widgets) && count($widgets) > 0}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {foreach $widgets as $widget_name => $widget}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                            {$widget.title}
                        </h3>
                        <div class="mt-2">
                            <span class="text-3xl font-bold text-gray-900">
                                {if is_callable($widget.value)}
                                    {$widget.value|call}
                                {else}
                                    {$widget.value}
                                {/if}
                            </span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="w-12 h-12 bg-{$widget.color|default:'blue'}-100 rounded-lg flex items-center justify-center">
                            {if isset($widget.icon)}
                                {icon name=$widget.icon class="w-6 h-6 text-{$widget.color|default:'blue'}-600"}
                            {else}
                                {icon name="chart-bar" class="w-6 h-6 text-{$widget.color|default:'blue'}-600"}
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
    {/if}

    <!-- Recent Activity and Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{adminkit_translate('quick_actions')}</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {if isset($entities)}
                        {foreach $entities as $entity_name => $entity_config}
                            {if in_array('new', $entity_config.actions)}
                            <a href="{url route="{$entity_name}/new"}" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                {icon name="plus" class="w-5 h-5 text-indigo-600 mr-3"}
                                <span class="text-sm font-medium text-gray-900">{adminkit_translate('add_new')} {$entity_config.title}</span>
                            </a>
                            {/if}
                        {/foreach}
                    {/if}
                </div>
            </div>
        </div>

        <!-- System Info -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{adminkit_translate('system_info')}</h3>
            </div>
            <div class="p-6">
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">{adminkit_translate('php_version')}</dt>
                        <dd class="text-sm text-gray-900">{$smarty.const.PHP_VERSION}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">{adminkit_translate('adminkit_version')}</dt>
                        <dd class="text-sm text-gray-900">1.0.6</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">{adminkit_translate('login_time')}</dt>
                        <dd class="text-sm text-gray-900">{$smarty.now|date_format:'d.m.Y H:i'}</dd>
                    </div>
                    {if isset($current_user)}
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">{adminkit_translate('current_user')}</dt>
                        <dd class="text-sm text-gray-900">{$current_user->getName()}</dd>
                    </div>
                    {/if}
                </dl>
            </div>
        </div>
    </div>

    <!-- Entity Overview -->
    {if isset($entities) && count($entities) > 0}
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">{adminkit_translate('entities')}</h3>
            <p class="text-sm text-gray-600 mt-1">{adminkit_translate('manageable_entities')}</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {foreach $entities as $entity_name => $entity_config}
                <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition-colors">
                    <div class="flex items-center mb-3">
                        {icon name="users" class="w-5 h-5 text-indigo-600 mr-2"}
                        <h4 class="font-medium text-gray-900">{$entity_config.title}</h4>
                    </div>
                    <div class="flex space-x-2">
                        {if in_array('index', $entity_config.actions)}
                        <a href="{url route=$entity_name}" class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                            {adminkit_translate('list_action')}
                        </a>
                        {/if}
                        {if in_array('new', $entity_config.actions)}
                        <a href="{url route="{$entity_name}/new"}" class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                            {adminkit_translate('add_new_action')}
                        </a>
                        {/if}
                    </div>
                </div>
                {/foreach}
            </div>
        </div>
    </div>
    {/if}
</div>
{/block}

{block name="scripts"}
<script>
    // Dashboard specific JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Dashboard loaded');
    });
</script>
{/block}
