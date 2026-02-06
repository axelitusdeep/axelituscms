<?php
// ============================================================================
// Renderion Engine BlocX
// ============================================================================

if (!defined('BASE_DIR')) {
    die('Direct access not permitted');
}

final class RenderCache {
    private static $hits = 0;
    private static $misses = 0;
    private const MAX_VALUE_SIZE = 102400;
    private const CACHE_DIR = BASE_DIR . '/cache/html/';
    private const CACHE_TTL = 3600;
    
    private static function ensureCacheDir() {
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
    }
    
    private static function generateSecureKey($data) {
        $serialized = serialize($data);
        return hash('sha256', $serialized);
    }
    
    private static function getCacheFilePath($key) {
        $secure_key = self::generateSecureKey($key);
        return self::CACHE_DIR . $secure_key . '.html';
    }
    
    private static function isCacheExpired($file_path) {
        if (!file_exists($file_path)) {
            return true;
        }
        
        $file_time = filemtime($file_path);
        $current_time = time();
        
        return ($current_time - $file_time) > self::CACHE_TTL;
    }
    
    private static function isValidCachedContent($value) {
        if (!is_string($value)) {
            return false;
        }
        
        if (strlen($value) > self::MAX_VALUE_SIZE) {
            return false;
        }
        
        $dangerous_patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*srcdoc/i',
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return false;
            }
        }
        
        return true;
    }
    
    public static function get($key) {
        self::ensureCacheDir();
        
        $cache_file = self::getCacheFilePath($key);
        
        if (self::isCacheExpired($cache_file)) {
            if (file_exists($cache_file)) {
                @unlink($cache_file);
            }
            self::$misses++;
            return null;
        }
        
        $value = file_get_contents($cache_file);
        
        if ($value !== false && self::isValidCachedContent($value)) {
            self::$hits++;
            return $value;
        } else {
            @unlink($cache_file);
            self::$misses++;
            return null;
        }
    }
    
    public static function set($key, $value) {
        if (!is_string($value) || strlen($value) > self::MAX_VALUE_SIZE) {
            return false;
        }
        
        self::ensureCacheDir();
        
        $cache_file = self::getCacheFilePath($key);
        
        $result = file_put_contents($cache_file, $value, LOCK_EX);
        
        if ($result !== false) {
            touch($cache_file);
        }
        
        return $result !== false;
    }
    
    public static function clear() {
        self::ensureCacheDir();
        
        $files = glob(self::CACHE_DIR . '*.html');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
        
        self::$hits = 0;
        self::$misses = 0;
    }
    
    public static function clearExpired() {
        self::ensureCacheDir();
        
        $files = glob(self::CACHE_DIR . '*.html');
        $deleted = 0;
        
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file) && self::isCacheExpired($file)) {
                    if (@unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }
        
        return $deleted;
    }
    
    public static function clearBySlug($slug) {
        self::ensureCacheDir();
        
        $pattern = self::CACHE_DIR . '*' . preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) . '*.html';
        $files = glob($pattern);
        
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }
    
    public static function getStats() {
        self::ensureCacheDir();
        
        $files = glob(self::CACHE_DIR . '*.html');
        $size = $files ? count($files) : 0;
        
        $total_size = 0;
        $expired = 0;
        $valid = 0;
        
        if ($files) {
            foreach ($files as $file) {
                $total_size += filesize($file);
                
                if (self::isCacheExpired($file)) {
                    $expired++;
                } else {
                    $valid++;
                }
            }
        }
        
        return [
            'hits' => self::$hits,
            'misses' => self::$misses,
            'total_files' => $size,
            'valid_files' => $valid,
            'expired_files' => $expired,
            'total_bytes' => $total_size,
            'ttl_seconds' => self::CACHE_TTL
        ];
    }
}

// ============================================================================
// LAZY LOADING MANAGER
// ============================================================================

final class LazyLoadManager {
    private static $lazy_blocks = [];
    private static $script_added = false;
    private const MAX_LAZY_BLOCKS = 50;
    private const MAX_BLOCK_SIZE = 51200; // 50KB per block
    
    private static function generateSecureBlockId($block) {
        $data = serialize($block);
        return 'block_' . hash('sha256', $data . random_bytes(16));
    }
    
    private static function sanitizeLazyHTML($html) {
        if (!is_string($html)) {
            return '';
        }
        
        if (strlen($html) > self::MAX_BLOCK_SIZE) {
            return '';
        }
        
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        
        $html = preg_replace('/javascript:/i', '', $html);
        
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        
        return $html;
    }
    
    public static function registerLazyBlock($block_data, $rendered_html) {
        if (count(self::$lazy_blocks) >= self::MAX_LAZY_BLOCKS) {
            return null;
        }
        
        $sanitized_html = self::sanitizeLazyHTML($rendered_html);
        if (empty($sanitized_html)) {
            return null;
        }
        
        $block_id = self::generateSecureBlockId($block_data);
        self::$lazy_blocks[$block_id] = $sanitized_html;
        
        return $block_id;
    }
    
    public static function getLazyPlaceholder($block_id, $type) {
        if (!preg_match('/^block_[a-f0-9]{64}$/', $block_id)) {
            return '';
        }
        
        $min_height = self::getMinHeightForType($type);
        
        $safe_block_id = htmlspecialchars($block_id, ENT_QUOTES, 'UTF-8');
        
        return sprintf(
            '<div class="lazy-block" data-block-id="%s" style="min-height:%dpx">
                <div class="lazy-placeholder">
                    <div class="lazy-spinner"></div>
                </div>
            </div>',
            $safe_block_id,
            (int)$min_height
        );
    }
    
    private static function getMinHeightForType($type) {
        $heights = [
            'hero' => 500,
            'features' => 400,
            'testimonials' => 300,
            'pricing' => 400,
            'gallery' => 300,
            'team' => 350,
            'video' => 400,
            'default' => 200
        ];
        
        return $heights[$type] ?? $heights['default'];
    }
    
