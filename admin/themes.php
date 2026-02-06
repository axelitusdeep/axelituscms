<?php
require_once __DIR__ . '/../config.php';

require_login();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($token)) {
        $message = 'Invalid request. Please try again.';
        $message_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'activate':
                $theme_file = $_POST['theme_file'] ?? '';
                if ($theme_file && file_exists(__DIR__ . '/../themes/' . $theme_file)) {
                    $source = __DIR__ . '/../themes/' . $theme_file;
                    $target = __DIR__ . '/../css/theme.css';
                    
                    if (copy($source, $target)) {
                        $message = 'Theme activated successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to activate theme.';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Theme file not found.';
                    $message_type = 'error';
                }
                break;
                
            case 'deactivate':
                $target = __DIR__ . '/../css/theme.css';
                
                if (file_put_contents($target, "/* No theme active */\n") !== false) {
                    $message = 'Theme deactivated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to deactivate theme.';
                    $message_type = 'error';
                }
                break;
                
            case 'delete':
                $theme_file = $_POST['theme_file'] ?? '';
                
                if (!$theme_file || !file_exists(__DIR__ . '/../themes/' . $theme_file)) {
                    $message = 'Theme file not found.';
                    $message_type = 'error';
                } else {
                    $current_theme = getCurrentThemeFile();
                    
                    if ($theme_file === $current_theme) {
                        $target = __DIR__ . '/../css/theme.css';
                        file_put_contents($target, "/* No theme active */\n");
                    }
                    
                    if (unlink(__DIR__ . '/../themes/' . $theme_file)) {
                        $message = 'Theme deleted successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to delete theme.';
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'upload':
                if (isset($_FILES['theme_file']) && $_FILES['theme_file']['error'] === 0) {
                    $file = $_FILES['theme_file'];
                    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    if ($file_ext !== 'css') {
                        $message = 'Please upload a CSS file.';
                        $message_type = 'error';
                    } else {
                        $safe_filename = preg_replace('/[^a-z0-9-_]/i', '', pathinfo($file['name'], PATHINFO_FILENAME));
                        $target_file = __DIR__ . '/../themes/' . $safe_filename . '.css';
                        
                        if (file_exists($target_file)) {
                            $message = 'Theme with this name already exists.';
                            $message_type = 'error';
                        } elseif (move_uploaded_file($file['tmp_name'], $target_file)) {
                            $message = 'Theme uploaded successfully!';
                            $message_type = 'success';
                        } else {
                            $message = 'Failed to upload theme.';
                            $message_type = 'error';
                        }
                    }
                } else {
                    $message = 'Please select a valid CSS file.';
                    $message_type = 'error';
                }
                break;
        }
    }
}

function getCurrentThemeFile() {
    $current_theme_path = __DIR__ . '/../css/theme.css';
    
    if (!file_exists($current_theme_path)) {
        return null;
    }
    
    $current_content = file_get_contents($current_theme_path);
    
    if (trim($current_content) === '/* No theme active */' || empty(trim($current_content))) {
        return null;
    }
    
    $themes_dir = __DIR__ . '/../themes/';
    
    if (!is_dir($themes_dir)) {
        return null;
    }
    
    $theme_files = glob($themes_dir . '*.css');
    
    foreach ($theme_files as $theme_file) {
        $theme_content = file_get_contents($theme_file);
        if ($theme_content === $current_content) {
            return basename($theme_file);
        }
    }
    
    return null;
}

function parseThemeMetadata($file_path) {
    $content = file_get_contents($file_path);
    $filename = basename($file_path, '.css');
    
    $metadata = [
        'name' => $filename,
        'author' => 'Unknown',
        'file' => basename($file_path)
    ];
    
    if (preg_match('/Theme:\s*([^\r\n]+)/i', $content, $m)) $metadata['name'] = trim($m[1]);
    if (preg_match('/Author:\s*([^\r\n]+)/i', $content, $m)) $metadata['author'] = trim($m[1]);
    
    $metadata['colors'] = extractThemeColors($content);
    
    return $metadata;
}

function extractThemeColors($css_content) {
    $colors = [];

    if (preg_match('/Colors:\s*([^\r\n]*)/i', $css_content, $matches)) {
        $color_line = $matches[1];
        if (preg_match_all('/#(?:[0-9a-fA-F]{3}){1,2}/', $color_line, $color_matches)) {
            $colors = array_values(array_map('strtolower', $color_matches[0]));
        }
    }
    
    if (empty($colors)) {
        $color_patterns = ['/--primary:\s*([^;]+);/i', '/--accent:\s*([^;]+);/i', '/--secondary:\s*([^;]+);/i'];
        foreach ($color_patterns as $pattern) {
            if (preg_match($pattern, $css_content, $match)) {
                $normalized = normalizeColor($match[1]);
                if ($normalized) $colors[] = $normalized;
            }
        }
    }

    $colors = array_values(array_filter($colors, fn($c) => $c !== null));

    while (count($colors) < 3) {
        $colors[] = 'No data';
    }
    
    return array_slice($colors, 0, 3);
}

function normalizeColor($color) {
    $color = trim($color);

    if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color)) {
        return strtolower($color);
    }

    if (preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*[\d.]+)?\)/i', $color, $matches)) {
        $r = min(255, max(0, intval($matches[1])));
        $g = min(255, max(0, intval($matches[2])));
        $b = min(255, max(0, intval($matches[3])));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    return null;
}

