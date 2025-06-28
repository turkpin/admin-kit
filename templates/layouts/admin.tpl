<!DOCTYPE html>
<html lang="{$locale|default:'tr'}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$page_title|default:$brand_name} - {$brand_name}</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{asset path='css/admin.css'}">
    
    <!-- Alpine.js for interactive components -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    {block name="head"}{/block}
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg">
            <div class="p-6">
                <h1 class="text-xl font-bold text-gray-800">{$brand_name}</h1>
            </div>
            
            <nav class="mt-8">
                <ul class="space-y-2 px-4">
                    <!-- Dashboard -->
                    <li>
                        <a href="{url route=''}" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100">
                            {icon name="home" class="w-5 h-5 mr-3"}
                            Dashboard
                        </a>
                    </li>
                    
                    {if isset($entities)}
                        {foreach $entities as $entity_name => $entity_config}
                            <li>
                                <a href="{url route=$entity_name}" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100">
                                    {icon name="users" class="w-5 h-5 mr-3"}
                                    {$entity_config.title}
                                </a>
                            </li>
                        {/foreach}
                    {/if}
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">
                                {$page_title|default:'Dashboard'}
                            </h1>
                            {if isset($breadcrumbs)}
                                <nav class="text-sm text-gray-600 mt-1">
                                    {foreach $breadcrumbs as $breadcrumb}
                                        {if !$breadcrumb@last}
                                            <a href="{$breadcrumb.url}" class="hover:text-gray-800">{$breadcrumb.title}</a>
                                            <span class="mx-2">/</span>
                                        {else}
                                            <span class="text-gray-800">{$breadcrumb.title}</span>
                                        {/if}
                                    {/foreach}
                                </nav>
                            {/if}
                        </div>
                        
                        <!-- User Menu -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center text-sm text-gray-700 hover:text-gray-900">
                                {icon name="user" class="w-5 h-5 mr-2"}
                                {if isset($current_user)}
                                    {$current_user->getName()}
                                {else}
                                    Admin
                                {/if}
                                <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                <a href="{url route='logout'}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Çıkış Yap
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            {if isset($flash_messages)}
                <div class="px-6 py-4">
                    {foreach $flash_messages as $type => $messages}
                        {foreach $messages as $message}
                            {flash type=$type message=$message}
                        {/foreach}
                    {/foreach}
                </div>
            {/if}

            <!-- Page Content -->
            <main class="flex-1 px-6 py-6">
                {block name="content"}{/block}
            </main>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="{asset path='js/admin.js'}"></script>
    
    {block name="scripts"}{/block}
</body>
</html>
