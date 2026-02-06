<?php
header('Content-Type: text/plain; charset=UTF-8');

$configPath = __DIR__ . '/data/sitemap-config.php';

$config = [
    'sitemap' => false,
    'website_domain' => '',
];

if (is_file($configPath)) {
    $loaded = require $configPath;
    if (is_array($loaded)) {
        $config = array_merge($config, $loaded);
    }
}

echo "User-agent: *\n";
echo "Allow: /\n";

$disallowPaths = [
    '/admin/',
    '/config/',
    '/data/',
    '/private/',
    '/tmp/',
    '/cache/',
    '/logs/',
    '/vendor/',
    '/tests/',
    '/.env',
    '/composer.json',
    '/composer.lock',
    '/extensions/',
    '/themes/',
];

foreach ($disallowPaths as $path) {
    echo "Disallow: {$path}\n";
}

// Sitemap
if ($config['sitemap'] === true && !empty($config['website_domain'])) {
    $domain = trim($config['website_domain']);
    if (!preg_match('#^https?://#i', $domain)) {
        $domain = 'https://' . $domain;
    }
    $domain = rtrim($domain, '/');
    echo "\nSitemap: {$domain}/sitemap\n";
}