    public static function outputLazyScript($nonce = '') {
        if (self::$script_added || empty(self::$lazy_blocks)) {
            return '';
        }
        
        self::$script_added = true;
        
        $blocks_json = json_encode(self::$lazy_blocks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        
        $nonce_attr = $nonce ? ' nonce="' . htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') . '"' : '';
        
        return <<<HTML
<script{$nonce_attr}>
(function() {
    'use strict';
    
    let lazyBlocks;
    try {
        lazyBlocks = {$blocks_json};
    } catch (e) {
        console.error('Failed to parse lazy blocks:', e);
        return;
    }
    
    const validBlockIdPattern = /^block_[a-f0-9]{64}$/;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                const blockId = element.getAttribute('data-block-id');
                
                if (!blockId || !validBlockIdPattern.test(blockId)) {
                    console.warn('Invalid block ID detected');
                    observer.unobserve(element);
                    return;
                }
                
                if (lazyBlocks[blockId]) {
                    const temp = document.createElement('div');
                    temp.innerHTML = lazyBlocks[blockId];
                    
                    temp.querySelectorAll('script').forEach(s => s.remove());
                    
                    element.innerHTML = temp.innerHTML;
                    observer.unobserve(element);
                    delete lazyBlocks[blockId];
                    
                    element.dispatchEvent(new CustomEvent('blockLoaded', {
                        detail: { blockId: blockId }
                    }));
                }
            }
        });
    }, {
        rootMargin: '200px',
        threshold: 0.01
    });
    
    document.querySelectorAll('.lazy-block').forEach(block => {
        const blockId = block.getAttribute('data-block-id');
        
        if (blockId && validBlockIdPattern.test(blockId)) {
            observer.observe(block);
        }
    });
    
    if (!('IntersectionObserver' in window)) {
        document.querySelectorAll('.lazy-block').forEach(block => {
            const blockId = block.getAttribute('data-block-id');
            
            if (blockId && validBlockIdPattern.test(blockId) && lazyBlocks[blockId]) {
                const temp = document.createElement('div');
                temp.innerHTML = lazyBlocks[blockId];
                temp.querySelectorAll('script').forEach(s => s.remove());
                block.innerHTML = temp.innerHTML;
            }
        });
    }
})();
</script>
HTML;
    }
    
    public static function clear() {
        self::$lazy_blocks = [];
        self::$script_added = false;
    }
}

// ============================================================================
// ICON REGISTRY
// ============================================================================

final class IconRegistry {
    private static $used_icons = [];
    private static $used_social_icons = [];
    private static $custom_icons = [];
    private static $custom_social = [];
    private const MAX_TRACKED = 100;
    
    private const ALLOWED_ICONS = [
        'bolt', 'layout', 'clock', 'shield', 'chart', 'code',
        'star', 'heart', 'globe', 'users', 'settings', 'edit',
        'plus', 'trash', 'copy', 'eye', 'eyeOff', 'image', 'file',
        'upload', 'money'
    ];
    
    private const ALLOWED_SOCIAL = [
        'twitter', 'github', 'linkedin', 'facebook', 'instagram', 'youtube',
        'tiktok', 'pinterest', 'reddit', 'discord', 'snapchat', 'whatsapp',
        'telegram', 'twitch', 'spotify', 'medium', 'slack', 'dribbble',
        'mastodon', 'patreon', 'kofi', 'vimeo', 'tumblr', 'stackoverflow',
        'gitlab', 'bluesky', 'line', 'wechat', 'flatlypage', 'xing'
    ];
    
    public static function registerIcon($name, $svg) {
        if (count(self::$custom_icons) >= 50) {
            return false;
        }
        
        if (!preg_match('/^[a-z0-9_-]+$/', $name)) {
            return false;
        }
        
        if (!is_string($svg) || strlen($svg) > 5000) {
            return false;
        }
        
        self::$custom_icons[$name] = $svg;
        return true;
    }
    
    public static function registerSocialIcon($platform, $svg) {
        if (count(self::$custom_social) >= 20) {
            return false;
        }
        
        if (!preg_match('/^[a-z0-9_-]+$/', $platform)) {
            return false;
        }
        
        if (!is_string($svg) || strlen($svg) > 5000) {
            return false;
        }
        
        self::$custom_social[$platform] = $svg;
        return true;
    }
    
    public static function getCustomIcon($name) {
        return self::$custom_icons[$name] ?? null;
    }
    
    public static function getCustomSocial($platform) {
        return self::$custom_social[$platform] ?? null;
    }
    
    public static function markIconUsed($icon) {
        if (count(self::$used_icons) >= self::MAX_TRACKED) {
            return false;
        }
        
        if (!in_array($icon, self::ALLOWED_ICONS, true) && !isset(self::$custom_icons[$icon])) {
            return false;
        }
        
        self::$used_icons[$icon] = true;
        return true;
    }
    
    public static function markSocialUsed($platform) {
        if (count(self::$used_social_icons) >= self::MAX_TRACKED) {
            return false;
        }
        
        if (!in_array($platform, self::ALLOWED_SOCIAL, true) && !isset(self::$custom_social[$platform])) {
            return false;
        }
        
        self::$used_social_icons[$platform] = true;
        return true;
    }
    
    public static function isIconAllowed($icon) {
        return in_array($icon, self::ALLOWED_ICONS, true) || isset(self::$custom_icons[$icon]);
    }
    
    public static function isSocialAllowed($platform) {
        return in_array($platform, self::ALLOWED_SOCIAL, true) || isset(self::$custom_social[$platform]);
    }
}

if (!isset($GLOBALS['REGISTERED_BLOCKS'])) {
    $GLOBALS['REGISTERED_BLOCKS'] = [];
}

// ============================================================================
// ICON HELPERS
// ============================================================================

function get_icon_svg($icon) {
    $custom = IconRegistry::getCustomIcon($icon);
    if ($custom !== null) {
        return $custom;
    }
    
    if (!IconRegistry::isIconAllowed($icon)) {
        $icon = 'bolt';
    }
    
    $icons = [
        'bolt' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        'layout' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
        'clock' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'shield' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'chart' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'code' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
        'star' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'heart' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        'globe' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
        'users' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'settings' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'edit' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
        'plus' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
        'trash' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>',
        'copy' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
        'eye' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
        'eyeOff' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>',
        'image' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
        'file' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
        'upload' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>',
        'money' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
    ];
    
    return $icons[$icon];
}

function render_icon($icon) {
    $icon = preg_replace('/[^a-z_-]/', '', strtolower($icon));
    
    if (!IconRegistry::markIconUsed($icon)) {
        $icon = 'bolt';
    }
    
    return get_icon_svg($icon);
}

