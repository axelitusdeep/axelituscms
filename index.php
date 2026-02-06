<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("X-Powered-By: AxElitus CMS");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

require_once __DIR__ . '/config.php';

define('CURRENT_PAGE', 'home');

$ext_assets = $GLOBALS['extensions']->getFrontendAssets('frontend');

$page_data = load_page('index');
if (!$page_data) {
    $page_data = ['title' => 'Welcome', 'description' => 'Welcome to AxElitus', 'blocks' => []];
}

$site_settings = get_site_settings();

$page_title = $page_data['title'] ?? $site_settings['site_name'] ?? SITE_NAME;
$page_description = $page_data['description'] ?? $site_settings['site_description'] ?? '';
$blocks = $page_data['blocks'] ?? [];
$has_newsletter = false;

foreach ($blocks as $block) {
    if (($block['type'] ?? '') === 'newsletter') {
        $has_newsletter = true;
        break;
    }
}

$REGISTERED_BLOCKS = [];
$REGISTERED_BLOCKS = ext_hook('register_blocks', $REGISTERED_BLOCKS);

$nav_links = $site_settings['nav_links'] ?? [];
$nav_buttons = $site_settings['nav_buttons'] ?? [];
$footer = $site_settings['footer'] ?? [];
$logo_text = $site_settings['logo_text'] ?? $site_settings['site_name'] ?? SITE_NAME;
$logo_image = $site_settings['logo_image'] ?? '';

$allowed_fonts = [
    'Inter' => '/assets/fonts/inter/inter.css',
    'Space Grotesk' => '/assets/fonts/space-grotesk/space-grotesk.css',
    'Arial' => '',
    'Helvetica' => '',
    'Times New Roman' => '',
    'Courier New' => '',
    'Verdana' => '',
    'Trebuchet MS' => '',
];

$website_font = $site_settings['website_font'] ?? 'Inter';
if (!isset($allowed_fonts[$website_font])) {
    $website_font = 'Inter';
}
$font_url = $allowed_fonts[$website_font];

$search_status_file = dirname(__FILE__) . '/data/search/search.txt';
$show_search = false;

