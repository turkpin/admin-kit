{* Language Switcher Component *}
<div class="relative inline-block text-left">
    <div>
        <button type="button" onclick="toggleLanguageMenu()" class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="language-menu-button" aria-expanded="true" aria-haspopup="true">
            <span class="mr-2">{$current_locale_flag|default:'🌍'}</span>
            {$current_locale_name|default:'Language'}
            <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>

    <div id="language-menu" class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50" role="menu" aria-orientation="vertical" aria-labelledby="language-menu-button" tabindex="-1">
        <div class="py-1" role="none">
            {if isset($supported_locales)}
                {foreach $supported_locales as $locale}
                    <a href="{url route="language/set/{$locale.code}"}" 
                       class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 {if $locale.code == $current_locale}bg-gray-50 text-gray-900{/if}" 
                       role="menuitem">
                        <span class="mr-3">{$locale.flag}</span>
                        {$locale.name}
                        {if $locale.code == $current_locale}
                            <svg class="ml-auto h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        {/if}
                    </a>
                {/foreach}
            {else}
                {* Default languages if not provided *}
                <a href="{url route="language/set/tr"}" class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                    <span class="mr-3">🇹🇷</span>
                    Türkçe
                </a>
                <a href="{url route="language/set/en"}" class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                    <span class="mr-3">🇺🇸</span>
                    English
                </a>
                <a href="{url route="language/set/de"}" class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                    <span class="mr-3">🇩🇪</span>
                    Deutsch
                </a>
                <a href="{url route="language/set/fr"}" class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                    <span class="mr-3">🇫🇷</span>
                    Français
                </a>
                <a href="{url route="language/set/es"}" class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                    <span class="mr-3">🇪🇸</span>
                    Español
                </a>
            {/if}
        </div>
    </div>
</div>

<script>
function toggleLanguageMenu() {
    const menu = document.getElementById('language-menu');
    const button = document.getElementById('language-menu-button');
    
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        button.setAttribute('aria-expanded', 'true');
    } else {
        menu.classList.add('hidden');
        button.setAttribute('aria-expanded', 'false');
    }
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('language-menu');
    const button = document.getElementById('language-menu-button');
    
    if (!button.contains(event.target) && !menu.contains(event.target)) {
        menu.classList.add('hidden');
        button.setAttribute('aria-expanded', 'false');
    }
});

// Close menu on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const menu = document.getElementById('language-menu');
        const button = document.getElementById('language-menu-button');
        menu.classList.add('hidden');
        button.setAttribute('aria-expanded', 'false');
    }
});
</script>
