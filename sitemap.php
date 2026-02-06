<?php
// AxElitus CMS - 動的サイトマップジェネレーター
$config = require __DIR__ . '/data/sitemap-config.php';

if (!isset($config['sitemap']) || $config['sitemap'] !== true) {
    http_response_code(404);
    exit('Sitemap disabled');
}

@ini_set('display_errors', '0');
@error_reporting(0);

if (function_exists('ob_get_level')) {
    while (@ob_get_level()) @ob_end_clean();
}

ob_start();

@header('Content-Type: application/xml; charset=utf-8');
@header('X-Content-Type-Options: nosniff');
@header('X-Frame-Options: DENY');

// ===== RATE LIMITING (防止 DoS) =====
$rate_limit_file = sys_get_temp_dir() . '/sitemap_rate_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$now = time();
$max_requests = 10;
$time_window = 60;
$access_times = [];

if (@file_exists($rate_limit_file)) {
    $content = @file_get_contents($rate_limit_file);
    $access_times = $content ? @unserialize($content) : [];
    if (!is_array($access_times)) {
        $access_times = [];
    }
}

$access_times = array_filter($access_times, function($timestamp) use ($now, $time_window) {
    return ($now - $timestamp) < $time_window;
});

if (count($access_times) >= $max_requests) {
    http_response_code(429);
    exit('Too many requests');
}

$access_times[] = $now;

@file_put_contents($rate_limit_file, @serialize($access_times));


function safe_xml($str) {
    return htmlspecialchars((string)$str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function safe_slug($slug) {
    $slug = strtolower(trim((string)$slug, '/'));
    $slug = preg_replace('/[^a-z0-9\-_]/', '', $slug); 
    return substr($slug, 0, 100); 
}

function safe_date($file, $base_dir) {
    $real_file = @realpath($file);
    $real_base = @realpath($base_dir);
    
    if (!$real_file || !$real_base || strpos($real_file, $real_base) !== 0) {
        return gmdate('Y-m-d');
    }
    
    if (@file_exists($real_file)) {
        return gmdate('Y-m-d', @filemtime($real_file));
    }
    return gmdate('Y-m-d');
}

function validate_url($url, $allowed_domain) {
    $parsed = @parse_url($url);
    
    if (!$parsed || !isset($parsed['host'])) {
        return false;
    }
    
    if ($parsed['host'] !== $allowed_domain) {
        return false;
    }
    
    if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
        return false;
    }
    
    return true;
}

$allowed_hosts = [
    $config['website_domain'],
    'www.' . $config['website_domain']
];

$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$host = preg_replace('/:\d+$/', '', $host);

if (!in_array($host, $allowed_hosts)) {
    http_response_code(400);
    exit('Invalid host');
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$base_url = $protocol . '://' . $host;


$pages = [];
$base_dir = __DIR__;

$pages[] = [
    'loc' => $base_url . '/',
    'lastmod' => gmdate('Y-m-d'),
    'priority' => '1.0'
];

$data_dir = $base_dir . '/data/';
$real_data_dir = @realpath($data_dir);
$real_base_dir = @realpath($base_dir);

if ($real_data_dir && $real_base_dir && 
    strpos($real_data_dir, $real_base_dir) === 0 && 
    @is_dir($real_data_dir)) {
    
    $pattern = $real_data_dir . '/product-*.php';
    $files = @glob($pattern);
    
    if (is_array($files)) {
        $count = 0;
        
        foreach ($files as $file) {
            $real_file = @realpath($file);
            
            if (!$real_file || strpos($real_file, $real_data_dir) !== 0) {
                continue;
            }
            
            $name = basename($real_file, '.php');
            
            if (!preg_match('/^product-[a-z0-9\-]{1,50}$/', $name)) {
                continue;
            }
            
            $slug = str_replace('product-', '', $name);
            $slug = safe_slug($slug);
            
            if (!$slug) {
                continue;
            }
            
            $url = $base_url . '/' . $slug;
            
            if (!validate_url($url, $host)) {
                continue;
            }
            
            $pages[] = [
                'loc' => $url,
                'lastmod' => safe_date($real_file, $real_data_dir),
                'priority' => '0.8'
            ];
            
            $count++;
            
            if ($count >= 500) {
                break;
            }
        }
    }
}

// ===== XML 生成器 =====

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";

foreach ($pages as $p) {
    echo '<url>';
    echo '<loc>' . safe_xml($p['loc']) . '</loc>';
    echo '<lastmod>' . safe_xml($p['lastmod']) . '</lastmod>';
    echo '<changefreq>weekly</changefreq>';
    echo '<priority>' . safe_xml($p['priority']) . '</priority>';
    echo '</url>', "\n";
}

echo '</urlset>';

ob_end_flush();
exit;
?>