if (file_exists($search_status_file)) {
    $status_content = trim(file_get_contents($search_status_file));
    if ($status_content === 'true') {
        $show_search = true;
    }
}
?>
<!-- Powered by AxElitus CMS -->
<!DOCTYPE html>
<html lang="en" data-cms="axelitus">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <meta name="description" content="<?= e($page_description) ?>">
    <meta name="generator" content="AxElitus CMS">
    <?php if (!empty($site_settings['favicon'])): ?>
    <link rel="icon" href="<?= e($site_settings['favicon']) ?>">
    <?php endif; ?>
    <link rel="preload" href="/assets/fonts/inter/inter.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <?php if (!empty($font_url)): ?>
    <link href="<?= e($font_url) ?>" rel="stylesheet">
    <?php endif; ?>
    
    <link rel="stylesheet" href="/css/styles.css?v=<?= filemtime(__DIR__ . '/css/styles.css') ?>">

    <?php 
    $themePath = __DIR__ . '/css/theme.css';
    $themeUrl = '/css/theme.css';

    if (is_file($themePath) && filesize($themePath) > 0) {
        
        $handle = fopen($themePath, 'r');
        $header = fread($handle, 25);
        fclose($handle);
        if (trim($header) !== '/* No theme active */') {
            $v = filemtime($themePath);
            echo '<link rel="stylesheet" href="' . htmlspecialchars($themeUrl) . '?v=' . $v . '">';
        }
    }
    ?>

    <style>
        body { font-family: '<?= e($website_font) ?>', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>

    <?php foreach ($ext_assets['css'] as $css): ?>
        <link rel="stylesheet" href="<?= $css ?>">
    <?php endforeach; ?>

    <script type="application/ld+json">
    {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "<?= e($site_settings['site_name'] ?? SITE_NAME) ?>",
    "url": "<?= (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]" ?>",
    "description": "<?= e($page_description) ?>",
    "publisher": {
        "@type": "Organization",
        "name": "<?= e($logo_text) ?>"
    }
    }
    </script>

    <?php echo ext_hook('frontend_head', ''); ?>
</head>
<body>
    <?php echo ext_hook('before_header', ''); ?>
    
    <?php $nav_links = ext_hook('modify_nav_links', $nav_links); ?>
    <nav class="navbar">
        <div class="container navbar-inner">
            <a href="/" class="logo">
                <?php if (!empty($logo_image)): ?>
                    <img src="<?= e($logo_image) ?>" alt="<?= e($logo_text) ?>">
                <?php endif; ?>
                <?= e($logo_text) ?>
            </a>
            <?php if (!empty($nav_links)): ?>
            <div class="nav-links">
                <?php foreach ($nav_links as $link): ?>
                    <a href="<?= e($link['url'] ?? '#') ?>"><?= e($link['label'] ?? '') ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($nav_buttons)): ?>
            <div class="nav-buttons">
                <?php if ($show_search): ?>
                    <a href="/search.php" class="btn btn-search" title="Search" style="padding: 8px; display: inline-flex; align-items: center; margin-right: 5px;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </a>
                <?php endif; ?>

                <?php foreach ($nav_buttons as $btn): ?>
                    <a href="<?= e($btn['url'] ?? '#') ?>" class="btn btn-<?= e($btn['style'] ?? 'ghost') ?>"><?= e($btn['label'] ?? '') ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <button class="mobile-menu-btn" aria-label="Menu" onclick="document.querySelector('.mobile-nav').classList.toggle('active')">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </nav>
    <?php echo ext_hook('after_header', ''); ?>
    
    <div class="mobile-nav">
        <?php if (!empty($nav_links)): ?>
            <?php foreach ($nav_links as $link): ?>
                <a href="<?= e($link['url'] ?? '#') ?>"><?= e($link['label'] ?? '') ?></a>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($nav_buttons)): ?>
            <?php foreach ($nav_buttons as $btn): ?>
                <a href="<?= e($btn['url'] ?? '#') ?>" class="btn btn-<?= e($btn['style'] ?? 'ghost') ?>"><?= e($btn['label'] ?? '') ?></a>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($show_search): ?>
            <a href="/search.php" class="btn btn-search" title="Search" style="padding: 8px; display: inline-flex; align-items: center; margin-right: 5px;">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </a>
        <?php endif; ?>
    </div>

    <main>
        <?php echo ext_hook('before_content', ''); ?>

        <?php if (!empty($blocks)): 
            $markdown_map = [
                '/\[u\](.*?)\[\/u\]/is'   => '<u>$1</u>',
                '/\[b\](.*?)\[\/b\]/is'   => '<b>$1</b>',
                '/\[i\](.*?)\[\/i\]/is'   => '<i>$1</i>',
                '/\[s\](.*?)\[\/s\]/is'   => '<s>$1</s>',
                '/\[quote\](.*?)\[\/quote\]/is'   => '<blockquote>$1</blockquote>',
            ];
            $patterns = array_keys($markdown_map);
            $replacements = array_values($markdown_map);
        ?>
            <?php foreach ($blocks as $block): 
                $html = render_block($block);
                echo preg_replace($patterns, $replacements, $html); 
            endforeach; ?>
        <?php endif; ?>

        <?php
        $site_settings = get_site_settings();
        $limit = 6;
        $cache_time = 60;

        if ($site_settings['recent_pages_enabled'] ?? false):
            $all_pages = get_all_products();

            $all_pages = array_filter($all_pages, function($page) {
                return ($page['slug'] ?? '') !== 'contact';
            });

            usort($all_pages, function($a, $b) {
                $timeA = strtotime($a['created_at'] ?? $a['updated_at'] ?? 'now');
                $timeB = strtotime($b['created_at'] ?? $b['updated_at'] ?? 'now');
                return $timeB - $timeA;
            });
            $recent_pages = array_slice($all_pages, 0, $limit);

            $cache_key = md5(serialize(array_map(function($page) {
                return $page['created_at'] ?? $page['updated_at'] ?? 'now';
            }, $recent_pages)));
            $cache_file = __DIR__ . "/cache/pages_grid.html";

            if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
                include($cache_file);
            } else {
                ob_start();
                ?>
                <section class="pages-section" style="margin-bottom: 50px;">
                    <div class="container">
                        <h2 style="font-size: 2.5rem; margin-bottom: 40px;">Our Pages</h2>
                        <?php if (empty($recent_pages)): ?>
                            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                <p>No pages found.</p>
                            </div>
                        <?php else: ?>
                            <div class="pages-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                                <?php foreach ($recent_pages as $page): ?>
                                    <article class="pages-card" style="background: var(--card); border: 1px solid var(--border); padding: 24px; border-radius: 12px; display: flex; flex-direction: column;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                            <span style="color: var(--primary-color); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                                <?= e($page['blocks'][0]['data']['badge'] ?? '') ?>
                                            </span>
                                            <time style="color: var(--text-secondary); font-size: 0.7rem;">
                                                <?= e(date('d.m.Y', strtotime($page['created_at'] ?? $page['updated_at'] ?? 'now'))) ?>
                                            </time>
                                        </div>
                                        <h3 style="margin: 0 0 12px 0; font-size: 1.4rem; color: var(--text);">
                                            <?= e($page['title'] ?? 'No title') ?>
                                        </h3>
                                        <p style="color: var(--text-secondary); line-height: 1.6; font-size: 0.95rem; flex-grow: 1;">
                                            <?= e(mb_strimwidth($page['description'] ?? '', 0, 120, "...")) ?>
                                        </p>
                                        <a href="/<?= e($page['slug'] ?? '') ?>" style="display: inline-block; margin-top: 20px; color: var(--primary-color); text-decoration: none; font-weight: 600; font-size: 0.9rem;">
                                            Czytaj więcej <span style="margin-left: 5px;">→</span>
                                        </a>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
                <?php
                $output = ob_get_clean();
                if (!is_dir(__DIR__ . '/cache')) {
                    mkdir(__DIR__ . '/cache', 0755, true);
                }
                file_put_contents($cache_file, $output);
                echo $output;
            }
        endif;
        ?>

        <?php echo ext_hook('after_content', ''); ?>

    </main>

    <?php
        $footer = ext_hook('modify_footer', $footer);
        echo ext_hook('before_footer', '');
    ?>
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3>
                        <?php if (!empty($logo_image)): ?>
                            <img src="<?= e($logo_image) ?>" alt="<?= e($logo_text) ?>">
                        <?php endif; ?>
                        <?= e($logo_text) ?>
                    </h3>
                    <p><?= e($footer['brand_description'] ?? '') ?></p>
                    <?php if (!empty($footer['social_links'])): ?>
                    <div class="social-links">
                        <?php foreach ($footer['social_links'] as $social): ?>
                            <a href="<?= e($social['url'] ?? '#') ?>" aria-label="<?= e(ucfirst($social['platform'] ?? '')) ?>"><?= render_social_icon($social['platform'] ?? 'twitter') ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($footer['columns'])): ?>
                    <?php foreach ($footer['columns'] as $column): ?>
                    <div class="footer-column">
                        <h4><?= e($column['title'] ?? '') ?></h4>
                        <?php if (!empty($column['links'])): ?>
                        <ul>
                            <?php foreach ($column['links'] as $link): ?>
                            <li><a href="<?= e($link['url'] ?? '#') ?>"><?= e($link['label'] ?? '') ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="footer-bottom">
                <div style="flex: 1;">
                    <p><?= e($footer['copyright'] ?? '© ' . date('Y') . ' ' . $logo_text . '. All rights reserved.') ?></p>
                    <?php if (!empty($footer['bottom_links'])): ?>
                    <div class="footer-legal">
                        <?php foreach ($footer['bottom_links'] as $link): ?>
                            <a href="<?= e($link['url'] ?? '#') ?>"><?= e($link['label'] ?? '') ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <button class="theme-toggle" aria-label="Toggle theme" title="Toggle light/dark theme">
                    <svg class="theme-icon-sun" viewBox="0 0 24 24" style="display: none;"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                    <svg class="theme-icon-moon" viewBox="0 0 24 24" style="display: block;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
            </div>
        </div>
    </footer>
    
    <script>
    const themeToggle = document.querySelector('.theme-toggle');
    const sunIcon = document.querySelector('.theme-icon-sun');
    const moonIcon = document.querySelector('.theme-icon-moon');
    const html = document.documentElement;
    
    function initTheme() {
        let theme = localStorage.getItem('theme');
        if (!theme) {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            theme = prefersDark ? 'dark' : 'light';
        }
        setTheme(theme);
    }
    
    function setTheme(theme) {
        if (theme === 'light') {
            html.setAttribute('data-theme', 'light');
            sunIcon.style.display = 'block';
            moonIcon.style.display = 'none';
            localStorage.setItem('theme', 'light');
        } else {
            html.removeAttribute('data-theme');
            sunIcon.style.display = 'none';
            moonIcon.style.display = 'block';
            localStorage.setItem('theme', 'dark');
        }
    }
    
    function toggleTheme() {
        const currentTheme = html.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        setTheme(newTheme);
    }
    
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
    
    initTheme();
    
    document.querySelectorAll('.mobile-nav a').forEach(link => {
        link.addEventListener('click', () => {
            document.querySelector('.mobile-nav').classList.remove('active');
        });
    });
    </script>

    <?php echo ext_hook('after_footer', ''); ?>

    <?php foreach ($ext_assets['js'] as $js): ?>
        <script src="<?= $js ?>"></script>
    <?php endforeach; ?>

    <?php if ($has_newsletter): ?>
        <script src="/newsletter/newsletter-form.js?v=<?= date('YmdH') ?>"></script>
    <?php endif; ?>
</body>
</html>