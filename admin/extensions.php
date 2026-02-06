<?php
require_once __DIR__ . '/../config.php';

// Ensure user is logged in
require_login();

$message = '';
$message_type = '';
$extensions_manager = $GLOBALS['extensions'];
$update_info = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($token)) {
        $message = 'Invalid request. Please try again.';
        $message_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $ext_id = $_POST['ext_id'] ?? '';
        
        switch ($action) {
            case 'activate':
                if ($extensions_manager->activateExtension($ext_id)) {
                    $message = 'Extension activated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to activate extension.';
                    $message_type = 'error';
                }
                break;
                
            case 'deactivate':
                if ($extensions_manager->deactivateExtension($ext_id)) {
                    $message = 'Extension deactivated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to deactivate extension.';
                    $message_type = 'error';
                }
                break;
                
            case 'delete':
                if ($extensions_manager->deleteExtension($ext_id)) {
                    $message = 'Extension deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to delete extension.';
                    $message_type = 'error';
                }
                break;
                
            case 'save_settings':
                $settings = [];
                $ext = $extensions_manager->getExtension($ext_id);
                if ($ext && !empty($ext['settings'])) {
                    foreach ($ext['settings'] as $setting) {
                        $key = $setting['key'];
                        if ($setting['type'] === 'checkbox') {
                            $settings[$key] = isset($_POST['setting_' . $key]) ? '1' : '0';
                        } else {
                            $settings[$key] = $_POST['setting_' . $key] ?? '';
                        }
                    }
                }
                $extensions_manager->saveExtensionSettings($ext_id, $settings);
                $message = 'Settings saved successfully!';
                $message_type = 'success';
                break;
                
            case 'upload':
                if (isset($_FILES['extension_zip']) && $_FILES['extension_zip']['error'] === 0) {
                    $allow_update = isset($_POST['allow_update']) && $_POST['allow_update'] === '1';
                    $result = installExtensionFromZip($_FILES['extension_zip']['tmp_name'], $allow_update);
                    
                    if ($result['success']) {
                        $message = $result['updated'] ? 'Extension updated successfully!' : 'Extension installed successfully!';
                        $message_type = 'success';
                        header('Location: extensions.php');
                        exit;
                    } elseif ($result['needs_confirmation']) {
                        $update_info = $result['info'];
                        $temp_file = sys_get_temp_dir() . '/ext_' . $result['info']['id'] . '_' . time() . '.zip';
                        move_uploaded_file($_FILES['extension_zip']['tmp_name'], $temp_file);
                        $_SESSION['pending_extension_upload'] = $temp_file;
                    } else {
                        $message = 'Installation failed: ' . $result['error'];
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Please select a valid ZIP file.';
                    $message_type = 'error';
                }
                break;
                
            case 'confirm_update':
                if (isset($_SESSION['pending_extension_upload']) && file_exists($_SESSION['pending_extension_upload'])) {
                    $result = installExtensionFromZip($_SESSION['pending_extension_upload'], true);
                    unlink($_SESSION['pending_extension_upload']);
                    unset($_SESSION['pending_extension_upload']);
                    
                    if ($result['success']) {
                        $message = 'Extension updated successfully!';
                        $message_type = 'success';
                        header('Location: extensions.php');
                        exit;
                    } else {
                        $message = 'Update failed: ' . $result['error'];
                        $message_type = 'error';
                    }
                } else {
                    $message = 'No pending update found.';
                    $message_type = 'error';
                }
                break;
                
            case 'cancel_update':
                if (isset($_SESSION['pending_extension_upload']) && file_exists($_SESSION['pending_extension_upload'])) {
                    unlink($_SESSION['pending_extension_upload']);
                    unset($_SESSION['pending_extension_upload']);
                }
                $message = 'Update cancelled.';
                $message_type = 'info';
                break;
        }
    }
}