function get_social_icon_svg($platform) {
    $custom = IconRegistry::getCustomSocial($platform);
    if ($custom !== null) {
        return $custom;
    }
    
    if (!IconRegistry::isSocialAllowed($platform)) {
        $platform = 'twitter';
    }
    
    $icons = [
        'twitter' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        'github' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.17 6.839 9.49.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.463-1.11-1.463-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0112 6.836c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.167 22 16.418 22 12c0-5.523-4.477-10-10-10z"/></svg>',
        'linkedin' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
        'facebook' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'instagram' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>',
        'youtube' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
        'tiktok' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
        'pinterest' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.225 0-2.376-.637-2.771-1.388l-.753 2.87c-.271 1.048-1.007 2.361-1.497 3.161C9.142 23.812 10.544 24 12.017 24c6.627 0 11.999-5.373 11.999-11.987C24.016 5.367 18.644 0 12.017 0z"/></svg>',
        'reddit' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/></svg>',
        'discord' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>',
        'snapchat' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.06c-.012.18-.022.345-.03.51.075.045.203.09.401.09.3-.016.659-.12 1.033-.301.165-.088.344-.104.464-.104.182 0 .359.029.509.09.45.149.734.479.734.838.015.449-.39.839-1.213 1.168-.089.029-.209.075-.344.119-.45.135-1.139.36-1.333.81-.09.224-.061.524.12.868l.015.015c.06.136 1.526 3.475 4.791 4.014.255.044.435.27.42.509 0 .075-.015.149-.045.225-.24.569-1.273.988-3.146 1.271-.059.091-.12.375-.164.57-.029.179-.074.36-.134.553-.076.271-.27.405-.555.405h-.03c-.135 0-.313-.031-.538-.074-.36-.075-.765-.135-1.273-.135-.3 0-.599.015-.913.074-.6.104-1.123.464-1.723.884-.853.599-1.826 1.288-3.294 1.288-.06 0-.119-.015-.18-.015h-.149c-1.468 0-2.427-.675-3.279-1.288-.599-.42-1.107-.779-1.707-.884-.314-.045-.629-.074-.928-.074-.54 0-.958.089-1.272.149-.211.043-.391.074-.54.074-.374 0-.523-.224-.583-.42-.061-.192-.09-.389-.135-.567-.046-.181-.105-.494-.166-.57-1.918-.222-2.95-.642-3.189-1.226-.031-.063-.052-.15-.055-.225-.015-.243.165-.465.42-.509 3.264-.54 4.73-3.879 4.791-4.02l.016-.029c.18-.345.224-.645.119-.869-.195-.434-.884-.658-1.332-.809-.121-.029-.24-.074-.346-.119-1.107-.435-1.257-.93-1.197-1.273.09-.479.674-.793 1.168-.793.146 0 .27.029.383.074.42.194.789.3 1.104.3.234 0 .384-.06.465-.105l-.046-.569c-.098-1.626-.225-3.651.307-4.837C7.392 1.077 10.739.807 11.727.807l.419-.015h.06z"/></svg>',
        'whatsapp' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
        'telegram' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
        'twitch' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714Z"/></svg>',
        'spotify' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/></svg>',
        'medium' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M13.54 12a6.8 6.8 0 01-6.77 6.82A6.8 6.8 0 010 12a6.8 6.8 0 016.77-6.82A6.8 6.8 0 0113.54 12zM20.96 12c0 3.54-1.51 6.42-3.38 6.42-1.87 0-3.39-2.88-3.39-6.42s1.52-6.42 3.39-6.42 3.38 2.88 3.38 6.42M24 12c0 3.17-.53 5.75-1.19 5.75-.66 0-1.19-2.58-1.19-5.75s.53-5.75 1.19-5.75C23.47 6.25 24 8.83 24 12z"/></svg>',
        'slack' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/></svg>',
        'dribbble' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 24C5.385 24 0 18.615 0 12S5.385 0 12 0s12 5.385 12 12-5.385 12-12 12zm10.12-10.358c-.35-.11-3.17-.953-6.384-.438 1.34 3.684 1.887 6.684 1.992 7.308 2.3-1.555 3.936-4.02 4.395-6.87zm-6.115 7.808c-.153-.9-.75-4.032-2.19-7.77l-.066.02c-5.79 2.015-7.86 6.025-8.04 6.4 1.73 1.358 3.92 2.166 6.29 2.166 1.42 0 2.77-.29 4-.814zm-11.62-2.58c.232-.4 3.045-5.055 8.332-6.765.135-.045.27-.084.405-.12-.26-.585-.54-1.167-.832-1.74C7.17 11.775 2.206 11.71 1.756 11.7l-.004.312c0 2.633.998 5.037 2.634 6.855zm-2.42-8.955c.46.008 4.683.026 9.477-1.248-1.698-3.018-3.53-5.558-3.8-5.928-2.868 1.35-5.01 3.99-5.676 7.17zM9.6 2.052c.282.38 2.145 2.914 3.822 6 3.645-1.365 5.19-3.44 5.373-3.702-1.81-1.61-4.19-2.586-6.795-2.586-.825 0-1.63.1-2.4.285zm10.335 3.483c-.218.29-1.935 2.493-5.724 4.04.24.49.47.985.68 1.486.08.18.15.36.22.53 3.41-.43 6.8.26 7.14.33-.02-2.42-.88-4.64-2.31-6.38z"/></svg>',
        'mastodon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.268 5.313c-.35-2.578-2.617-4.61-5.304-5.004C17.51.242 15.792 0 11.813 0h-.03c-3.98 0-4.835.242-5.288.309C3.882.692 1.496 2.518.917 5.127.64 6.412.61 7.837.661 9.143c.074 1.874.088 3.745.26 5.611.118 1.24.325 2.47.62 3.68.55 2.237 2.777 4.098 4.96 4.857 2.336.792 4.849.923 7.256.38.265-.061.527-.132.786-.213.585-.184 1.27-.39 1.774-.753a.057.057 0 0 0 .023-.043v-1.809a.052.052 0 0 0-.02-.041.053.053 0 0 0-.046-.01 20.282 20.282 0 0 1-4.709.545c-2.73 0-3.463-1.284-3.674-1.818a5.593 5.593 0 0 1-.319-1.433.053.053 0 0 1 .066-.054c1.517.363 3.072.546 4.632.546.376 0 .75 0 1.125-.01 1.57-.044 3.224-.124 4.768-.422.038-.008.077-.015.11-.024 2.435-.464 4.753-1.92 4.989-5.604.008-.145.03-1.52.03-1.67.002-.512.167-3.63-.024-5.545zm-3.748 9.195h-2.561V8.29c0-1.309-.55-1.976-1.67-1.976-1.23 0-1.846.79-1.846 2.35v3.403h-2.546V8.663c0-1.56-.617-2.35-1.848-2.35-1.112 0-1.668.668-1.67 1.977v6.218H4.822V8.102c0-1.31.337-2.35 1.011-3.12.696-.77 1.608-1.164 2.74-1.164 1.311 0 2.302.5 2.962 1.498l.638 1.06.638-1.06c.66-.999 1.65-1.498 2.96-1.498 1.13 0 2.043.395 2.74 1.164.675.77 1.012 1.81 1.012 3.12z"/></svg>',
        'patreon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.386.524c-4.764 0-8.64 3.876-8.64 8.64 0 4.75 3.876 8.613 8.64 8.613 4.75 0 8.614-3.864 8.614-8.613C24 4.4 20.136.524 15.386.524M.003 23.537h4.22V.524H.003"/></svg>',
        'kofi' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.881 8.948c-.773-4.085-4.859-4.593-4.859-4.593H.723c-.604 0-.679.798-.679.798s-.082 7.324-.022 11.822c.164 2.424 2.586 2.672 2.586 2.672s8.267-.023 11.966-.049c2.438-.426 2.683-2.566 2.658-3.734 4.352.24 7.422-2.831 6.649-6.916zm-11.062 3.511c-1.246 1.453-4.011 3.976-4.011 3.976s-.121.119-.31.023c-.076-.057-.108-.09-.108-.09-.443-.441-3.368-3.049-4.034-3.954-.709-.965-1.041-2.7-.091-3.71.951-1.01 3.005-1.086 4.363.407 0 0 1.565-1.782 3.468-.963 1.904.82 1.832 3.011.723 4.311zm6.173.478c-.928.116-1.682.028-1.682.028V7.284h1.77s1.971.551 1.971 2.638c0 1.913-.985 2.667-2.059 3.015z"/></svg>',
        'vimeo' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.977 6.416c-.105 2.338-1.739 5.543-4.894 9.609-3.268 4.247-6.026 6.37-8.29 6.37-1.409 0-2.578-1.294-3.553-3.881L5.322 11.4C4.603 8.816 3.834 7.522 3.01 7.522c-.179 0-.806.378-1.881 1.132L0 7.197c1.185-1.044 2.351-2.084 3.501-3.128C5.08 2.701 6.266 1.984 7.055 1.91c1.867-.18 3.016 1.1 3.447 3.838.465 2.953.789 4.789.971 5.507.539 2.45 1.131 3.674 1.776 3.674.502 0 1.256-.796 2.265-2.385 1.004-1.589 1.54-2.797 1.612-3.628.144-1.371-.395-2.061-1.614-2.061-.574 0-1.167.121-1.777.391 1.186-3.868 3.434-5.757 6.762-5.637 2.473.06 3.628 1.664 3.493 4.797z"/></svg>',
        'tumblr' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14.563 24c-5.093 0-7.031-3.756-7.031-6.411V9.747H5.116V6.648c3.63-1.313 4.512-4.596 4.71-6.469C9.84.051 9.941 0 9.999 0h3.517v6.114h4.801v3.633h-4.82v7.47c.016 1.001.375 2.371 2.207 2.371h.09c.631-.02 1.486-.205 1.936-.419l1.156 3.425c-.436.636-2.4 1.374-4.156 1.404h-.178l.011.002z"/></svg>',
        'stackoverflow' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.725 0l-1.72 1.277 6.39 8.588 1.716-1.277L15.725 0zm-3.94 3.418l-1.369 1.644 8.225 6.85 1.369-1.644-8.225-6.85zm-3.15 4.465l-.905 1.94 9.702 4.517.904-1.94-9.701-4.517zm-1.85 4.86l-.44 2.093 10.473 2.201.44-2.092-10.473-2.203zM1.89 15.47V24h19.19v-8.53h-2.133v6.397H4.021v-6.396H1.89zm4.265 2.133v2.13h10.66v-2.13H6.154Z"/></svg>',
        'gitlab' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.955 13.587l-1.342-4.135-2.664-8.189a.455.455 0 0 0-.867 0L16.418 9.45H7.582L4.919 1.263C4.783.84 4.185.84 4.05 1.26L1.386 9.449.044 13.587a.924.924 0 0 0 .331 1.023L12 23.054l11.625-8.443a.92.92 0 0 0 .330-1.024"/></svg>',
        'bluesky' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 10.8c-1.087-2.114-4.046-6.053-6.798-7.995C2.566.944 1.561 1.266.902 1.565.139 1.908 0 3.08 0 3.768c0 .69.378 5.65.624 6.479.815 2.736 3.713 3.66 6.383 3.364.136-.02.275-.039.415-.056-.138.022-.276.04-.415.056-3.912.58-7.387 2.005-2.83 7.078 5.013 5.19 6.87-1.113 7.823-4.308.953 3.195 2.05 9.271 7.733 4.308 4.267-4.308 1.172-6.498-2.74-7.078a8.741 8.741 0 0 1-.415-.056c.14.017.279.036.415.056 2.67.297 5.568-.628 6.383-3.364.246-.828.624-5.79.624-6.478 0-.69-.139-1.861-.902-2.206-.659-.298-1.664-.62-4.3 1.24C16.046 4.748 13.087 8.687 12 10.8z"/></svg>',
        'line' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.105.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>',
        'wechat' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178A1.17 1.17 0 0 1 4.623 7.17c0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178 1.17 1.17 0 0 1-1.162-1.178c0-.651.52-1.18 1.162-1.18zm5.34 2.867c-1.797-.052-3.746.512-5.28 1.786-1.72 1.428-2.687 3.72-1.78 6.22.942 2.453 3.666 4.229 6.884 4.229.826 0 1.622-.12 2.361-.336a.722.722 0 0 1 .598.082l1.584.926a.272.272 0 0 0 .14.047c.134 0 .24-.111.24-.247 0-.06-.023-.12-.038-.177l-.327-1.233a.582.582 0 0 1-.023-.156.49.49 0 0 1 .201-.398C23.024 18.48 24 16.82 24 14.98c0-3.21-2.931-5.837-6.656-6.088V8.89c-.135-.01-.27-.027-.407-.027zm-2.53 3.274c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.969-.982zm4.844 0c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.969-.982z"/></svg>',
        'flatlypage' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M1.139,10.785c2.355,-2.34 2.35,-2.344 2.556,-2.547c0.078,-0.077 3.035,-0.02 4.087,-0.036c0.256,-0.004 1.694,-2.812 5.62,-5.095c5.564,-3.236 9.601,-2.389 10.558,-2.232c0.071,0.012 0.043,0.088 -0.059,1.504c-0.647,8.983 -7.685,12.863 -8.987,13.72c-0.197,0.13 0.229,2.523 -0.192,3.517c-0.419,0.99 -3.908,3.683 -4.235,3.723c-0.417,0.051 -0.01,-2.035 -0.712,-3.864c-0.935,-2.437 -3.147,-1.942 -2.359,-3.122c0.131,-0.196 4.425,-3.782 5.035,-4.322c0.579,-0.513 0.008,-1.327 -0.6,-1.113c-0.322,0.114 -5.417,4.593 -5.553,4.647c-0.553,0.22 -0.839,-0.788 -0.974,-1.096c-0.816,-1.857 -3.157,-1.629 -4.786,-1.905c-1.026,-0.174 -0.451,-0.695 0.6,-1.78l17.729,-5.714c-0.291,-0.219 -0.274,-0.252 -0.625,-0.354c-1.886,-0.551 -2.944,2.007 -1.401,2.989c1.707,1.086 3.541,-1.228 2.026,-2.635l-17.729,5.714Z"/><path d="M7.464,20.241c-1.992,2.204 -4.202,1.853 -6.596,1.997c-0.097,0.006 -0.039,-1.98 0.059,-2.738c0.054,-0.421 0.244,-2.487 1.925,-3.848c1.683,-1.362 2.31,0.71 2.182,0.75c-2.12,0.665 -2.297,2.159 -2.629,3.897c-0.045,0.235 -0.08,0.321 0.157,0.341c0.161,0.014 1.862,-0.167 2.838,-0.777c1.305,-0.815 1.275,-1.923 1.418,-1.897c0.095,0.017 0.695,0.126 1.026,0.644c0.456,0.714 -0.364,1.61 -0.38,1.631Z"/></svg>',
        'xing' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.188 0c-.517 0-.741.325-.927.66 0 0-7.455 13.224-7.702 13.657.015.024 4.919 9.023 4.919 9.023.17.308.436.66.967.66h3.454c.211 0 .375-.078.463-.22.089-.151.089-.346-.009-.536l-4.879-8.916c-.004-.006-.004-.016 0-.022L22.139.756c.095-.191.097-.387.006-.535C22.056.078 21.894 0 21.686 0h-3.498zM3.648 4.74c-.211 0-.385.074-.473.216-.09.149-.078.339.02.531l2.34 4.05c.004.01.004.016 0 .021L1.86 16.051c-.099.188-.093.381 0 .529.085.142.239.234.45.234h3.461c.518 0 .766-.348.945-.667l3.734-6.609-2.378-4.155c-.172-.315-.434-.659-.962-.659H3.648v.016z"/></svg>',
        ];
    
    return $icons[$platform];
}

