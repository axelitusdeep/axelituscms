<?php
// Define base directory
define('BASE_DIR', __DIR__);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

// Site Configuration
define('SITE_NAME', 'AxElitus CMS');
define('SITE_URL', $protocol . $host . $basePath);
define('DATA_DIR', __DIR__ . '/data/');
define('ADMIN_DIR', __DIR__ . '/admin/');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');

if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

function generate_csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verify_csrf_token(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function is_logged_in(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /admin');
        exit;
    }
    check_ip_ban();
}

function check_ip_ban(): void {
    static $tracker = null;
    
    if ($tracker === null) {
        require_once __DIR__ . '/admin/login_tracking.php';
        $tracker = new LoginTracker();
    }
    
    $clientIP = $tracker->getClientIP();
    
    if ($tracker->isIPBanned($clientIP)) {
        session_unset();
        session_destroy();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        http_response_code(403);
        exit('Access forbidden.');
    }
}

function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

function load_page(string $filename): ?array {
    $filepath = DATA_DIR . $filename . '.php';
    if (file_exists($filepath)) {
        return include $filepath;
    }
    return null;
}

function save_page(string $filename, array $data): bool {
    $filepath = DATA_DIR . $filename . '.php';
    $content = "<?php\nreturn " . var_export($data, true) . ";\n";
    return file_put_contents($filepath, $content) !== false;
}

function get_site_settings(): array {
    $settings = load_page('settings');
    return $settings ?? get_default_site_settings();
}

function get_default_site_settings(): array {
    return [
        'site_name' => SITE_NAME,
        'site_description' => 'Build amazing websites with ease',
        'logo_text' => SITE_NAME,
        'logo_image' => '',
        'favicon' => '',
        'primary_color' => '#ffffff',
        'nav_links' => [
            ['label' => 'Features', 'url' => '#features'],
            ['label' => 'Testimonials', 'url' => '#testimonials'],
            ['label' => 'Pricing', 'url' => '#pricing'],
        ],
        'nav_buttons' => [
            ['label' => 'Log in', 'url' => '#', 'style' => 'ghost'],
            ['label' => 'Get Started', 'url' => '#', 'style' => 'primary'],
        ],
        'footer' => [
            'brand_description' => 'A lightweight, self-hosted CMS that lets you create and manage websites with ease.',
            'columns' => [
                [
                    'title' => 'Product',
                    'links' => [
                        ['label' => 'Features', 'url' => '#features'],
                        ['label' => 'Pricing', 'url' => '#pricing'],
                        ['label' => 'Integrations', 'url' => '#'],
                    ]
                ],
                [
                    'title' => 'Company',
                    'links' => [
                        ['label' => 'About', 'url' => '#'],
                        ['label' => 'Blog', 'url' => '#'],
                        ['label' => 'Careers', 'url' => '#'],
                    ]
                ],
                [
                    'title' => 'Resources',
                    'links' => [
                        ['label' => 'Documentation', 'url' => '#'],
                        ['label' => 'Guides', 'url' => '#'],
                        ['label' => 'Support', 'url' => '#'],
                    ]
                ],
                [
                    'title' => 'Legal',
                    'links' => [
                        ['label' => 'Privacy', 'url' => '#'],
                        ['label' => 'Terms', 'url' => '#'],
                    ]
                ],
            ],
            'social_links' => [
                ['platform' => 'twitter', 'url' => '#'],
                ['platform' => 'github', 'url' => '#'],
                ['platform' => 'linkedin', 'url' => '#'],
            ],
            'copyright' => '© ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.',
            'bottom_links' => [
                ['label' => 'Privacy Policy', 'url' => '#'],
                ['label' => 'Terms of Service', 'url' => '#'],
            ],
        ],
    ];
}

function get_all_products(): array {
    $products = [];
    $files = glob(DATA_DIR . 'product-*.php');
    foreach ($files as $file) {
        $data = include $file;
        if (is_array($data)) {
            $products[] = $data;
        }
    }

    usort($products, function($a, $b) {
        return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
    });
    return $products;
}

function find_product_by_slug(string $slug): ?array {
    $products = get_all_products();
    foreach ($products as $product) {
        if (isset($product['slug']) && $product['slug'] === $slug) {
            return $product;
        }
    }
    return null;
}

function get_all_pages(): array {
    $pages = [
        ['label' => 'Homepage', 'url' => '/'],
    ];
    
    $products = get_all_products();
    foreach ($products as $product) {
        $pages[] = [
            'label' => $product['title'] ?? 'Product',
            'url' => '/' . ($product['slug'] ?? ''),
        ];
    }
    
    return $pages;
}

function getPages(): array {
    return get_all_products();
}

function getSiteSettings(): array {
    return get_site_settings();
}

function getNavigation(): array {
    $settings = get_site_settings();
    return [
        'items' => $settings['nav_links'] ?? [],
    ];
}

function getFooterSettings(): array {
    $settings = get_site_settings();
    $footer = $settings['footer'] ?? [];
    
    return [
        'description' => $footer['brand_description'] ?? 'A lightweight, self-hosted CMS that lets you create and manage websites with ease..',
        'social_links' => $footer['social_links'] ?? [],
        'columns' => $footer['columns'] ?? [],
        'copyright' => $footer['copyright'] ?? '© ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.',
        'bottom_links' => $footer['bottom_links'] ?? [],
    ];
}

function sanitize(string $input): string {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

// Load extensions system
require_once __DIR__ . '/extensions-loader.php';

// Load block rendering engine
require_once BASE_DIR . '/engine/renderion.php';