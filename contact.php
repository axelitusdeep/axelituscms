<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("X-Powered-By: AxElitus CMS");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

require_once __DIR__ . '/config.php';

$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : 'contact';

define('CURRENT_PAGE', 'contact');

$site_settings = get_site_settings();
if (empty($site_settings['contact_page_enabled']) || $site_settings['contact_page_enabled'] === false) {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/contact-handler.php';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

$page = null;
$pages = get_all_products();
foreach ($pages as $p) {
    if ($p['slug'] === $slug) {
        $page = $p;
        break;
    }
}

$site_settings = get_site_settings();
$page_title = $page['title'] ?? 'Contact Us';
$page_description = $page['description'] ?? '';
$blocks = $page['blocks'] ?? [];
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
$nav_links = ext_hook('modify_nav_links', $nav_links);

$nav_buttons = $site_settings['nav_buttons'] ?? [];
$footer = $site_settings['footer'] ?? [];
$logo_text = $site_settings['logo_text'] ?? $site_settings['site_name'] ?? 'AxElitus';
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
    <title><?= e($page_title) ?> - <?= e($site_settings['site_name']) ?></title>
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
    <link rel="stylesheet" href="/css/contact.css?v=<?= filemtime(__DIR__ . '/css/contact.css') ?>">

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

    <?php
    $ext_assets = $GLOBALS['extensions']->getFrontendAssets('frontend');

    foreach ($ext_assets['css'] as $css): ?>
        <link rel="stylesheet" href="<?= $css ?>">
    <?php endforeach;

    echo ext_hook('frontend_head', '');
    ?>
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

    <?php echo ext_hook('after_header', ''); ?>

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

        <section class="contact-form-section" id="contact-form">
            <div class="container">
                <div class="section-header">
                    <h2>Contact Us</h2>
                    <p>Have questions? Reach out to us!</p>
                </div>

                <?php $message = get_contact_message(); ?>
                <?php if ($message): ?>
                    <div style="padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; <?= $message['type'] === 'success' ? 'background: #dcfce7; color: #166534;' : 'background: #fee2e2; color: #991b1b;' ?>">
                        <?= e($message['text']) ?>
                    </div>
                <?php endif; ?>

                <div class="contact-form-wrapper">
                    <form class="contact-form" method="POST" action="">
                        <div class="form-group">
                            <label for="name">Name <span style="color: #ef4444;">*</span></label>
                            <input type="text" id="name" name="name" required maxlength="100" 
                                placeholder="John Doe" value="<?= e(get_contact_data('name')) ?>">
                            <?php if ($error = get_contact_error('name')): ?>
                                <small style="color: #ef4444;"><?= e($error) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span style="color: #ef4444;">*</span></label>
                            <input type="email" id="email" name="email" required 
                                placeholder="john@example.com" value="<?= e(get_contact_data('email')) ?>">
                            <?php if ($error = get_contact_error('email')): ?>
                                <small style="color: #ef4444;"><?= e($error) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="message">Message <span style="color: #ef4444;">*</span></label>
                            <textarea id="message" name="message" rows="5" required maxlength="5000" 
                                    placeholder="Your message..."><?= e(get_contact_data('message')) ?></textarea>
                            <?php if ($error = get_contact_error('message')): ?>
                                <small style="color: #ef4444;"><?= e($error) ?></small>
                            <?php endif; ?>
                            <small style="color: var(--muted); font-size: 0.875rem; margin-top: 4px; display: block;">
                                Maximum 5000 characters
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Send Message
                        </button>

                        <p style="margin-top: 12px; font-size: 0.875rem; color: var(--muted);">
                            <span style="color: #ef4444;">*</span> Required fields
                        </p>
                    </form>
                </div>
            </div>
        </section>

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

    <?php
    echo ext_hook('after_footer', '');

    foreach ($ext_assets['js'] as $js): ?>
        <script src="<?= $js ?>"></script>
    <?php endforeach; ?>

    <?php if ($has_newsletter): ?>
        <script src="/newsletter/newsletter-form.js"></script>
    <?php endif; ?>
</body>
</html>