function render_social_icon($platform) {
    $platform = preg_replace('/[^a-z_-]/', '', strtolower($platform));
    
    if (!IconRegistry::markSocialUsed($platform)) {
        $platform = 'twitter';
    }
    
    return get_social_icon_svg($platform);
}

function render_block($block, $options = []) {
    global $REGISTERED_BLOCKS;

    if (!is_array($block)) {
        return '';
    }

    $type = $block['type'] ?? '';
    $data = $block['data'] ?? [];
    
    $cache_key = ['type' => $type, 'data' => $data, 'id' => $block['id'] ?? ''];
    
    $cached = RenderCache::get($cache_key);
    if ($cached !== null) {
        return $cached;
    }
    
    $id = '';
    if (!empty($block['id'])) {
        $clean_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $block['id']);
        $id = ' id="' . e($clean_id) . '"';
    }

    if (isset($REGISTERED_BLOCKS[$type]) && is_callable($REGISTERED_BLOCKS[$type]['render'])) {
        $html = call_user_func(
            $REGISTERED_BLOCKS[$type]['render'],
            $data,
            $id
        );
        
        RenderCache::set($cache_key, $html);
        return $html;
    }

    $allowed_blocks = [
        'hero', 'stats', 'features', 'testimonials', 'pricing', 'cta',
        'text', 'image', 'image-text', 'product-cards', 'video',
        'gallery', 'faq', 'team', 'audio', 'countdown', 'newsletter', 'html'
    ];
    
    if (!in_array($type, $allowed_blocks, true)) {
        return '';
    }

    $lazy_types = ['gallery', 'team', 'testimonials', 'video', 'audio'];
    $should_lazy_load = isset($options['enable_lazy']) && $options['enable_lazy'] 
                        && in_array($type, $lazy_types, true);
    
    $html = '';
    
    switch ($type) {
        case 'hero': $html = render_hero($data, $id); break;
        case 'stats': $html = render_stats($data, $id); break;
        case 'features': $html = render_features($data, $id); break;
        case 'testimonials': $html = render_testimonials($data, $id); break;
        case 'pricing': $html = render_pricing($data, $id); break;
        case 'cta': $html = render_cta($data, $id); break;
        case 'text': $html = render_text($data, $id); break;
        case 'image': $html = render_image($data, $id); break;
        case 'image-text': $html = render_image_text($data, $id); break;
        case 'product-cards': $html = render_product_cards($data, $id); break;
        case 'video': $html = render_video($data, $id); break;
        case 'gallery': $html = render_gallery($data, $id); break;
        case 'faq': $html = render_faq($data, $id); break;
        case 'team': $html = render_team($data, $id); break;
        case 'audio': $html = render_audio($data, $id); break;
        case 'countdown': $html = render_countdown($data, $id); break;
        case 'newsletter': $html = render_newsletter($data, $id); break;
        case 'html': $html = $data['html'] ?? ''; break;
        default: return '';
    }
    
    if (!empty($html)) {
        RenderCache::set($cache_key, $html);
    }
    
    if ($should_lazy_load && !empty($html)) {
        $block_id = LazyLoadManager::registerLazyBlock($block, $html);
        if ($block_id) {
            return LazyLoadManager::getLazyPlaceholder($block_id, $type);
        }
    }
    
    return $html;
}