function isGrayscaleColor($hex) {
    $hex = ltrim($hex, '#');
    
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $max_diff = max(abs($r - $g), abs($g - $b), abs($r - $b));
    
    return $max_diff < 15;
}

function isSimilarToExisting($new_color, $existing_colors) {
    $new_hex = ltrim($new_color, '#');
    if (strlen($new_hex) === 3) {
        $new_hex = $new_hex[0] . $new_hex[0] . $new_hex[1] . $new_hex[1] . $new_hex[2] . $new_hex[2];
    }
    
    $new_r = hexdec(substr($new_hex, 0, 2));
    $new_g = hexdec(substr($new_hex, 2, 2));
    $new_b = hexdec(substr($new_hex, 4, 2));
    
    foreach ($existing_colors as $existing) {
        $existing_hex = ltrim($existing, '#');
        if (strlen($existing_hex) === 3) {
            $existing_hex = $existing_hex[0] . $existing_hex[0] . $existing_hex[1] . $existing_hex[1] . $existing_hex[2] . $existing_hex[2];
        }
        
        $exist_r = hexdec(substr($existing_hex, 0, 2));
        $exist_g = hexdec(substr($existing_hex, 2, 2));
        $exist_b = hexdec(substr($existing_hex, 4, 2));
        
        $distance = sqrt(
            pow($new_r - $exist_r, 2) +
            pow($new_g - $exist_g, 2) +
            pow($new_b - $exist_b, 2)
        );
        
        // If colors are too similar (distance < 80), skip
        if ($distance < 80) {
            return true;
        }
    }
    
    return false;
}

function getAllThemes() {
    $themes_dir = __DIR__ . '/../themes/';
    $themes = [];
    
    if (!is_dir($themes_dir)) {
        mkdir($themes_dir, 0755, true);
        return $themes;
    }
    
    $theme_files = glob($themes_dir . '*.css');
    
    foreach ($theme_files as $theme_file) {
        $themes[] = parseThemeMetadata($theme_file);
    }
    
    return $themes;
}

$csrf_token = generate_csrf_token();
$all_themes = getAllThemes();
$current_theme_file = getCurrentThemeFile();

// ==================== HTML SECTION ====================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Themes - AxElitus CMS</title>
    <meta name="generator" content="AxElitus CMS">
    <link rel="icon" href="admin.ico?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>" type="image/x-icon">
    <link rel="preload" href="/assets/fonts/inter/inter.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/css/admin.css?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>">
    <script src="/assets/js/admin-theme.js?v=2"></script>
