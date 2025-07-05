<!DOCTYPE html>
<html lang="{$current_lang}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$page_title} - AdminKit</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .hero { text-align: center; padding: 4rem 0; color: white; }
        .hero h1 { font-size: 3rem; font-weight: 700; margin-bottom: 1rem; }
        .hero p { font-size: 1.25rem; opacity: 0.9; margin-bottom: 2rem; }
        .hero .version { background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; }
        
        .content { background: white; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; margin-top: 2rem; }
        .section { padding: 3rem; }
        .section:not(:last-child) { border-bottom: 1px solid #e5e7eb; }
        
        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
        .feature { text-align: center; padding: 1.5rem; border-radius: 8px; background: #f8fafc; }
        .feature-icon { width: 60px; height: 60px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem; }
        .feature h3 { font-size: 1.25rem; margin-bottom: 0.5rem; color: #374151; }
        .feature p { color: #6b7280; line-height: 1.6; }
        
        .steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; }
        .step { border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; }
        .step-number { width: 40px; height: 40px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 1rem; }
        .step h3 { margin-bottom: 0.5rem; color: #374151; }
        .step p { color: #6b7280; margin-bottom: 1rem; line-height: 1.6; }
        .step code { background: #f3f4f6; padding: 0.5rem; border-radius: 4px; font-family: monospace; font-size: 0.875rem; display: block; }
        
        .actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; }
        .action { border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; text-decoration: none; color: inherit; transition: transform 0.2s, box-shadow 0.2s; }
        .action:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .action-icon { width: 50px; height: 50px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; color: white; font-size: 1.25rem; }
        .action-icon.blue { background: #3b82f6; }
        .action-icon.green { background: #10b981; }
        .action-icon.purple { background: #8b5cf6; }
        .action-icon.gray { background: #6b7280; }
        .action h3 { margin-bottom: 0.5rem; color: #374151; }
        .action p { color: #6b7280; line-height: 1.6; }
        
        .section h2 { font-size: 2rem; margin-bottom: 2rem; color: #374151; text-align: center; }
        
        .lang-switcher { position: absolute; top: 2rem; right: 2rem; }
        .lang-switcher select { padding: 0.5rem; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: white; border-radius: 4px; }
        
        @media (max-width: 768px) {
            .hero h1 { font-size: 2rem; }
            .container { padding: 1rem; }
            .section { padding: 2rem; }
            .features, .steps, .actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="lang-switcher">
        <select onchange="changeLanguage(this.value)">
            {foreach $supported_languages as $lang}
                <option value="{$lang}" {if $lang == $current_lang}selected{/if}>
                    {if $lang == 'tr'}Türkçe{elseif $lang == 'en'}English{elseif $lang == 'de'}Deutsch{else}{$lang|upper}{/if}
                </option>
            {/foreach}
        </select>
    </div>

    <div class="container">
        <div class="hero">
            <h1>Welcome to AdminKit</h1>
            <p>A powerful, modern admin panel toolkit for PHP applications</p>
            <span class="version">v1.0.7</span>
        </div>

        <div class="content">
            <!-- Features Section -->
            <div class="section">
                <h2>Key Features</h2>
                <div class="features">
                    {foreach $features as $feature}
                        <div class="feature">
                            <div class="feature-icon">
                                {if $feature.icon == 'cog'}⚙️
                                {elseif $feature.icon == 'shield'}🛡️
                                {elseif $feature.icon == 'chart-bar'}📊
                                {elseif $feature.icon == 'globe'}🌐
                                {elseif $feature.icon == 'mobile'}📱
                                {elseif $feature.icon == 'puzzle'}🧩
                                {else}✨{/if}
                            </div>
                            <h3>{$feature.title}</h3>
                            <p>{$feature.description}</p>
                        </div>
                    {/foreach}
                </div>
            </div>

            <!-- Getting Started Section -->
            <div class="section">
                <h2>Getting Started</h2>
                <div class="steps">
                    {foreach $getting_started_steps as $step}
                        <div class="step">
                            <div class="step-number">{$step.number}</div>
                            <h3>{$step.title}</h3>
                            <p>{$step.description}</p>
                            <code>{$step.command}</code>
                        </div>
                    {/foreach}
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="section">
                <h2>Quick Actions</h2>
                <div class="actions">
                    {foreach $quick_actions as $action}
                        <a href="{$action.url}" class="action" {if strpos($action.url, 'http') === 0}target="_blank"{/if}>
                            <div class="action-icon {$action.color}">
                                {if $action.icon == 'book'}📚
                                {elseif $action.icon == 'eye'}👁️
                                {elseif $action.icon == 'cog'}⚙️
                                {elseif $action.icon == 'code'}💻
                                {else}🔗{/if}
                            </div>
                            <h3>{$action.title}</h3>
                            <p>{$action.description}</p>
                        </a>
                    {/foreach}
                </div>
            </div>
        </div>
    </div>

    <script>
        function changeLanguage(lang) {
            // This would typically make an AJAX request to change the language
            // For now, we'll just reload with a language parameter
            const url = new URL(window.location);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }

        // Add some animation
        document.addEventListener('DOMContentLoaded', function() {
            const features = document.querySelectorAll('.feature');
            const steps = document.querySelectorAll('.step');
            const actions = document.querySelectorAll('.action');

            function animateElements(elements, delay = 100) {
                elements.forEach((element, index) => {
                    setTimeout(() => {
                        element.style.opacity = '0';
                        element.style.transform = 'translateY(20px)';
                        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                        
                        setTimeout(() => {
                            element.style.opacity = '1';
                            element.style.transform = 'translateY(0)';
                        }, 50);
                    }, index * delay);
                });
            }

            animateElements(features, 150);
            animateElements(steps, 200);
            animateElements(actions, 100);
        });
    </script>
</body>
</html>