/**
 * Hero block
 */
function render_hero($d, $id = '') {
    if (!is_array($d)) return '';
    ob_start(); ?>
    <section class="hero"<?= $id ?>>
        <div class="hero-grid"></div>
        <div class="container hero-content">
            <?php if (!empty($d['badge'])): ?><div class="badge"><span class="badge-dot"></span><span><?= e($d['badge']) ?></span></div><?php endif; ?>
            <h1><?= e($d['title'] ?? '') ?></h1>
            <p><?= e($d['subtitle'] ?? '') ?></p>
            <div class="hero-buttons">
                <?php if (!empty($d['button_primary'])): ?><a href="<?= e($d['button_primary_url'] ?? '#') ?>" class="btn btn-primary"><?= e($d['button_primary']) ?></a><?php endif; ?>
                <?php if (!empty($d['button_secondary'])): ?><a href="<?= e($d['button_secondary_url'] ?? '#') ?>" class="btn btn-outline"><?= e($d['button_secondary']) ?></a><?php endif; ?>
            </div>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * Stats block
 */
function render_stats($d, $id = '') {
    if (!is_array($d)) return '';
    $items = $d['items'] ?? [];
    if (empty($items) || !is_array($items)) return '';
    ob_start(); ?>
    <section class="stats"<?= $id ?>>
        <div class="container">
            <div class="stats-grid">
                <?php foreach ($items as $item): if (!is_array($item)) continue; ?>
                <div class="stat-item"><h3><?= e($item['value'] ?? '') ?></h3><p><?= e($item['label'] ?? '') ?></p></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * Features block
 */
function render_features($d, $id = '') {
    if (!is_array($d)) return '';
    $items = $d['items'] ?? [];
    ob_start(); ?>
    <section class="features"<?= $id ?>>
        <div class="container">
            <?php if (!empty($d['title'])): ?>
            <div class="section-header">
                <h2><?= e($d['title']) ?></h2>
                <?php if (!empty($d['subtitle'])): ?><p><?= e($d['subtitle']) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($items) && is_array($items)): ?>
            <div class="features-grid">
                <?php foreach ($items as $item): if (!is_array($item)) continue; ?>
                <div class="feature-card">
                    <div class="feature-icon"><?= render_icon($item['icon'] ?? 'bolt') ?></div>
                    <h3><?= e($item['title'] ?? '') ?></h3>
                    <p><?= e($item['description'] ?? '') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * Testimonials block
 */
function render_testimonials($d, $id = '') {
    if (!is_array($d)) return '';
    $items = $d['items'] ?? [];
    ob_start(); ?>
    <section class="testimonials"<?= $id ?>>
        <div class="container">
            <?php if (!empty($d['title'])): ?>
            <div class="section-header">
                <h2><?= e($d['title']) ?></h2>
                <?php if (!empty($d['subtitle'])): ?><p><?= e($d['subtitle']) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($items) && is_array($items)): ?>
            <div class="testimonials-grid">
                <?php foreach ($items as $item): if (!is_array($item)) continue; ?>
                <div class="testimonial-card">
                    <p>"<?= e($item['quote'] ?? '') ?>"</p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar"><?= e($item['initials'] ?? '') ?></div>
                        <div class="testimonial-info"><h4><?= e($item['name'] ?? '') ?></h4><span><?= e($item['role'] ?? '') ?></span></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * Pricing block
 */
function render_pricing($d, $id = '') {
    if (!is_array($d)) return '';
    $items = $d['items'] ?? [];
    ob_start(); ?>
    <section class="pricing"<?= $id ?>>
        <div class="container">
            <?php if (!empty($d['title'])): ?>
            <div class="section-header">
                <h2><?= e($d['title']) ?></h2>
                <?php if (!empty($d['subtitle'])): ?><p><?= e($d['subtitle']) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($items) && is_array($items)): ?>
            <div class="pricing-grid">
                <?php foreach ($items as $item): if (!is_array($item)) continue; ?>
                <div class="pricing-card <?= !empty($item['featured']) ? 'featured' : '' ?>">
                    <?php if (!empty($item['featured'])): ?><span class="pricing-popular">Most Popular</span><?php endif; ?>
                    <h3><?= e($item['name'] ?? '') ?></h3>
                    <div class="price"><?= e($item['price'] ?? '') ?><span><?= e($item['period'] ?? '') ?></span></div>
                    <p class="description"><?= e($item['description'] ?? '') ?></p>
                    <?php if (!empty($item['features']) && is_array($item['features'])): ?>
                    <ul class="pricing-features">
                        <?php foreach ($item['features'] as $feature): ?>
                        <li><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><?= e($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <a href="<?= e($item['button_url'] ?? '#') ?>" class="btn <?= !empty($item['featured']) ? 'btn-primary' : 'btn-outline' ?>"><?= e($item['button_text'] ?? $item['button'] ?? 'Get Started') ?></a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * CTA block
 */
function render_cta($d, $id = '') {
    if (!is_array($d)) return '';
    ob_start(); ?>
    <section class="cta"<?= $id ?>>
        <div class="container">
            <div class="cta-inner">
                <h2><?= e($d['title'] ?? '') ?></h2>
                <p><?= e($d['subtitle'] ?? '') ?></p>
                <div class="cta-buttons">
                    <?php if (!empty($d['button_primary'])): ?><a href="<?= e($d['button_primary_url'] ?? '#') ?>" class="btn btn-primary"><?= e($d['button_primary']) ?></a><?php endif; ?>
                    <?php if (!empty($d['button_secondary'])): ?><a href="<?= e($d['button_secondary_url'] ?? '#') ?>" class="btn btn-outline"><?= e($d['button_secondary']) ?></a><?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * Text block
 */
function render_text($d, $id = '') {
    if (!is_array($d)) return '';
    ob_start(); ?>
    <section class="text-section"<?= $id ?>>
        <div class="container">
            <?php if (!empty($d['title'])): ?><h2><?= e($d['title']) ?></h2><?php endif; ?>
            <div class="text-content"><?= nl2br(e($d['content'] ?? '')) ?></div>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * Image block
 */
function render_image($d, $id = '') {
    if (!is_array($d) || empty($d['url'])) return '';
    ob_start(); ?>
    <section class="image-section"<?= $id ?>>
        <div class="container">
            <figure>
                <img src="<?= e($d['url']) ?>" alt="<?= e($d['alt'] ?? '') ?>" loading="lazy">
                <?php if (!empty($d['caption'])): ?><figcaption><?= e($d['caption']) ?></figcaption><?php endif; ?>
            </figure>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * Image + Text block
 */
function render_image_text($d, $id = '') {
    if (!is_array($d)) return '';
    $allowed_positions = ['left', 'right'];
    $image_pos = in_array($d['image_position'] ?? '', $allowed_positions, true) 
        ? $d['image_position'] 
        : 'left';
    
    ob_start(); ?>
    <section class="image-text"<?= $id ?>>
        <div class="container">
            <div class="image-text-grid image-text-<?= e($image_pos) ?>">
                <div class="image-text-image">
                    <img src="<?= e($d['image_url'] ?? '') ?>" alt="<?= e($d['image_alt'] ?? '') ?>" loading="lazy">
                </div>
                <div class="image-text-content">
                    <?php if (!empty($d['subtitle'])): ?><p class="section-subtitle"><?= e($d['subtitle']) ?></p><?php endif; ?>
                    <?php if (!empty($d['title'])): ?><h2><?= e($d['title']) ?></h2><?php endif; ?>
                    <div class="text-content"><?= nl2br(e($d['content'] ?? '')) ?></div>
                    <?php if (!empty($d['button_text'])): ?>
                    <a href="<?= e($d['button_url'] ?? '#') ?>" class="btn btn-primary"><?= e($d['button_text']) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * Product Cards block
 */
function render_product_cards($d, $id = '') {
    if (!is_array($d)) return '';
    $products = $d['products'] ?? [];
    if (empty($products) || !is_array($products)) return '';
    ob_start(); ?>
    <section class="product-cards"<?= $id ?>>
        <div class="container">
            <?php if (!empty($d['title'])): ?>
            <div class="section-header">
                <h2><?= e($d['title']) ?></h2>
                <?php if (!empty($d['subtitle'])): ?><p><?= e($d['subtitle']) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="products-grid">
                <?php foreach ($products as $product): if (!is_array($product)) continue; ?>
                <div class="product-card">
                    <?php if (!empty($product['image'])): ?>
                    <div class="product-image">
                        <img src="<?= e($product['image']) ?>" alt="<?= e($product['title'] ?? '') ?>" width="48" height="48" loading="lazy">
                    </div>
                    <?php endif; ?>
                    <h3><?= e($product['title'] ?? '') ?></h3>
                    <p class="product-description"><?= e($product['description'] ?? '') ?></p>
                    <?php if (!empty($product['features']) && is_array($product['features'])): ?>
                    <ul class="product-features">
                        <?php foreach ($product['features'] as $feature): ?>
                        <li><?= e($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <?php if (!empty($product['button_text'])): ?>
                    <a href="<?= e($product['button_url'] ?? '#') ?>" class="btn btn-primary btn-sm"><?= e($product['button_text']) ?></a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * Video block
 */
function render_video($d, $id = '') {
    if (!is_array($d) || empty($d['url'])) return '';
    
    $url = $d['url'];
    $type = $d['type'] ?? 'url';
    $poster = !empty($d['posterUrl']) ? $d['posterUrl'] : '';
    $playIcon = !empty($d['playIcon']) ? $d['playIcon'] : '/assets/images/play.webp';
    $pauseIcon = !empty($d['pauseIcon']) ? $d['pauseIcon'] : '/assets/images/pause.webp';
    $reloadIcon = !empty($d['reloadIcon']) ? $d['reloadIcon'] : '/assets/images/reload.webp';
    
    $id_attr = '';
    if (!empty($id)) {
        $safe_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
        $id_attr = ' id="' . e($safe_id) . '"';
    }
    
    $allowed_types = ['url', 'youtube', 'facebook'];
    if (!in_array($type, $allowed_types, true)) {
        $type = 'url';
    }
    
    if ($type === 'url') {
        if (preg_match('/youtube\.com|youtu\.be/i', $url)) {
            $type = 'youtube';
        } elseif (preg_match('/facebook\.com|fb\.watch/i', $url)) {
            $type = 'facebook';
        }
    }
    
    $video_unique_id = 'video-' . uniqid();
    
    ob_start(); ?>
    <section class="video-section"<?= $id_attr ?>>
        <div class="container">
            <?php if (!empty($d['title'])): ?>
            <div class="section-header">
                <h2><?= e($d['title']) ?></h2>
                <?php if (!empty($d['subtitle'])): ?><p><?= e($d['subtitle']) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="video-wrapper" style="position: relative;">
                <?php 
                if ($poster && ($type === 'youtube' || $type === 'facebook')): ?>
                    <div class="video-overlay" style="background-image: url('<?= e($poster) ?>'); position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center; z-index: 10; cursor: pointer; display: flex; align-items: center; justify-content: center;" onclick="this.style.display='none';" role="button" aria-label="Odtwórz wideo" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' '){this.style.display='none';}">
                        <picture>
                            <source srcset="<?= e($playIcon) ?>" type="image/webp">
                            <img src="<?= e(str_replace('.webp', '.png', $playIcon)) ?>" 
                                 alt="Play" 
                                 style="width: 80px; 
                                        height: 80px;
                                        transition: transform 0.2s;
                                        filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));"
                                 onmouseover="this.style.transform='scale(1.1)'"
                                 onmouseout="this.style.transform='scale(1)'"
                                 loading="lazy">
                        </picture>
                    </div>
                <?php endif; ?>
                
                <?php if ($type === 'youtube'): 
                    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $matches);
                    $video_id = $matches[1] ?? '';
                    if ($video_id && preg_match('/^[a-zA-Z0-9_-]{11}$/', $video_id)):
                        $yt_src = "https://www.youtube.com/embed/" . e($video_id);
                        $yt_src .= $poster ? "?autoplay=1&rel=0" : "?rel=0";
                ?>
                    <iframe src="<?= $yt_src ?>" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen 
                            loading="lazy"
                            title="<?= !empty($d['title']) ? e($d['title']) : 'YouTube video' ?>"></iframe>
                <?php endif; 
                elseif ($type === 'facebook'): 
                    $fb_src = "https://www.facebook.com/plugins/video.php?href=" . urlencode($url) . "&show_text=false";
                    $fb_src .= $poster ? "&autoplay=true" : "";
                ?>
                    <iframe src="<?= e($fb_src) ?>" 
                            frameborder="0" 
                            allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" 
                            allowfullscreen 
                            loading="lazy"
                            title="<?= !empty($d['title']) ? e($d['title']) : 'Facebook video' ?>"></iframe>
                <?php else: ?>
                    <video id="<?= $video_unique_id ?>"
                        preload="metadata"
                        <?= $poster ? 'poster="'.e($poster).'"' : '' ?>
                        controlsList="nodownload">
                        <source src="<?= e($url) ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    
                    <button class="video-play-pause-btn" id="play-pause-<?= $video_unique_id ?>" style=" position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 15%; min-width: 90px; max-width: 150px; aspect-ratio: 1/1; background: none; border: none; cursor: pointer; z-index: 5; opacity: 1; transition: opacity 0.3s; pointer-events: auto; " aria-label="Play/Pause">
                        <picture>
                            <source srcset="<?= e($playIcon) ?>" type="image/webp">
                            <img class="play-icon" src="<?= e(str_replace('.webp', '.png', $playIcon)) ?>" alt="Play" style="width: 100%; height: 100%; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));">
                        </picture>
                        <picture style="display: none;">
                            <source srcset="<?= e($pauseIcon) ?>" type="image/webp">
                            <img class="pause-icon" src="<?= e(str_replace('.webp', '.png', $pauseIcon)) ?>" alt="Pause" style="width: 100%; height: 100%; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));">
                        </picture>

                    <picture class="reload-pic" style="display: none;">
                        <?php $reloadIcon = str_replace(basename($playIcon), 'reload.webp', $playIcon); ?>
                        <source srcset="<?= e($reloadIcon) ?>" type="image/webp">
                        <img class="reload-icon" src="<?= e(str_replace('.webp', '.png', $reloadIcon)) ?>" alt="Pause" style="width: 100%; height: 100%; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));">
                    </picture>
                    </button>
                    <script>
                    (function () {
                        const video = document.getElementById('<?= $video_unique_id ?>');
                        const btn = document.getElementById('play-pause-<?= $video_unique_id ?>');
                        const playPicture = btn.querySelector('picture:nth-child(1)');
                        const pausePicture = btn.querySelector('picture:nth-child(2)');
                        const reloadPicture = btn.querySelector('picture.reload-pic'); // Pobranie nowej ikony
                        const wrapper = btn.parentElement;
                        let hideTimeout;

                        function showButton() {
                            btn.style.opacity = '1';
                            clearTimeout(hideTimeout);
                            hideTimeout = setTimeout(() => {
                                if (!video.paused && !video.ended) {
                                    btn.style.opacity = '0';
                                }
                            }, 2000);
                        }

                        function updateButton() {
                            playPicture.style.display = 'none';
                            pausePicture.style.display = 'none';
                            reloadPicture.style.display = 'none';

                            if (video.ended) {
                                reloadPicture.style.display = '';
                                btn.style.opacity = '1';
                            } else if (video.paused) {
                                playPicture.style.display = '';
                                btn.style.opacity = '1';
                            } else {
                                pausePicture.style.display = '';
                                showButton();
                            }
                        }

                        btn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();

                            if (video.ended) {
                                video.currentTime = 0;
                                video.play();
                            } else if (video.paused) {
                                video.play();
                            } else {
                                video.pause();
                            }
                        });

                        video.addEventListener('play', updateButton);
                        video.addEventListener('pause', updateButton);
                        video.addEventListener('ended', updateButton);
                        
                        wrapper.addEventListener('mousemove', showButton);
                        wrapper.addEventListener('mouseenter', showButton);
                        
                        updateButton();
                    })();
                    </script>
                <?php endif; ?>
            </div>
            <?php if (!empty($d['caption'])): ?>
                <p class="video-caption"><?= e($d['caption']) ?></p>
            <?php endif; ?>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * Gallery block
 */
function render_gallery($d, $id = '') {
    if (!is_array($d)) return '';
    $images = $d['images'] ?? [];
    if (empty($images) || !is_array($images)) return '';
    ob_start(); ?>
    <section class="gallery-section"<?= $id ?>>
        <div class="container">
            <?php if (!empty($d['title'])): ?>
            <div class="section-header">
                <h2><?= e($d['title']) ?></h2>
                <?php if (!empty($d['subtitle'])): ?><p><?= e($d['subtitle']) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="gallery-grid">
                <?php foreach ($images as $image): if (!is_array($image)) continue; ?>
                <div class="gallery-item">
                    <img src="<?= e($image['url'] ?? '') ?>" alt="<?= e($image['alt'] ?? '') ?>" loading="lazy">
                    <?php if (!empty($image['caption'])): ?>
                        <div class="gallery-caption"><?= e($image['caption']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * FAQ block
 */
function render_faq($d, $id = '') {
    if (!is_array($d)) return '';
    $items = $d['items'] ?? [];
    if (empty($items) || !is_array($items)) return '';
    ob_start(); ?>
    <section class="faq-section"<?= $id ?>>
        <div class="container">
            <?php if (!empty($d['title'])): ?>
            <div class="section-header">
                <h2><?= e($d['title']) ?></h2>
                <?php if (!empty($d['subtitle'])): ?><p><?= e($d['subtitle']) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="faq-list">
                <?php foreach ($items as $item): if (!is_array($item)) continue; ?>
                <details class="faq-item">
                    <summary class="faq-question"><?= e($item['question'] ?? '') ?></summary>
                    <div class="faq-answer"><?= nl2br(e($item['answer'] ?? '')) ?></div>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * Team block
 */
function render_team($d, $id = '') {
    if (!is_array($d)) return '';
    $members = $d['members'] ?? [];
    if (empty($members) || !is_array($members)) return '';
    ob_start(); ?>
    <section class="team-section"<?= $id ?>>
        <div class="container">
            <?php if (!empty($d['title'])): ?>
            <div class="section-header">
                <h2><?= e($d['title']) ?></h2>
                <?php if (!empty($d['subtitle'])): ?><p><?= e($d['subtitle']) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="team-grid">
                <?php foreach ($members as $member): if (!is_array($member)) continue; ?>
                <div class="team-member">
                    <?php if (!empty($member['image'])): ?>
                        <img src="<?= e($member['image']) ?>" alt="<?= e($member['name'] ?? '') ?>" class="team-photo" loading="lazy">
                    <?php else: ?>
                        <div class="team-avatar"><?= e($member['initials'] ?? substr($member['name'] ?? '?', 0, 2)) ?></div>
                    <?php endif; ?>
                    <h3><?= e($member['name'] ?? '') ?></h3>
                    <p class="team-role"><?= e($member['role'] ?? '') ?></p>
                    <?php if (!empty($member['bio'])): ?>
                        <p class="team-bio"><?= e($member['bio']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($member['social']) && is_array($member['social'])): ?>
                    <div class="team-social">
                        <?php foreach ($member['social'] as $social): if (!is_array($social)) continue; ?>
                        <a href="<?= e($social['url'] ?? '#') ?>" target="_blank" rel="noopener" class="team-social-link">
                            <?= render_social_icon($social['platform'] ?? 'twitter') ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * Audio block
 */
function render_audio($d, $id = '') {
    if (!is_array($d) || empty($d['url'])) return '';
    ob_start(); ?>
    <section class="audio-section"<?= $id ?>>
        <div class="container">
            <?php if (!empty($d['title'])): ?>
            <div class="section-header">
                <h2><?= e($d['title']) ?></h2>
                <?php if (!empty($d['subtitle'])): ?><p><?= e($d['subtitle']) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="audio-wrapper">
                <audio controls preload="metadata" class="audio-player">
                    <source src="<?= e($d['url']) ?>" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
                <?php if (!empty($d['music_link'])): ?>
                <a href="<?= e($d['music_link']) ?>" class="btn btn-outline" target="_blank" rel="noopener"><?= e($d['music_link_text'] ?? 'Download') ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php return ob_get_clean();
}

/**
 * Countdown block
 */
function render_countdown($d, $id = '') {
    if (!is_array($d) || empty($d['target_date'])) return '';
    
    $target_date = filter_var($d['target_date'], FILTER_SANITIZE_STRING);
    $target_time = filter_var($d['target_time'] ?? '00:00', FILTER_SANITIZE_STRING);
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $target_date)) {
        return '';
    }
    
    $target_datetime = $target_date . ' ' . $target_time;
    $timer_id = 'timer_' . hash('sha256', $target_datetime . random_bytes(8));
    
    ob_start(); ?>
    <section class="countdown-section"<?= $id ?>>
        <div class="container">
            <?php if (!empty($d['title'])): ?>
            <div class="section-header">
                <h2><?= e($d['title']) ?></h2>
                <?php if (!empty($d['subtitle'])): ?><p><?= e($d['subtitle']) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="countdown-timer" id="<?= e($timer_id) ?>" data-target="<?= e($target_datetime) ?>">
                <div class="countdown-item">
                    <div class="countdown-value" data-days>00</div>
                    <div class="countdown-label">Days</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-value" data-hours>00</div>
                    <div class="countdown-label">Hours</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-value" data-minutes>00</div>
                    <div class="countdown-label">Minutes</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-value" data-seconds>00</div>
                    <div class="countdown-label">Seconds</div>
                </div>
            </div>
        </div>
    </section>
    <script>
    (function() {
        'use strict';
        const timer = document.getElementById(<?= json_encode($timer_id) ?>);
        if (!timer) return;
        
        const targetDateStr = <?= json_encode($target_datetime) ?>;
        const targetDate = new Date(targetDateStr.replace(' ', 'T') + 'Z').getTime();
        if (isNaN(targetDate)) return;
        
        function updateCountdown() {
            const now = Date.now();
            const distance = targetDate - now;
            
            if (distance < 0) {
                timer.querySelector('[data-days]').textContent = '00';
                timer.querySelector('[data-hours]').textContent = '00';
                timer.querySelector('[data-minutes]').textContent = '00';
                timer.querySelector('[data-seconds]').textContent = '00';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            timer.querySelector('[data-days]').textContent = String(days).padStart(2, '0');
            timer.querySelector('[data-hours]').textContent = String(hours).padStart(2, '0');
            timer.querySelector('[data-minutes]').textContent = String(minutes).padStart(2, '0');
            timer.querySelector('[data-seconds]').textContent = String(seconds).padStart(2, '0');
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    })();
    </script>
    <?php return ob_get_clean();
}

/**
 * Newsletter block
 */
function render_newsletter($d, $id = '') {
    if (!is_array($d)) return '';
    
    $section_id = '';
    if (!empty($id) && preg_match('/id="([^"]+)"/', $id, $m)) {
        $section_id = $m[1];
    }

    ob_start(); ?>
    <section class="newsletter-section"<?= $id ?>>
        <?php if ($section_id !== 'newsletter'): ?>
            <div id="newsletter" style="height:1px; margin-top:-1px;"></div>
        <?php endif; ?>
        <div class="container">
            <div class="newsletter-inner">
                <?php if (!empty($d['title'])): ?>
                    <h2><?= e($d['title']) ?></h2>
                <?php endif; ?>
                <?php if (!empty($d['subtitle'])): ?>
                    <p><?= e($d['subtitle']) ?></p>
                <?php endif; ?>
                <form class="newsletter-form" method="POST" action="/newsletter/subscribe">
                    <input
                        type="email"
                        name="email"
                        class="newsletter-input"
                        placeholder="<?= e($d['placeholder'] ?? 'Enter your email') ?>"
                        required
                    >
                    <button type="submit" class="btn btn-primary">
                        <?= e($d['button_text'] ?? 'Subscribe') ?>
                    </button>
                </form>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}