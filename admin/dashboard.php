<?php
require_once __DIR__ . '/../config.php';

$extensionsFile = __DIR__ . '/../data/extensions.json';
$activeExtensions = [];

if (is_file($extensionsFile) && is_readable($extensionsFile)) {
    $extensionsData = json_decode(file_get_contents($extensionsFile), true);
    if (isset($extensionsData['active']) && is_array($extensionsData['active'])) {
        $activeExtensions = $extensionsData['active'];
    }
}

$ext_assets = $GLOBALS['extensions']->getFrontendAssets('admin');

$sitemap_file = __DIR__ . '/../data/sitemap-config.php';
if (file_exists($sitemap_file)) {
    $sitemap_settings = include $sitemap_file;
} else {
    $sitemap_settings = [
        'sitemap' => false,
        'website_domain' => '',
    ];
}

require_login();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = $_SESSION['admin_msg'] ?? '';
$message_type = $_SESSION['admin_msg_type'] ?? '';
unset($_SESSION['admin_msg'], $_SESSION['admin_msg_type']);

$current_tab = $_GET['tab'] ?? 'index';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($token)) {
        $_SESSION['admin_msg'] = 'Invalid request (CSRF). Please try again.';
        $_SESSION['admin_msg_type'] = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $redirect_tab = $_POST['current_tab'] ?? $current_tab;
        
        switch ($action) {
            case 'save_index':
                $data = [
                    'title' => $_POST['page_title'] ?? SITE_NAME,
                    'description' => $_POST['page_description'] ?? '',
                    'blocks' => json_decode($_POST['blocks_data'] ?? '[]', true),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                
                $data = ext_hook('before_save_page', $data);

                if (save_page('index', $data)) {
                    ext_hook('after_save_page', $data);

                    $_SESSION['admin_msg'] = 'Main page has been saved!';
                    $_SESSION['admin_msg_type'] = 'success';
                } else {
                    $_SESSION['admin_msg'] = 'Error saving main page.';
                    $_SESSION['admin_msg_type'] = 'error';
                }
                break;

            case 'save_settings':
                $settings = [
                    'site_name' => $_POST['site_name'] ?? SITE_NAME,
                    'site_description' => $_POST['site_description'] ?? '',
                    'logo_text' => $_POST['logo_text'] ?? SITE_NAME,
                    'logo_image' => $_POST['logo_image'] ?? '',
                    'favicon' => $_POST['favicon'] ?? '',
                    'website_font' => $_POST['website_font'] ?? 'Inter',
                    'primary_color' => $_POST['primary_color'] ?? '#ffffff',
                    'nav_links' => json_decode($_POST['nav_links'] ?? '[]', true),
                    'nav_buttons' => json_decode($_POST['nav_buttons'] ?? '[]', true),
                    'footer' => json_decode($_POST['footer_data'] ?? '{}', true),
                    'recent_pages_enabled' => isset($_POST['recent_pages']) && $_POST['recent_pages'] === 'true',
                    'contact_page_enabled' => isset($_POST['contact_page_enabled']) && $_POST['contact_page_enabled'] === 'true',
                    'contact_email' => $_POST['contact_email'] ?? '',
                ];

                ext_hook('before_save_page', $settings);

                $sitemap_config = [
                    'sitemap' => ($_POST['website_sitemap'] ?? 'false') === 'true',
                    'website_domain' => trim($_POST['website_domain'] ?? ''),
                ];
                $sitemap_file = __DIR__ . '/../data/sitemap-config.php';
                $sitemap_content = "<?php\nreturn " . var_export($sitemap_config, true) . ";\n";
                @file_put_contents($sitemap_file, $sitemap_content);

                if (save_page('settings', $settings)) {
                    $_SESSION['admin_msg'] = 'Settings have been updated!';
                    $_SESSION['admin_msg_type'] = 'success';
                } else {
                    $_SESSION['admin_msg'] = 'Error saving settings.';
                    $_SESSION['admin_msg_type'] = 'error';
                }
                break;

            case 'add_product':
                $title = $_POST['product_title'] ?? '';
                $slug = !empty($_POST['product_slug']) ? slugify($_POST['product_slug']) : slugify($title);
                
                if (empty($title)) {
                    $_SESSION['admin_msg'] = 'Title is required.';
                    $_SESSION['admin_msg_type'] = 'error';
                } else {
                    if (find_product_by_slug($slug)) {
                        $slug = $slug . '-' . time();
                    }
                    $product_id = 'product-' . $slug;
                    $data = [
                        'id' => $product_id,
                        'slug' => $slug,
                        'title' => $title,
                        'description' => $_POST['product_description'] ?? '',
                        'blocks' => json_decode($_POST['blocks_data'] ?? '[]', true),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];

                    $data = ext_hook('before_save_page', $data);

                    if (save_page($product_id, $data)) {
                        ext_hook('after_save_page', $data);

                        $_SESSION['admin_msg'] = "Page \"$title\" has been created!";
                        $_SESSION['admin_msg_type'] = 'success';
                        $redirect_tab = 'products';
                    } else {
                        $_SESSION['admin_msg'] = 'Error creating page.';
                        $_SESSION['admin_msg_type'] = 'error';
                    }
                }
                break;

            case 'save_product':
                $product_id = $_POST['product_id'] ?? '';
                if (empty($product_id)) {
                    $_SESSION['admin_msg'] = 'Invalid page ID.';
                    $_SESSION['admin_msg_type'] = 'error';
                } else {
                    $data = [
                        'id' => $product_id,
                        'slug' => $_POST['product_slug'] ?? '',
                        'title' => $_POST['product_title'] ?? '',
                        'description' => $_POST['product_description'] ?? '',
                        'blocks' => json_decode($_POST['blocks_data'] ?? '[]', true),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $data = ext_hook('before_save_page', $data);

                    $existing = load_page($product_id);
                    if ($existing && isset($existing['created_at'])) {
                        $data['created_at'] = $existing['created_at'];
                    }
                    if (save_page($product_id, $data)) {
                        ext_hook('after_save_page', $data);

                        $_SESSION['admin_msg'] = 'Page has been updated!';
                        $_SESSION['admin_msg_type'] = 'success';
                    } else {
                        $_SESSION['admin_msg'] = 'Error saving page.';
                        $_SESSION['admin_msg_type'] = 'error';
                    }
                }
                break;

            case 'delete_product':
                $product_id = $_POST['product_id'] ?? '';
                $filepath = DATA_DIR . '/' . $product_id . '.php';
                
                if (file_exists($filepath) && unlink($filepath)) {
                    $_SESSION['admin_msg'] = 'Page has been deleted.';
                    $_SESSION['admin_msg_type'] = 'success';
                } else {
                    $_SESSION['admin_msg'] = 'Error deleting page.';
                    $_SESSION['admin_msg_type'] = 'error';
                }
                $redirect_tab = 'products';
                break;
        }
        
        header("Location: dashboard.php?tab=" . $redirect_tab);
        exit;
    }
}

// Load current data
$adminFile = DATA_DIR . '/admin.json';
$adminData = json_decode(file_get_contents($adminFile), true);
$index_data = load_page('index');
$site_settings = get_site_settings();
$products = get_all_products();
$all_pages = get_all_pages();
$csrf_token = generate_csrf_token();

// Get current tab
$tab = $_GET['tab'] ?? 'index';
$edit_product = null;
if ($tab === 'edit' && isset($_GET['id'])) {
    $edit_product = load_page($_GET['id']);
    if (!$edit_product) {
        $tab = 'products';
    }
}

// Default blocks for new pages
$default_index_blocks = [
    [
        'id' => 'hero',
        'type' => 'hero',
        'data' => [
            'badge' => 'Customize it for yourself',
            'title' => 'Build faster and easier',
            'subtitle' => 'Save time and create a website with AxElitus CMS.',
            'button_primary' => 'Start Now',
            'button_primary_url' => 'admin/',
            'button_secondary' => 'Learn more',
            'button_secondary_url' => '#',
        ]
    ],
    [
        'id' => 'features',
        'type' => 'features',
        'data' => [
            'title' => 'Powerful Features',
            'subtitle' => 'Everything you need to build and scale your web applications.',
            'items' => [
                ['icon' => 'bolt', 'title' => 'Fast & Lightweight', 'description' => 'Optimized for speed, so your pages load instantly.'],
                ['icon' => 'layout', 'title' => 'Block-Based Editing', 'description' => 'Simply add, move, and edit blocks to create your pages.'],
                ['icon' => 'code', 'title' => 'No Coding Required', 'description' => 'Build beautiful websites without any programming skills.'],
            ]
        ]
    ],
    [
        'id' => 'cta',
        'type' => 'cta',
        'data' => [
            'title' => 'Ready to Get Started?',
            'subtitle' => 'Create your website quickly and easily.',
            'button_primary' => 'Launch the dashboard',
            'button_primary_url' => '/admin',
            'button_secondary' => 'Learn more',
            'button_secondary_url' => '#',
        ]
    ],
];

$default_product_blocks = [
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= e($site_settings['site_name'] ?? SITE_NAME) ?></title>
    <link rel="icon" href="admin.ico?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>" type="image/x-icon">
    <link rel="preload" href="/assets/fonts/inter/inter.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/css/admin.css?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>">
    <script src="/assets/js/admin-theme.js?v=2"></script>
    <style>
        /* Editor with Preview Layout */
        .editor-with-preview {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 24px;
            align-items: start;
        }
        
        @media (max-width: 1400px) {
            .editor-with-preview {
                grid-template-columns: 1fr 350px;
            }
        }
        
        @media (max-width: 1200px) {
            .editor-with-preview {
                grid-template-columns: 1fr;
            }
            .preview-panel {
                display: none;
            }
        }
        
        .editor-panel {
            min-width: 0;
        }
        
        .preview-panel {
            position: sticky;
            top: 24px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            height: calc(100vh - 180px);
            min-height: 500px;
            max-height: 800px;
            display: flex;
            flex-direction: column;
        }
        
        .preview-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            background: var(--bg);
            flex-shrink: 0;
        }
        
        .preview-toolbar-left {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-muted);
        }
        
        .preview-toolbar-left svg {
            opacity: 0.7;
        }
        
        .preview-toolbar-right {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .preview-device-btn {
            display: none;
            padding: 6px !important;
            opacity: 0.5;
            transition: opacity 0.2s;
        }
        
        .preview-device-btn:hover {
            opacity: 0.8;
        }
        
        .preview-device-btn.active {
            opacity: 1;
            background: var(--border);
        }
        
        .preview-container {
            flex: 1;
            overflow: hidden;
            background: #111111;
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        
        .preview-frame-wrapper {
            width: 100%;
            height: 100%;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            transition: width 0.3s ease, max-width 0.3s ease;
        }
        
        .preview-frame-wrapper.tablet {
            max-width: 768px;
        }
        
        .preview-frame-wrapper.mobile {
            max-width: 375px;
        }
        
        .preview-frame-wrapper iframe {
            width: 100%;
            height: 100%;
            border: none;
            transform-origin: top left;
        }
        
        /* Loading state for preview */
        .preview-frame-wrapper.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 24px;
            height: 24px;
            margin: -12px 0 0 -12px;
            border: 2px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>

    <?php
        $ext_assets = $GLOBALS['extensions']->getFrontendAssets('admin');
        foreach ($ext_assets['css'] as $css): ?>
            <link rel="stylesheet" href="<?= e($css) ?>">
        <?php endforeach;
        echo ext_hook('admin_head', '');
    ?>
</head>
<body>
    <?php echo ext_hook('before_header', ''); ?>

    <div class="app">
        <!-- Sidebar -->
        <aside class="sidebar">

            <button class="mobile-nav-toggle" onclick="document.querySelector('.sidebar').classList.remove('open')">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="6" y1="6" x2="18" y2="18"/>
                    <line x1="6" y1="18" x2="18" y2="6"/>
                </svg>
            </button>

            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="/logos/logo.webp" alt="Logo" style="width: 20px;">
                    <span>AxElitus CMS</span>
                </div>
            </div>


            <?php
                $nav_links = ext_hook('modify_nav_links', $nav_links);
            ?>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-label">Content</div>
                    <a href="?tab=index" class="nav-item <?= $tab === 'index' ? 'active' : '' ?>">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Główna
                    </a>
                    <a href="?tab=products" class="nav-item <?= $tab === 'products' || $tab === 'edit' ? 'active' : '' ?>">
                        <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"> <rect x="2" y="3" width="20" height="18" rx="2"></rect> <rect x="3" y="4" width="18" height="6" fill="currentColor" opacity="0.3" stroke="none"></rect> <line x1="6" y1="14" x2="14" y2="14"></line> <line x1="6" y1="17" x2="11" y2="17"></line> </svg>
                        Strony
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-label">Site</div>
                    <a href="?tab=settings" class="nav-item <?= $tab === 'settings' ? 'active' : '' ?>">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Ustawienia
                    </a>
                    <a href="themes.php" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7h-3a2 2 0 0 1-2-2V2"/><path d="M9 18a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h7l4 4v10a2 2 0 0 1-2 2Z"/><path d="M3 7.6v12.8A1.6 1.6 0 0 0 4.6 22h9.8"/></svg>
                        Wygląd
                    </a>
                    <a href="extensions.php" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Rozszerzenia
                    </a>
                <?php if (in_array('popflow', $activeExtensions, true)) : ?>
                    <a href="popups.php" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 8V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6"/>
                            <rect x="13" y="12" width="8" height="9" rx="1"/>
                            <line x1="17" y1="15" x2="17" y2="15"/>
                        </svg>
                        Wyskakujące okna
                    </a>
                <?php endif; ?>
                </div>

                <div class="nav-section">
                    <div class="nav-label">Newsletter</div>
                    <a href="../newsletter/settings.php" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Ustawienia
                    </a>
                    <a href="../newsletter/manager.php" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"> <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path> <polyline points="22,6 12,13 2,6"></polyline> </svg>
                        Menedżer
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-label">Quick Actions</div>
                    <a href="?tab=new" class="nav-item <?= $tab === 'new' ? 'active' : '' ?>">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        Nowa strona
                    </a>
                    <a href="/" target="_blank" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        Pokaż stronę
                    </a>
                </div>
            </nav>

            <?php echo ext_hook('after_header', ''); ?>
            
            <div class="sidebar-footer">
                <a href="/admin/account" class="user-menu">
                    <div class="user-avatar">
                        <?= strtoupper(substr($adminData['username'], 0, 1)) ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name">
                            <?= e($adminData['username']) ?>
                        </div>
                        <div class="user-role">Ustawienia Konta</div>
                    </div>
                </a>
            </div>

            <?php
            $sidebar_items = ext_hook('admin_sidebar_items', []);
            if (!empty($sidebar_items)):
                foreach ($sidebar_items as $item): ?>
                    <a href="<?= e($item['url']) ?>" class="nav-item">
                        <?= $item['icon'] ?>
                        <?= e($item['label']) ?>
                    </a>
                <?php endforeach;
            endif;
            ?>
        </aside>
        
        <!-- Main Content -->
        <main class="main">
            <?php echo ext_hook('before_content', ''); ?>

            <header class="main-header">

                <button class="mobile-nav-toggle" onclick="document.querySelector('.sidebar').classList.add('open')">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>

                <div class="main-header-inner">
                    <h1>
                        <?php if ($tab === 'index'): ?>Edytuj główną
                        <?php elseif ($tab === 'products'): ?>Wszystkie strony
                        <?php elseif ($tab === 'new'): ?>Stwórz nową stronę
                        <?php elseif ($tab === 'settings'): ?>Ustawienia strony
                        <?php elseif ($tab === 'edit' && $edit_product): ?>Edytuj: <?= e($edit_product['title']) ?>
                        <?php endif; ?>
                    </h1>
                    <div class="header-actions">
                        <a class="btn btn-secondary btn-sm" href="javascript:void(0)" onclick="toggleTheme()" id="theme-toggle-btn">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            </svg>
                            <span>Motyw Jasny/Ciemny</span>
                        </a>
                    
                        <?php if ($tab === 'index'): ?>
                            <a href="/" target="_blank" class="btn btn-secondary btn-sm">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                Podgląd
                            </a>
                        <?php elseif ($tab === 'products'): ?>
                            <a href="?tab=new" class="btn btn-primary btn-sm">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Nowa strona
                            </a>
                        <?php elseif ($tab === 'edit' && $edit_product): ?>
                            <a href="/<?= e($edit_product['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                Podgląd
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            
            <div class="main-content">
                <?php if ($message): ?>
                    <div class="message <?= $message_type ?>">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="20" height="20">
                            <?php if ($message_type === 'success'): ?>
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                            <?php else: ?>
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            <?php endif; ?>
                        </svg>
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($tab === 'settings'): ?>
                    <!-- Site Settings -->
                    <form method="POST" action="" id="settingsForm">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        <input type="hidden" name="action" value="save_settings">
                        <input type="hidden" name="nav_links" id="navLinksData" value="">
                        <input type="hidden" name="nav_buttons" id="navButtonsData" value="">
                        <input type="hidden" name="footer_data" id="footerData" value="">
                        
                        <div class="settings-tabs">
                            <button type="button" class="settings-tab active" data-tab="general">General</button>
                            <button type="button" class="settings-tab" data-tab="navigation">Navigation</button>
                            <button type="button" class="settings-tab" data-tab="footer">Footer</button>
                            <button type="button" class="settings-tab" data-tab="custom">Custom</button>

                        </div>
                        
                        <!-- General Settings -->
                        <div class="settings-panel active" id="panel-general">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">General Settings</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Site Name</label>
                                            <input type="text" name="site_name" class="form-input" value="<?= e($site_settings['site_name'] ?? '') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Logo Text</label>
                                            <input type="text" name="logo_text" class="form-input" value="<?= e($site_settings['logo_text'] ?? '') ?>">
                                            <p class="form-hint">Displayed in header and footer</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Site Description</label>
                                        <textarea name="site_description" class="form-textarea"><?= e($site_settings['site_description'] ?? '') ?></textarea>
                                        <p class="form-hint">Used for SEO meta description</p>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Logo Image URL (optional)</label>
                                            <input type="text" name="logo_image" class="form-input" value="<?= e($site_settings['logo_image'] ?? '') ?>" placeholder="https://...">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Favicon URL (optional)</label>
                                            <input type="text" name="favicon" class="form-input" value="<?= e($site_settings['favicon'] ?? '') ?>" placeholder="https://...">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Website Font</label>
                                            <select name="website_font" class="form-input">
                                                <option value="Inter" <?= ($site_settings['website_font'] ?? 'Inter') === 'Inter' ? 'selected' : '' ?>>Inter</option>
                                                <option value="Arial" <?= ($site_settings['website_font'] ?? 'Inter') === 'Arial' ? 'selected' : '' ?>>Arial</option>
                                                <option value="Helvetica" <?= ($site_settings['website_font'] ?? 'Inter') === 'Helvetica' ? 'selected' : '' ?>>Helvetica</option>
                                                <option value="Times New Roman" <?= ($site_settings['website_font'] ?? 'Inter') === 'Times New Roman' ? 'selected' : '' ?>>Times New Roman</option>
                                                <option value="Georgia" <?= ($site_settings['website_font'] ?? 'Inter') === 'Georgia' ? 'selected' : '' ?>>Georgia</option>
                                                <option value="Courier New" <?= ($site_settings['website_font'] ?? 'Inter') === 'Courier New' ? 'selected' : '' ?>>Courier New</option>
                                                <option value="Verdana" <?= ($site_settings['website_font'] ?? 'Inter') === 'Verdana' ? 'selected' : '' ?>>Verdana</option>
                                                <option value="Trebuchet MS" <?= ($site_settings['website_font'] ?? 'Inter') === 'Trebuchet MS' ? 'selected' : '' ?>>Trebuchet MS</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Contact Email</label>
                                            <input type="email" name="contact_email" class="form-input" value="<?= e($site_settings['contact_email'] ?? '') ?>" placeholder="contact@example.com">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Website Sitemap</label>
                                            <select name="website_sitemap" class="form-input">
                                                <option value="true" <?= ($sitemap_settings['sitemap'] ?? false) ? 'selected' : '' ?>>Enabled</option>
                                                <option value="false" <?= !($sitemap_settings['sitemap'] ?? false) ? 'selected' : '' ?>>Disabled</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Website Domain</label>
                                            <input type="text" name="website_domain" class="form-input" value="<?= e($sitemap_settings['website_domain'] ?? '') ?>" placeholder="example.com">
                                        </div>
                                    </div>
                                    <br>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Show Recent Pages</label>
                                            <div class="form-switch">
                                                <input type="checkbox" name="recent_pages" id="recent_pages" value="true" <?= ($site_settings['recent_pages_enabled'] ?? false) ? 'checked' : '' ?>>
                                                <label for="recent_pages"></label>
                                            </div>
                                            <p class="form-hint">Display recently updated pages in the footer/navigation</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Contact Page</label>
                                            <div class="form-switch">
                                                <input type="checkbox" name="contact_page_enabled" id="contact_page_enabled" value="true" <?= ($site_settings['contact_page_enabled'] ?? false) ? 'checked' : '' ?>>
                                                <label for="contact_page_enabled"></label>
                                            </div>
                                            <p class="form-hint">Enable the contact page</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Navigation Settings -->
                        <div class="settings-panel" id="panel-navigation">
                            <div class="card" style="margin-bottom: 24px;">
                                <div class="card-header">
                                    <h3 class="card-title">Navigation Links</h3>
                                </div>
                                <div class="card-body">
                                    <div id="navLinksContainer"></div>
                                    <button type="button" class="repeater-add" onclick="addNavLink()">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        Add Link
                                    </button>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Navigation Buttons</h3>
                                </div>
                                <div class="card-body">
                                    <div id="navButtonsContainer"></div>
                                    <button type="button" class="repeater-add" onclick="addNavButton()">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        Add Button
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Footer Settings -->
                        <div class="settings-panel" id="panel-footer">
                            <div class="card" style="margin-bottom: 24px;">
                                <div class="card-header">
                                    <h3 class="card-title">Footer Brand</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label class="form-label">Brand Description</label>
                                        <textarea class="form-textarea" id="footerBrandDesc" oninput="updateFooterData()"><?= e($site_settings['footer']['brand_description'] ?? '') ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Copyright Text</label>
                                        <input type="text" class="form-input" id="footerCopyright" value="<?= e($site_settings['footer']['copyright'] ?? '') ?>" oninput="updateFooterData()">
                                    </div>
                                </div>
                                
                            </div>
                            
                            <div class="card" style="margin-bottom: 24px;">
                                <div class="card-header">
                                    <h3 class="card-title">Footer Columns</h3>
                                </div>
                                <div class="card-body">
                                    <div id="footerColumnsContainer"></div>
                                    <button type="button" class="repeater-add" onclick="addFooterColumn()">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        Add Column
                                    </button>
                                </div>
                            </div>
                            
                            <div class="card" style="margin-bottom: 24px;">
                                <div class="card-header">
                                    <h3 class="card-title">Social Links</h3>
                                </div>
                                <div class="card-body">
                                    <div id="socialLinksContainer"></div>
                                    <button type="button" class="repeater-add" onclick="addSocialLink()">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        Add Social Link
                                    </button>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Bottom Links</h3>
                                </div>
                                <div class="card-body">
                                    <div id="bottomLinksContainer"></div>
                                    <button type="button" class="repeater-add" onclick="addBottomLink()">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        Add Link
                                    </button>
                                </div>
                            </div>
                        </div>


                        <!-- Custom Settings -->
                        <div class="settings-panel" id="panel-custom">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Custom Settings</h3>
                                </div>

                                <?php echo ext_hook('before_custom_settings', ''); ?>
                                <div class="card-body" id="custom-settings-content">
                                </div>

                                <div class="card-body" id="no-custom-settings">
                                    <p>No custom settings available.</p>
                                    <a href="extensions.php" style="color: #4da3ff; text-decoration: underline; display: inline-flex; align-items: center; gap: 4px;">
                                        <div style="width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16">
                                                <rect x="3" y="3" width="7" height="7"/>
                                                <rect x="14" y="3" width="7" height="7"/>
                                                <rect x="14" y="14" width="7" height="7"/>
                                                <rect x="3" y="14" width="7" height="7"/>
                                            </svg>
                                        </div>
                                        Go to Extensions Manager
                                    </a>
                                    <script>
                                    document.addEventListener("DOMContentLoaded", function () {
                                        const content = document.getElementById("custom-settings-content");
                                        const emptyInfo = document.getElementById("no-custom-settings");

                                        if (!content || !emptyInfo) return;

                                        const hasContent = content.innerHTML.trim() !== "";

                                        content.style.display = hasContent ? "" : "none";
                                        emptyInfo.style.display = hasContent ? "none" : "";
                                    });
                                    </script>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
                            <button type="submit" class="btn btn-primary">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                Save Settings
                            </button>
                        </div>
                    </form>

                <?php elseif ($tab === 'index'): ?>
                    <!-- Homepage Block Editor with Mini Preview -->
                    <form method="POST" action="" id="pageForm">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        <input type="hidden" name="action" value="save_index">
                        <input type="hidden" name="blocks_data" id="blocksData" value="">
                        
                        <div class="card" style="margin-bottom: 24px;">
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Page Title</label>
                                        <input type="text" name="page_title" class="form-input" value="<?= e($index_data['title'] ?? SITE_NAME) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Meta Description</label>
                                        <input type="text" name="page_description" class="form-input" value="<?= e($index_data['description'] ?? '') ?>" placeholder="SEO description...">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="editor-with-preview">
                            <div class="editor-panel">
                                <div class="editor-wrapper">
                                    <div class="editor-toolbar">
                                        <div class="editor-toolbar-left">
                                            <span style="font-size: 0.8125rem; color: var(--text-muted);">
                                                <span id="blockCount">0</span> blocks
                                            </span>
                                        </div>
                                        <div class="editor-toolbar-right">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="expandAllBlocks()">Expand All</button>
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="collapseAllBlocks()">Collapse All</button>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                                Save Changes
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="blocks-container" id="blocksContainer"></div>
                                    
                                    <div style="padding: 0 24px 24px;">
                                        <button type="button" class="add-block-btn" onclick="showAddBlockModal()">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="20" height="20"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                            Add Block
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="preview-panel">
                                <div class="preview-toolbar">
                                    <div class="preview-toolbar-left">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <span>Mini Preview</span>
                                    </div>
                                    <div class="preview-toolbar-right">
                                        <button type="button" class="btn btn-ghost btn-sm preview-device-btn active" data-device="desktop" onclick="setPreviewDevice('desktop')" title="Desktop">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                        </button>
                                        <button type="button" class="btn btn-ghost btn-sm preview-device-btn" data-device="tablet" onclick="setPreviewDevice('tablet')" title="Tablet">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                                        </button>
                                        <button type="button" class="btn btn-ghost btn-sm preview-device-btn" data-device="mobile" onclick="setPreviewDevice('mobile')" title="Mobile">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                                        </button>
                                        <a href="/" target="_blank" class="btn btn-ghost btn-sm" title="Open in New Tab">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        </a>
                                    </div>
                                </div>
                                <div class="preview-container" id="previewContainer">
                                    <div class="preview-frame-wrapper" id="previewFrameWrapper">
                                        <iframe id="previewFrame" src="" title="Page Preview"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                <?php elseif ($tab === 'products'): ?>
                    <!-- Products List -->
                    <div class="card">
                        <?php if (empty($products)): ?>
                            <div class="empty-state">
                                <svg class="empty-state-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                                <h3>No pages yet</h3>
                                <p>Create your first page to get started.</p>
                                <a href="?tab=new" class="btn btn-primary">Create Page</a>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>URL</th>
                                        <th>Updated</th>
                                        <th style="width: 150px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <div class="product-title"><?= e($product['title']) ?></div>
                                            </td>
                                            <td>
                                                <span class="product-slug">/<?= e($product['slug']) ?></span>
                                            </td>
                                            <td>
                                                <span class="product-date"><?= date('M j, Y', strtotime($product['updated_at'] ?? $product['created_at'] ?? 'now')) ?></span>
                                            </td>
                                            <td>
                                                <div class="product-actions">
                                                    <a href="?tab=edit&id=<?= e($product['id']) ?>" class="btn btn-secondary btn-sm">Edit</a>
                                                    <a href="/<?= e($product['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm">View</a>
                                                    <button type="button" class="btn btn-ghost btn-sm" onclick="confirmDelete('<?= e($product['id']) ?>', '<?= e(addslashes($product['title'])) ?>')" style="color: var(--error);">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                <?php elseif ($tab === 'new'): ?>
                    <!-- New Product Form -->
                    <form method="POST" action="" id="pageForm">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        <input type="hidden" name="action" value="add_product">
                        <input type="hidden" name="blocks_data" id="blocksData" value="">
                        
                        <div class="card" style="margin-bottom: 24px;">
                            <div class="card-body">
                                <div class="form-row-3">
                                    <div class="form-group">
                                        <label class="form-label">Page Title *</label>
                                        <input type="text" name="product_title" class="form-input" required placeholder="My Awesome Page">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">URL Slug</label>
                                        <input type="text" name="product_slug" class="form-input" placeholder="auto-generated">
                                        <p class="form-hint">Leave empty to auto-generate from title</p>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Meta Description</label>
                                        <input type="text" name="product_description" class="form-input" placeholder="SEO description...">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="editor-wrapper">
                            <div class="editor-toolbar">
                                <div class="editor-toolbar-left">
                                    <span style="font-size: 0.8125rem; color: var(--text-muted);">
                                        <span id="blockCount">0</span> blocks
                                    </span>
                                </div>
                                <div class="editor-toolbar-right">
                                    <button type="button" class="btn btn-ghost btn-sm" onclick="expandAllBlocks()">Expand All</button>
                                    <button type="button" class="btn btn-ghost btn-sm" onclick="collapseAllBlocks()">Collapse All</button>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                        Create Page
                                    </button>
                                </div>
                            </div>
                            
                            <div class="blocks-container" id="blocksContainer"></div>
                            
                            <div style="padding: 0 24px 24px;">
                                <button type="button" class="add-block-btn" onclick="showAddBlockModal()">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="20" height="20"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    Add Block
                                </button>
                            </div>
                        </div>
                    </form>

                <?php elseif ($tab === 'edit' && $edit_product): ?>
                    <!-- Edit Product Form with Mini Preview -->
                    <form method="POST" action="" id="pageForm">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        <input type="hidden" name="action" value="save_product">
                        <input type="hidden" name="product_id" value="<?= e($edit_product['id']) ?>">
                        <input type="hidden" name="blocks_data" id="blocksData" value="">
                        
                        <div class="card" style="margin-bottom: 24px;">
                            <div class="card-body">
                                <div class="form-row-3">
                                    <div class="form-group">
                                        <label class="form-label">Page Title *</label>
                                        <input type="text" name="product_title" class="form-input" value="<?= e($edit_product['title']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">URL Slug</label>
                                        <input type="text" name="product_slug" class="form-input" value="<?= e($edit_product['slug']) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Meta Description</label>
                                        <input type="text" name="product_description" class="form-input" value="<?= e($edit_product['description'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="editor-with-preview">
                            <div class="editor-panel">
                                <div class="editor-wrapper">
                                    <div class="editor-toolbar">
                                        <div class="editor-toolbar-left">
                                            <span style="font-size: 0.8125rem; color: var(--text-muted);">
                                                <span id="blockCount">0</span> blocks
                                            </span>
                                        </div>
                                        <div class="editor-toolbar-right">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="expandAllBlocks()">Expand All</button>
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="collapseAllBlocks()">Collapse All</button>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                                Save Changes
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="blocks-container" id="blocksContainer"></div>
                                    
                                    <div style="padding: 0 24px 24px;">
                                        <button type="button" class="add-block-btn" onclick="showAddBlockModal()">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="20" height="20"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                            Add Block
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="preview-panel">
                                <div class="preview-toolbar">
                                    <div class="preview-toolbar-left">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <span>Mini Preview</span>
                                    </div>
                                    <div class="preview-toolbar-right">
                                        <button type="button" class="btn btn-ghost btn-sm preview-device-btn active" data-device="desktop" onclick="setPreviewDevice('desktop')" title="Desktop">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                        </button>
                                        <button type="button" class="btn btn-ghost btn-sm preview-device-btn" data-device="tablet" onclick="setPreviewDevice('tablet')" title="Tablet">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                                        </button>
                                        <button type="button" class="btn btn-ghost btn-sm preview-device-btn" data-device="mobile" onclick="setPreviewDevice('mobile')" title="Mobile">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                                        </button>
                                        <a href="/<?= e($edit_product['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm" title="Open in New Tab">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        </a>
                                    </div>
                                </div>
                                <div class="preview-container" id="previewContainer">
                                    <div class="preview-frame-wrapper" id="previewFrameWrapper">
                                        <iframe id="previewFrame" src="" title="Page Preview"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <?php echo ext_hook('after_content', ''); ?>
        </main>
    </div>

    <!-- Add Block Modal -->
    <div class="modal-overlay" id="addBlockModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add Block</h3>
                <button type="button" class="modal-close" onclick="hideAddBlockModal()">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="20" height="20"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="block-types-grid">
                    <div class="block-type-card" onclick="addBlock('hero')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"> <rect x="2" y="3" width="20" height="18" rx="2"/> <rect x="3" y="4" width="18" height="6" fill="currentColor" opacity="0.1" stroke="none"/> <line x1="6" y1="14" x2="14" y2="14"/> <line x1="6" y1="17" x2="11" y2="17"/> </svg>
                        <h4>Hero</h4>
                        <p>Full-width header section</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('stats')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        <h4>Stats</h4>
                        <p>Numbers and metrics</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('features')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        <h4>Features</h4>
                        <p>Feature cards grid</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('testimonials')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <h4>Testimonials</h4>
                        <p>Customer reviews</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('pricing')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        <h4>Pricing</h4>
                        <p>Pricing table</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('cta')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <h4>CTA</h4>
                        <p>Call to action</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('text')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <h4>Text</h4>
                        <p>Rich text content</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('image')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <h4>Image</h4>
                        <p>Full-width image</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('image-text')">
                        <svg class="w-6 h-6 flex-shrink-0 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"> <rect x="3" y="3" width="18" height="10" rx="2"/> <path d="M3 10l4-3 4 3 6-4 4 3"/> <line x1="5" y1="16" x2="19" y2="16"/> <line x1="5" y1="19" x2="15" y2="19"/> </svg>
                        <h4>Image + Text</h4>
                        <p>Image with text content</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('product-cards')">
                        <svg class="w-6 h-6 flex-shrink-0 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"> <path d="M3 7v10c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2z"/> <path d="M3 9h18"/> <circle cx="8" cy="14" r="1" fill="currentColor"/> <line x1="11" y1="14" x2="16" y2="14"/> </svg>
                        <h4>Product Cards</h4>
                        <p>Product showcase grid</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('video')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                        <h4>Video</h4>
                        <p>Video player (URL, YouTube, Facebook)</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('gallery')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                        <h4>Gallery</h4>
                        <p>Image gallery grid</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('faq')">
                        <svg class="w-6 h-6 flex-shrink-0 mt-1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"> <circle cx="12" cy="12" r="10"/> <path d="M10 10a2 2 0 1 1 4 0c0 2-2 2-2 3.5"/> <line x1="12" y1="17" x2="12" y2="17"/> </svg>
                        <h4>FAQ</h4>
                        <p>Frequently Asked Questions</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('team')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <h4>Team</h4>
                        <p>Team members showcase</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('audio')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                        <h4>Audio</h4>
                        <p>Audio player with music link</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('countdown')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <h4>Countdown</h4>
                        <p>Countdown timer to date</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('newsletter')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <h4>Newsletter</h4>
                        <p>Email subscription form</p>
                    </div>
                    <div class="block-type-card" onclick="addBlock('html')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                        <h4>Custom HTML</h4>
                        <p>Raw HTML code</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">Delete Page</h3>
                <button type="button" class="modal-close" onclick="hideDeleteModal()">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="20" height="20"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <p style="color: var(--text-muted);">Are you sure you want to delete "<span id="deleteProductName"></span>"? This action cannot be undone.</p>
                <form method="POST" action="" id="deleteForm">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                    <input type="hidden" name="action" value="delete_product">
                    <input type="hidden" name="product_id" id="deleteProductId" value="">
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="hideDeleteModal()">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Link Selector Modal -->
    <div class="modal-overlay" id="linkSelectorModal">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">Select Link</h3>
                <button type="button" class="modal-close" onclick="hideLinkSelector()">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="20" height="20"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Internal Pages</label>
                    <div id="internalPages"></div>
                </div>
                <div class="form-group" style="margin-top: 16px;">
                    <label class="form-label">Anchors</label>
                    <div id="anchorsList" style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <!-- Dynamically populated -->
                    </div>
                </div>
                <div class="form-group" style="margin-top: 16px;">
                    <label class="form-label">Custom URL</label>
                    <input type="text" class="form-input" id="customLinkUrl" placeholder="https://example.com">
                    <button type="button" class="btn btn-primary btn-sm" style="margin-top: 8px;" onclick="selectLink(document.getElementById('customLinkUrl').value)">Use Custom URL</button>
                </div>
            </div>
        </div>
    </div>

    <?php
    foreach ($ext_assets['js'] as $js): ?>
        <script src="<?= e($js) ?>"></script>
    <?php endforeach; ?>

   <?php include __DIR__ . '/scripts.php'; ?>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if (typeof updateLivePreview === "function") {
                    updateLivePreview();
                }
            }, 1000); 
        });
   </script>

   <script src="/assets/js/admin-editor.js?v=2x72"></script>

   <script src="easyedit.js?v=<?= date('YmdH') ?>"></script>

</body>
</html>