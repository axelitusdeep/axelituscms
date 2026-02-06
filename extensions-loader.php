<?php
class ExtensionsManager {
    private $extensions = [];
    private $activeExtensions = [];
    private $hooks = [];
    private $extensionsDir = __DIR__ . '/extensions';
    private $configFile = __DIR__ . '/data/extensions.json';
    private $validTypes = ['frontend', 'admin', 'both'];
    
    public function __construct() {
        $this->loadConfig();
        $this->discoverExtensions();
        $this->loadActiveExtensions();
    }
    
    /**
     * Load extensions configuration
     */
    private function loadConfig() {
        if (file_exists($this->configFile)) {
            $config = json_decode(file_get_contents($this->configFile), true);
            $this->activeExtensions = $config['active'] ?? [];
        }
    }
    
    /**
     * Save extensions configuration
     */
    private function saveConfig() {
        $config = ['active' => $this->activeExtensions];
        if (!is_dir(dirname($this->configFile))) {
            mkdir(dirname($this->configFile), 0755, true);
        }
        file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT));
    }
    
    /**
     * Discover all available extensions
     */
    private function discoverExtensions() {
        if (!is_dir($this->extensionsDir)) {
            mkdir($this->extensionsDir, 0755, true);
            return;
        }
        
        $dirs = scandir($this->extensionsDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            $extPath = $this->extensionsDir . '/' . $dir;
            $manifestPath = $extPath . '/manifest.xml';
            
            if (is_dir($extPath) && file_exists($manifestPath)) {
                $manifest = $this->parseManifest($manifestPath, $dir);
                if ($manifest) {
                    $manifest['path'] = $extPath;
                    $manifest['id'] = $dir;
                    $this->extensions[$dir] = $manifest;
                }
            }
        }
    }
    
    /**
     * Parse extension manifest file
     */
    private function parseManifest($manifestPath, $extId) {
        try {
            $xml = simplexml_load_file($manifestPath);
            if (!$xml) return null;
            
            // Validate extension type
            $rawType = (string)($xml->type ?? 'both');
            if (!in_array($rawType, $this->validTypes)) {
                error_log("Extension validation failed: invalid configuration");
                return null;
            }
            
            $manifest = [
                'name' => (string)$xml->name,
                'version' => (string)$xml->version,
                'description' => (string)$xml->description,
                'view-setting-edit-modal' => (string)($xml->settings['view-setting-edit-modal'] ?? 'true'),
                'author' => (string)$xml->author,
                'type' => $rawType,
                'hooks' => [],
                'assets' => [],
                'settings' => []
            ];
            
            // Parse hooks
            if (isset($xml->hooks->hook)) {
                foreach ($xml->hooks->hook as $hook) {
                    $manifest['hooks'][] = [
                        'name' => (string)$hook['name'],
                        'function' => (string)$hook['function'],
                        'priority' => (int)($hook['priority'] ?? 10)
                    ];
                }
            }
            
            // Parse assets
            if (isset($xml->assets)) {
                if (isset($xml->assets->css)) {
                    foreach ($xml->assets->css as $css) {
                        $manifest['assets']['css'][] = (string)$css;
                    }
                }
                if (isset($xml->assets->js)) {
                    foreach ($xml->assets->js as $js) {
                        $manifest['assets']['js'][] = (string)$js;
                    }
                }
            }
            
            // Parse settings
            if (isset($xml->settings->setting)) {
                foreach ($xml->settings->setting as $setting) {
                    $manifest['settings'][] = [
                        'key' => (string)$setting['key'],
                        'label' => (string)$setting['label'],
                        'type' => (string)$setting['type'],
                        'default' => (string)($setting['default'] ?? ''),
                        'placeholder' => (string)($setting['placeholder'] ?? '')
                    ];
                }
            }
            
            return $manifest;
        } catch (Exception $e) {
            error_log("Extension manifest parsing failed");
            return null;
        }
    }
    
    /**
     * Load active extensions
     */
    private function loadActiveExtensions() {
        foreach ($this->activeExtensions as $extId) {
            if (isset($this->extensions[$extId])) {
                $this->loadExtension($extId);
            }
        }
    }
    
    /**
     * Load single extension
     */
    private function loadExtension($extId) {
        $ext = $this->extensions[$extId];
        $mainFile = $ext['path'] . '/main.php';
        
        if (file_exists($mainFile)) {
            require_once $mainFile;
            
            // Register hooks
            foreach ($ext['hooks'] as $hook) {
                $this->registerHook($hook['name'], $hook['function'], $hook['priority']);
            }
        }
    }
    
    /**
     * Register a hook
     */
    public function registerHook($hookName, $function, $priority = 10) {
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }
        
        $this->hooks[$hookName][] = [
            'function' => $function,
            'priority' => $priority
        ];
        
        // Sort by priority
        usort($this->hooks[$hookName], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }
    
    /**
     * Execute a hook
     */
    public function executeHook($hookName, $data = null) {
        if (!isset($this->hooks[$hookName])) {
            return $data;
        }
        
        foreach ($this->hooks[$hookName] as $hook) {
            if (is_callable($hook['function'])) {
                $data = call_user_func($hook['function'], $data);
            }
        }
        
        return $data;
    }
    
    /**
     * Check if hook exists
     */
    public function hasHook($hookName) {
        return isset($this->hooks[$hookName]) && !empty($this->hooks[$hookName]);
    }
    
    /**
     * Get all extensions
     */
    public function getAllExtensions() {
        return $this->extensions;
    }
    
    /**
     * Get active extensions
     */
    public function getActiveExtensions() {
        return array_filter($this->extensions, function($ext) {
            return in_array($ext['id'], $this->activeExtensions);
        });
    }
    
    /**
     * Get extension by ID
     */
    public function getExtension($extId) {
        return $this->extensions[$extId] ?? null;
    }
    
    /**
     * Activate extension
     */
    public function activateExtension($extId) {
        if (!isset($this->extensions[$extId])) {
            return false;
        }
        
        if (!in_array($extId, $this->activeExtensions)) {
            $this->activeExtensions[] = $extId;
            $this->saveConfig();
            $this->loadExtension($extId);
            
            // Execute activation hook
            $activationFile = $this->extensions[$extId]['path'] . '/activate.php';
            if (file_exists($activationFile)) {
                require_once $activationFile;
            }
        }
        
        return true;
    }
    
    /**
     * Deactivate extension
     */
    public function deactivateExtension($extId) {
        $key = array_search($extId, $this->activeExtensions);
        if ($key !== false) {
            unset($this->activeExtensions[$key]);
            $this->activeExtensions = array_values($this->activeExtensions);
            $this->saveConfig();
            
            // Execute deactivation hook
            if (isset($this->extensions[$extId])) {
                $deactivationFile = $this->extensions[$extId]['path'] . '/deactivate.php';
                if (file_exists($deactivationFile)) {
                    require_once $deactivationFile;
                }
            }
            
            // Remove hooks
            foreach ($this->hooks as $hookName => &$hooks) {
                $hooks = array_filter($hooks, function($hook) use ($extId) {
                    return strpos($hook['function'], $extId) === false;
                });
            }
        }
        
        return true;
    }
    
    /**
     * Is extension active
     */
    public function isActive($extId) {
        return in_array($extId, $this->activeExtensions);
    }
    
    /**
     * Get extension assets for frontend
     */
    public function getFrontendAssets($type = 'frontend') {
        $assets = ['css' => [], 'js' => []];
        
        foreach ($this->getActiveExtensions() as $ext) {
            // Skip admin-only extensions on frontend
            if ($type === 'frontend' && $ext['type'] === 'admin') {
                continue;
            }
            // Skip frontend-only extensions on admin
            if ($type === 'admin' && $ext['type'] === 'frontend') {
                continue;
            }
            
            if (!empty($ext['assets']['css'])) {
                foreach ($ext['assets']['css'] as $css) {
                    $assets['css'][] = '/extensions/' . $ext['id'] . '/' . $css;
                }
            }
            
            if (!empty($ext['assets']['js'])) {
                foreach ($ext['assets']['js'] as $js) {
                    $assets['js'][] = '/extensions/' . $ext['id'] . '/' . $js;
                }
            }
        }
        
        return $assets;
    }
    
    /**
     * Get extension settings
     */
    public function getExtensionSettings($extId) {
        $settingsFile = __DIR__ . '/data/extension-settings-' . $extId . '.json';
        if (file_exists($settingsFile)) {
            return json_decode(file_get_contents($settingsFile), true);
        }
        return [];
    }
    
    /**
     * Save extension settings
     */
    public function saveExtensionSettings($extId, $settings) {
        $settingsFile = __DIR__ . '/data/extension-settings-' . $extId . '.json';
        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
    }
    
    /**
     * Delete extension
     */
    public function deleteExtension($extId) {
        if (!isset($this->extensions[$extId])) {
            return false;
        }
        
        // Deactivate first
        $this->deactivateExtension($extId);
        
        // Delete files
        $path = $this->extensions[$extId]['path'];
        $this->deleteDirectory($path);
        
        // Remove from extensions list
        unset($this->extensions[$extId]);
        
        // Delete settings
        $settingsFile = __DIR__ . '/data/extension-settings-' . $extId . '.json';
        if (file_exists($settingsFile)) {
            unlink($settingsFile);
        }
        
        return true;
    }
    
    /**
     * Helper to delete directory recursively
     */
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
}

// Global instance
$GLOBALS['extensions'] = new ExtensionsManager();

/**
 * Helper functions for extensions
 */
function ext_hook($hookName, $data = null) {
    return $GLOBALS['extensions']->executeHook($hookName, $data);
}

function ext_has_hook($hookName) {
    return $GLOBALS['extensions']->hasHook($hookName);
}

function ext_register_hook($hookName, $function, $priority = 10) {
    $GLOBALS['extensions']->registerHook($hookName, $function, $priority);
}

function ext_get_setting($extId, $key, $default = null) {
    $settings = $GLOBALS['extensions']->getExtensionSettings($extId);
    return $settings[$key] ?? $default;
}