</head>
<body>
    <div class="app">
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
                    <span>FlaAxElitustlyPage CMS</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-label">Content</div>
                    <a href="index.php" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Homepage
                    </a>
                    <a href="index.php?tab=products" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        Pages
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-label">Site</div>
                    <a href="dashboard?tab=settings" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Settings
                    </a>
                    <a href="themes.php" class="nav-item active">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7h-3a2 2 0 0 1-2-2V2"/><path d="M9 18a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h7l4 4v10a2 2 0 0 1-2 2Z"/><path d="M3 7.6v12.8A1.6 1.6 0 0 0 4.6 22h9.8"/></svg>
                        Themes
                    </a>
                    <a href="extensions.php" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Extensions
                    </a>
                </div>
            </nav>
        </aside>
        
        <main class="main">
            <header class="main-header">
                <button class="mobile-nav-toggle" onclick="document.querySelector('.sidebar').classList.add('open')">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>

                <div class="main-header-inner">
                    <h1>Themes</h1>
                    <div class="header-actions">
                        <?php if ($current_theme_file): ?>
                            <form method="POST" action="" style="display: inline-block; margin-right: 8px;">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                <input type="hidden" name="action" value="deactivate">
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                    Deactivate Theme
                                </button>
                            </form>
                        <?php endif; ?>
                        <button type="button" class="btn btn-primary btn-sm" onclick="window.location.href='theme-edit/'">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7h-3a2 2 0 0 1-2-2V2"/><path d="M9 18a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h7l4 4v10a2 2 0 0 1-2 2Z"/><path d="M3 7.6v12.8A1.6 1.6 0 0 0 4.6 22h9.8"/></svg>
                            Create your own
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('uploadModal').classList.add('active')">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            Upload Theme
                        </button>
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

                <?php if (empty($all_themes)): ?>
                    <div class="card">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M20 7h-3a2 2 0 0 1-2-2V2"/><path d="M9 18a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h7l4 4v10a2 2 0 0 1-2 2Z"/><path d="M3 7.6v12.8A1.6 1.6 0 0 0 4.6 22h9.8"/>
                            </svg>
                            <h3>No themes installed</h3>
                            <p>Upload a theme to get started.</p>
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('uploadModal').classList.add('active')">Upload Theme</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="extensions-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px;">
                        <?php foreach ($all_themes as $theme): 
                            $is_active = $theme['file'] === $current_theme_file;
                        ?>
                            <div class="card extension-card" style="position: relative;">
                                <?php if ($is_active): ?>
                                    <div style="position: absolute; top: 16px; right: 16px;">
                                        <span class="badge" style="background: var(--success); color: white; font-size: 0.75rem; padding: 4px 12px; border-radius: 12px;">Active</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.25rem;"><?= e($theme['name']) ?></h3>
                                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0 0 12px 0;">by <?= e($theme['author']) ?></p>
                                    
                                    <?php if (!empty($theme['colors'])): ?>
                                        <div style="display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap; min-height: 24px;">
                                            <?php foreach ($theme['colors'] as $color): ?>
                                                <div title="<?= e($color) ?>" 
                                                    style="width: 24px; height: 24px; border-radius: 4px; background: <?= e($color) ?>; border: 1px solid var(--border); flex-shrink: 0;">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="display: flex; gap: 6px; margin-bottom: 16px;">
                                            <div style="width: 24px; height: 24px; border-radius: 4px; background: #333333; border: 1px solid var(--border); opacity: 0.5;"></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div style="display: flex; gap: 8px;">
                                        <?php if ($is_active): ?>
                                            <form method="POST" action="" style="flex: 1;">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                <input type="hidden" name="action" value="deactivate">
                                                <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%;">Deactivate</button>
                                            </form>
                                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this theme? It will be deactivated first.');">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="theme_file" value="<?= e($theme['file']) ?>">
                                                <button type="submit" class="btn btn-ghost btn-sm" style="color: var(--error);">
                                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="" style="flex: 1;">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <input type="hidden" name="theme_file" value="<?= e($theme['file']) ?>">
                                                <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">Activate</button>
                                            </form>
                                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this theme?');">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="theme_file" value="<?= e($theme['file']) ?>">
                                                <button type="submit" class="btn btn-ghost btn-sm" style="color: var(--error);">
                                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Upload Modal -->
    <div class="modal-overlay" id="uploadModal">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">Upload Theme</h3>
                <button type="button" class="modal-close" onclick="document.getElementById('uploadModal').classList.remove('active')">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="20" height="20">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                    <input type="hidden" name="action" value="upload">
                    
                    <div class="form-group">
                        <label class="form-label">Theme CSS File</label>
                        <input type="file" name="theme_file" class="form-input" accept=".css" required>
                        <p class="form-hint">Upload a CSS file with theme styles</p>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('uploadModal').classList.remove('active')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload Theme</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>