function installExtensionFromZip($zipPath, $allowUpdate = false) {
    global $extensions_manager;
    
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return ['success' => false, 'error' => 'Failed to open ZIP file'];
    }
    
    $manifestContent = $zip->getFromName('manifest.xml');
    if (!$manifestContent) {
        $zip->close();
        return ['success' => false, 'error' => 'No manifest.xml found in ZIP'];
    }
    
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($manifestContent);
    if ($xml === false) {
        $zip->close();
        return ['success' => false, 'error' => 'Invalid manifest.xml format'];
    }
    
    $extId = preg_replace('/[^a-z0-9-_]/i', '', (string)($xml->id ?? 'extension-' . time()));
    $newVersion = (string)($xml->version ?? '1.0.0');
    $extName = (string)($xml->name ?? 'Unknown Extension');
    
    $targetPath = __DIR__ . '/../extensions/' . $extId;
    $isUpdate = false;
    
    if (is_dir($targetPath)) {
        $existingExt = $extensions_manager->getExtension($extId);
        
        if (!$allowUpdate) {
            $zip->close();
            return [
                'success' => false,
                'needs_confirmation' => true,
                'info' => [
                    'id' => $extId,
                    'name' => $extName,
                    'old_version' => $existingExt['version'] ?? 'Unknown',
                    'new_version' => $newVersion
                ]
            ];
        }
        
        $savedSettings = $extensions_manager->getExtensionSettings($extId);
        $wasActive = $extensions_manager->isActive($extId);
        
        deleteDirectory($targetPath);
        $isUpdate = true;
    }
    
    if (!mkdir($targetPath, 0755, true)) {
        $zip->close();
        return ['success' => false, 'error' => 'Failed to create directory'];
    }

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        
        if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false || strpos($filename, '/') === 0) {
            continue;
        }
        
        $zip->extractTo($targetPath, $filename);
    }
    
    $zip->close();
    
    if ($isUpdate && isset($savedSettings) && isset($wasActive)) {
        if ($wasActive) {
            $extensions_manager->activateExtension($extId);
        }
        if (!empty($savedSettings)) {
            $extensions_manager->saveExtensionSettings($extId, $savedSettings);
        }
    }
    
    return ['success' => true, 'id' => $extId, 'updated' => $isUpdate];
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    
    return rmdir($dir);
}

$csrf_token = generate_csrf_token();
$all_extensions = $extensions_manager->getAllExtensions();
$active_extensions = $extensions_manager->getActiveExtensions();

$view = $_GET['view'] ?? 'list';
$ext_id = $_GET['id'] ?? '';
$current_ext = $ext_id ? $extensions_manager->getExtension($ext_id) : null;

// ==================== HTML SECTION ====================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extensions - AxElitus CMS</title>
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
                    <span>AxElitus CMS</span>
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
                    <a href="themes.php" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7h-3a2 2 0 0 1-2-2V2"/><path d="M9 18a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h7l4 4v10a2 2 0 0 1-2 2Z"/><path d="M3 7.6v12.8A1.6 1.6 0 0 0 4.6 22h9.8"/></svg>
                        Themes
                    </a>
                    <a href="extensions.php" class="nav-item active">
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
                    <h1>Extensions</h1>
                    <div class="header-actions">
                        <?php if ($view === 'list'): ?>
                            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('uploadModal').classList.add('active')">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                Upload Extension
                            </button>
                        <?php elseif ($view === 'settings' && $current_ext): ?>
                            <a href="extensions.php" class="btn btn-secondary btn-sm">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                                Back to Extensions
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

                <?php if ($view === 'settings' && $current_ext): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?= e($current_ext['name']) ?> Settings</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($current_ext['settings'])): ?>
                                <p style="color: var(--text-muted);">This extension has no configurable settings.</p>
                            <?php else: ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                    <input type="hidden" name="action" value="save_settings">
                                    <input type="hidden" name="ext_id" value="<?= e($ext_id) ?>">
                                    
                                    <?php
                                    $saved_settings = $extensions_manager->getExtensionSettings($ext_id);
                                    foreach ($current_ext['settings'] as $setting):
                                        $value = $saved_settings[$setting['key']] ?? $setting['default'];
                                    ?>
                                        <div class="form-group">
                                            <label class="form-label"><?= e($setting['label']) ?></label>
                                            <?php if ($setting['type'] === 'text'): ?>
                                                <input type="text" name="setting_<?= e($setting['key']) ?>" class="form-input" value="<?= e($value) ?>" placeholder="<?= e($setting['placeholder'] ?? '') ?>">
                                            <?php elseif ($setting['type'] === 'textarea'): ?>
                                                <textarea name="setting_<?= e($setting['key']) ?>" class="form-textarea"><?= e($value) ?></textarea>
                                            <?php elseif ($setting['type'] === 'checkbox'): ?>
                                                <div class="form-switch">
                                                    <input type="checkbox" name="setting_<?= e($setting['key']) ?>" id="<?= e($setting['key']) ?>" value="1" <?= $value ? 'checked' : '' ?>>
                                                    <label for="<?= e($setting['key']) ?>"></label>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div style="margin-top: 24px;">
                                        <button type="submit" class="btn btn-primary">Save Settings</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if (empty($all_extensions)): ?>
                        <div class="card">
                            <div class="empty-state">
                                <svg class="empty-state-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                                </svg>
                                <h3>No extensions installed</h3>
                                <p>Upload an extension to get started.</p>
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('uploadModal').classList.add('active')">Upload Extension</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="extensions-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px;">
                            <?php foreach ($all_extensions as $ext): 
                                $is_active = $extensions_manager->isActive($ext['id']);
                            ?>
                                <div class="card extension-card" style="position: relative;">
                                    <?php if ($is_active): ?>
                                        <div style="position: absolute; top: 16px; right: 16px;">
                                            <span class="badge" style="background: var(--success); color: white; font-size: 0.75rem; padding: 4px 12px; border-radius: 12px;">Active</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body">
                                        <h3 style="margin: 0 0 8px 0; font-size: 1.25rem;"><?= e($ext['name']) ?></h3>
                                        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0 0 12px 0;">v<?= e($ext['version']) ?> by <?= e($ext['author']) ?></p>
                                        <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 16px;"><?= e($ext['description']) ?></p>
                                        
                                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
                                            <?php if ($ext['type'] === 'frontend'): ?>
                                                <span style="padding: 4px 8px; background: var(--border); border-radius: 4px; font-size: 0.75rem;">Frontend</span>
                                            <?php elseif ($ext['type'] === 'admin'): ?>
                                                <span style="padding: 4px 8px; background: var(--border); border-radius: 4px; font-size: 0.75rem;">Admin</span>
                                            <?php else: ?>
                                                <span style="padding: 4px 8px; background: var(--border); border-radius: 4px; font-size: 0.75rem;">Frontend & Admin</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div style="display: flex; gap: 8px;">
                                            <?php if ($is_active): ?>
                                                <form method="POST" action="" style="flex: 1;">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                    <input type="hidden" name="action" value="deactivate">
                                                    <input type="hidden" name="ext_id" value="<?= e($ext['id']) ?>">
                                                    <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%;">Deactivate</button>
                                                </form>
                                                <?php if (!empty($ext['settings']) && ($ext['view-setting-edit-modal'] ?? 'true') !== 'false'): ?>
                                                    <a href="extensions.php?view=settings&id=<?= e($ext['id']) ?>" class="btn btn-ghost btn-sm">
                                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                                    </a>
                                                <?php endif; ?>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this extension?');">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="ext_id" value="<?= e($ext['id']) ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm" style="color: var(--error);">
                                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="" style="flex: 1;">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                    <input type="hidden" name="action" value="activate">
                                                    <input type="hidden" name="ext_id" value="<?= e($ext['id']) ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">Activate</button>
                                                </form>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this extension?');">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="ext_id" value="<?= e($ext['id']) ?>">
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
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Upload Modal -->
    <div class="modal-overlay<?= $update_info ? '' : '' ?>" id="uploadModal">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">Upload Extension</h3>
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
                        <label class="form-label">Extension ZIP File</label>
                        <input type="file" name="extension_zip" class="form-input" accept=".zip" required>
                        <p class="form-hint">Upload a ZIP file containing the extension files with manifest.xml</p>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('uploadModal').classList.remove('active')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload & Install</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Confirmation Modal -->
    <?php if ($update_info): ?>
    <div class="modal-overlay active" id="updateConfirmModal">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="24" height="24" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Update Extension?
                </h3>
            </div>
            <div class="modal-body">
                <div style="margin-bottom: 24px;">
                    <p style="font-size: 1rem; margin-bottom: 16px;">
                        <strong><?= e($update_info['name']) ?></strong> is already installed. Do you want to update it?
                    </p>
                    
                    <div style="background: var(--bg-secondary); border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <span style="color: var(--text-muted); font-size: 0.875rem;">Current version:</span>
                            <strong style="font-size: 1.125rem;"><?= e($update_info['old_version']) ?></strong>
                        </div>
                        
                        <div style="text-align: center; margin: 12px 0;">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="20" height="20" style="color: var(--primary);">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <polyline points="19 12 12 19 5 12"/>
                            </svg>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--text-muted); font-size: 0.875rem;">New version:</span>
                            <strong style="font-size: 1.125rem; color: var(--primary);"><?= e($update_info['new_version']) ?></strong>
                        </div>
                    </div>
                    
                    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
                        <p style="margin: 0; font-size: 0.875rem; color: #856404;">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16" style="display: inline-block; vertical-align: middle; margin-right: 6px;">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <strong>Note:</strong> Your current settings will be preserved during the update.
                        </p>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <form method="POST" action="" style="display: inline-block;">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        <input type="hidden" name="action" value="cancel_update">
                        <button type="submit" class="btn btn-secondary">Cancel</button>
                    </form>
                    <form method="POST" action="" style="display: inline-block;">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        <input type="hidden" name="action" value="confirm_update">
                        <button type="submit" class="btn btn-primary">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Update Extension
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>