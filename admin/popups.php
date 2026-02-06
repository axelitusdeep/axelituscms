<?php
require_once __DIR__ . '/../config.php';
require_login();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generate_popup_csrf_token() {
    if (!isset($_SESSION['popup_csrf_token'])) {
        $_SESSION['popup_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['popup_csrf_token'];
}

function verify_popup_csrf_token($token) {
    return isset($_SESSION['popup_csrf_token']) && hash_equals($_SESSION['popup_csrf_token'], $token);
}

$CONFIG_PATH = __DIR__ . '/../data/popups.json';

function advanced_popup_load_config($path) {
    if (!file_exists($path)) {
        $default = [
            'popups' => [], 
            'settings' => [
                'max_popups_per_session' => 10, 
                'global_frequency_days' => 0
            ]
        ];
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, json_encode($default, JSON_PRETTY_PRINT));
        return $default;
    }
    
    $content = file_get_contents($path);
    $decoded = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error in popups.json: " . json_last_error_msg());
        return ['popups' => [], 'settings' => []];
    }
    
    return $decoded ?: ['popups' => [], 'settings' => []];
}

function advanced_popup_save_config($path, $config) {
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        error_log("JSON encode error: " . json_last_error_msg());
        return false;
    }
    return file_put_contents($path, $json) !== false;
}

// Sanitize & validate a URL — only http / https allowed
function sanitize_popup_url($raw) {
    $url = filter_var(trim($raw), FILTER_SANITIZE_URL);
    if ($url === '') return '';

    // Must pass VALIDATE (well-formed) AND have an allowed scheme
    if (!filter_var($url, FILTER_VALIDATE_URL)) return '';

    $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');
    if (!in_array($scheme, ['http', 'https'], true)) return '';

    return $url;
}

// Sanitize popup data
function sanitize_popup_data($data) {
    $result = [
        'id' => preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['id'] ?? ''),
        'active' => (bool)($data['active'] ?? false),
        'show_close' => (bool)($data['show_close'] ?? true),
        'prevent_backdrop_close' => (bool)($data['prevent_backdrop_close'] ?? false),
        'style' => in_array($data['style'] ?? '', ['modern', 'glass', 'gradient', 'minimal']) ? $data['style'] : 'modern',
        'type' => in_array($data['type'] ?? '', ['modal', 'banner-top', 'banner-bottom', 'slide-in-br']) ? $data['type'] : 'modal',
        'badge' => substr($data['badge'] ?? '', 0, 50),
        'title' => substr($data['title'] ?? '', 0, 200),
        'description' => substr($data['description'] ?? '', 0, 1000),
        'cta_url' => sanitize_popup_url($data['cta_url'] ?? ''),
        'cta_text' => substr($data['cta_text'] ?? '', 0, 100),
        'trigger' => [
            'type' => in_array($data['trigger']['type'] ?? '', ['immediate', 'delay', 'scroll', 'exit_intent', 'inactivity']) ? $data['trigger']['type'] : 'immediate',
            'delay' => max(0, min(300, intval($data['trigger']['delay'] ?? 0))),
            'scroll_percent' => max(0, min(100, intval($data['trigger']['scroll_percent'] ?? 0))),
            'inactivity_seconds' => max(0, min(600, intval($data['trigger']['inactivity_seconds'] ?? 0))),
        ],
        'frequency_days' => max(0, min(365, intval($data['frequency_days'] ?? 0))),
        'auto_close_seconds' => max(0, min(300, intval($data['auto_close_seconds'] ?? 0))),
    ];

    // Optional targeting
    if (isset($data['targeting'])) {
        $result['targeting'] = [
            'device' => in_array($data['targeting']['device'] ?? '', ['', 'desktop', 'mobile']) ? $data['targeting']['device'] : '',
            'visitor_type' => in_array($data['targeting']['visitor_type'] ?? '', ['', 'new']) ? $data['targeting']['visitor_type'] : '',
        ];
    }

    // Optional conditions
    if (isset($data['conditions']['url_contains']) && $data['conditions']['url_contains'] !== '') {
        $result['conditions'] = [
            'url_contains' => substr($data['conditions']['url_contains'], 0, 200),
        ];
    }

    return $result;
}

// API Handler
if (isset($_GET['popup_api'])) {
    // Verify CSRF for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['csrf_token']) || !verify_popup_csrf_token($input['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }
    }
    
    ob_clean();
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Cache-Control: no-store');
    $action = $_GET['popup_api'];
    $config = advanced_popup_load_config($CONFIG_PATH);

    switch ($action) {
        case 'get_popups':
            echo json_encode([
                'success' => true, 
                'data' => $config,
                'csrf_token' => generate_popup_csrf_token()
            ]);
            break;

        case 'save_popup':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id']) || $data['id'] === '') {
                echo json_encode(['success' => false, 'error' => 'Missing ID']);
                break;
            }
            
            $sanitized = sanitize_popup_data($data);
            
            $found = false;
            foreach ($config['popups'] as &$popup) {
                if ($popup['id'] === $sanitized['id']) {
                    $popup = $sanitized;
                    $found = true;
                    break;
                }
            }
            unset($popup);
            
            if (!$found) {
                $config['popups'][] = $sanitized;
            }
            
            if (advanced_popup_save_config($CONFIG_PATH, $config)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save']);
            }
            break;

        case 'delete_popup':
            $data = json_decode(file_get_contents('php://input'), true);
            $config['popups'] = array_values(array_filter(
                $config['popups'], 
                fn($p) => $p['id'] !== ($data['id'] ?? '')
            ));
            
            if (advanced_popup_save_config($CONFIG_PATH, $config)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
    exit;
}

$csrf_token = generate_popup_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Popup Manager</title>
<!-- Global SaaS stylesheet (your main app CSS) -->
<link rel="stylesheet" href="../css/admin.css">

<style>
/* ─── Popup Manager — scoped additions only ─── */

/* Page layout (fits inside .main from global) */
.pm-page {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: var(--bg);
    color: var(--text);
}

.pm-header {
    background: rgba(10, 10, 10, 0.8);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    height: 64px;
    flex-shrink: 0;
    position: sticky;
    top: 0;
    z-index: 50;
}

.pm-header-inner {
    max-width: 1080px;
    margin: 0 auto;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.pm-header h1 {
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: -0.02em;
}

.pm-content {
    flex: 1;
    padding: 32px;
    max-width: 1080px;
    margin: 0 auto;
    width: 100%;
}

/* Popup cards grid */
.pm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 12px;
}

/* Card overrides — inactive state */
.pm-card.inactive {
    opacity: 0.38;
    filter: grayscale(1);
}

.pm-card .card-header {
    padding: 16px 18px 10px;
    align-items: flex-start;
    gap: 12px;
}

.pm-card .card-header-left {
    flex: 1;
    min-width: 0;
}

.pm-card .card-header-left strong {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pm-card .card-header-left small {
    font-size: 0.6875rem;
    color: var(--text-subtle);
    font-family: ui-monospace, 'SF Mono', 'Fira Code', monospace;
}

/* Tags row */
.pm-tags {
    padding: 10px 18px 12px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.pm-tag {
    display: inline-flex;
    align-items: center;
    font-size: 0.6875rem;
    font-weight: 500;
    border-radius: 6px;
    padding: 3px 8px;
    white-space: nowrap;
    background: var(--bg-elevated);
    border: 1px solid var(--border);
    color: var(--text-muted);
}

.pm-tag--active {
    border-color: rgba(34, 197, 94, 0.25);
    color: var(--success);
    background: rgba(34, 197, 94, 0.08);
}

.pm-tag--inactive {
    border-color: rgba(239, 68, 68, 0.2);
    color: var(--error);
    background: rgba(239, 68, 68, 0.06);
}

.pm-tag--cta {
    border-color: rgba(59, 130, 246, 0.25);
    color: var(--blue);
    background: rgba(59, 130, 246, 0.08);
}

/* Card footer */
.pm-card .card-footer {
    padding: 10px 18px 14px;
    margin-top: auto;
    border-top: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.pm-card-actions {
    display: flex;
    gap: 6px;
    align-items: center;
}

/* Empty state */
.pm-empty {
    text-align: center;
    padding: 80px 24px;
    color: var(--text-muted);
}

.pm-empty-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 16px;
    opacity: 0.25;
    color: var(--text-muted);
}

.pm-empty h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 6px;
}

.pm-empty p {
    font-size: 0.875rem;
    margin-bottom: 24px;
    max-width: 360px;
    margin-left: auto;
    margin-right: auto;
}

/* ─── Edit modal — tabs ─── */
.pm-tabs {
    display: flex;
    gap: 2px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 22px;
}

.pm-tab {
    padding: 8px 16px;
    cursor: pointer;
    border: none;
    background: none;
    color: var(--text-muted);
    font-size: 0.8125rem;
    font-weight: 500;
    font-family: inherit;
    border-radius: var(--radius-sm) var(--radius-sm) 0 0;
    position: relative;
    bottom: -1px;
    border-bottom: 2px solid transparent;
    transition: color 0.15s, background 0.15s;
    white-space: nowrap;
}

.pm-tab:hover {
    color: var(--text);
    background: var(--bg-hover);
}

.pm-tab.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
    font-weight: 600;
}

.pm-panel {
    display: none;
}

.pm-panel.active {
    display: block;
}

/* ─── Form layout inside modal ─── */
.pm-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.pm-row + .pm-row,
.pm-row + .form-group,
.form-group + .pm-row {
    margin-top: 14px;
}

/* Section dividers inside modal */
.pm-divider {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-subtle);
    margin: 22px 0 14px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border);
}

/* Toggle switch */
.pm-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
}

.pm-toggle-label {
    font-size: 0.8125rem;
    color: var(--text);
    font-weight: 500;
}

.pm-toggle-hint {
    font-size: 0.6875rem;
    color: var(--text-subtle);
    margin-top: 2px;
}

/* Checkbox row */
.pm-check-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 0;
}

.pm-check-row input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: var(--blue);
    cursor: pointer;
}

.pm-check-row label {
    font-size: 0.8125rem;
    color: var(--text);
    cursor: pointer;
}

/* Modal footer */
.pm-modal-footer {
    padding: 16px 24px 20px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 4px;
}

.pm-modal-footer small {
    color: var(--text-subtle);
    font-size: 0.6875rem;
}

/* Toast / notification */
.pm-toast {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%) translateY(12px);
    background: var(--bg-elevated);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 10px 18px;
    font-size: 0.8125rem;
    color: var(--text);
    box-shadow: 0 8px 24px rgba(0,0,0,0.35);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s, transform 0.2s;
    z-index: 200;
    white-space: nowrap;
}

.pm-toast.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

.pm-toast--success {
    border-color: rgba(34, 197, 94, 0.3);
    color: var(--success);
}

.pm-toast--error {
    border-color: rgba(239, 68, 68, 0.3);
    color: var(--error);
}

/* ─── Responsive ─── */
@media (max-width: 768px) {
    .pm-header { padding: 0 16px; }
    .pm-content { padding: 20px 16px; }
    .pm-grid { grid-template-columns: 1fr; }
    .pm-row { grid-template-columns: 1fr; }
    .form-input, .form-select, .form-textarea { font-size: 1rem; min-height: 48px; }
}
</style>
</head>
<body>

<!-- ─── Page shell ─── -->
<div class="pm-page">

    <!-- Header -->
    <header class="pm-header">
        <div class="pm-header-inner">
            <h1>Popup Manager</h1>
            <button class="btn btn-primary btn-sm" onclick="openModal()">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 1v12M1 7h12" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                New Popup
            </button>
        </div>
    </header>

    <!-- Body -->
    <main class="pm-content">
        <div class="pm-grid" id="popupGrid">
            <!-- Cards injected by JS -->
        </div>
        <!-- Empty state (shown when no popups) -->
        <div class="pm-empty" id="emptyState" style="display:none;">
            <svg class="pm-empty-icon" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="6" y="10" width="36" height="28" rx="4"/>
                <path d="M6 18h36"/>
                <circle cx="14" cy="14" r="2" fill="currentColor" stroke="none"/>
                <circle cx="22" cy="14" r="2" fill="currentColor" stroke="none"/>
            </svg>
            <h3>No popups yet</h3>
            <p>Create your first popup to start engaging your visitors.</p>
            <button class="btn btn-primary btn-sm" onclick="openModal()">Create Popup</button>
        </div>
    </main>
</div>

<!-- ─── Edit / Create Modal ─── -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal" style="max-width:760px;">

        <!-- Modal header -->
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">New Popup</h2>
            <button class="modal-close" onclick="closeModal()">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M4 4l10 10M14 4 4 14" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
            </button>
        </div>

        <!-- Modal body -->
        <div class="modal-body" style="padding: 20px 24px 0;">

            <!-- Tabs -->
            <div class="pm-tabs">
                <button class="pm-tab active" data-tab="content">Content</button>
                <button class="pm-tab" data-tab="behavior">Behavior</button>
                <button class="pm-tab" data-tab="targeting">Targeting</button>
            </div>

            <!-- ── Content tab ── -->
            <div class="pm-panel active" data-panel="content">
                <div class="form-group">
                    <label class="form-label">Popup ID</label>
                    <input type="text" class="form-input" id="popupId" placeholder="e.g. welcome_modal" readonly>
                </div>

                <div class="pm-row">
                    <div class="form-group">
                        <label class="form-label">Badge</label>
                        <input type="text" class="form-input" id="popupBadge" placeholder="e.g. New">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Style</label>
                        <select class="form-input" id="popupStyle">
                            <option value="modern">Modern</option>
                            <option value="glass">Glass</option>
                            <option value="gradient">Gradient</option>
                            <option value="minimal">Minimal</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-input" id="popupTitle" placeholder="Popup title">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-input form-textarea" id="popupDescription" placeholder="Popup description…"></textarea>
                </div>

                <div class="pm-row">
                    <div class="form-group">
                        <label class="form-label">CTA URL</label>
                        <input type="url" class="form-input" id="popupCtaUrl" placeholder="https://…">
                    </div>
                    <div class="form-group">
                        <label class="form-label">CTA Text</label>
                        <input type="text" class="form-input" id="popupCtaText" placeholder="Learn more">
                    </div>
                </div>

                <div class="pm-divider">Options</div>

                <div class="pm-toggle-row">
                    <div>
                        <div class="pm-toggle-label">Active</div>
                        <div class="pm-toggle-hint">Popup will be shown to visitors</div>
                    </div>
                    <div class="form-switch">
                        <input type="checkbox" id="popupActive">
                        <label for="popupActive"></label>
                    </div>
                </div>

                <div class="pm-check-row">
                    <input type="checkbox" id="popupShowClose">
                    <label for="popupShowClose">Show close button</label>
                </div>
                <div class="pm-check-row">
                    <input type="checkbox" id="popupPreventBackdrop">
                    <label for="popupPreventBackdrop">Prevent close on backdrop click</label>
                </div>
            </div>

            <!-- ── Behavior tab ── -->
            <div class="pm-panel" data-panel="behavior">
                <div class="form-group">
                    <label class="form-label">Popup Type</label>
                    <select class="form-input" id="popupType">
                        <option value="modal">Modal (center)</option>
                        <option value="banner-top">Banner — Top</option>
                        <option value="banner-bottom">Banner — Bottom</option>
                        <option value="slide-in-br">Slide-in — Bottom Right</option>
                    </select>
                </div>

                <div class="pm-divider">Trigger</div>

                <div class="form-group">
                    <label class="form-label">Trigger Type</label>
                    <select class="form-input" id="triggerType">
                        <option value="immediate">Immediate</option>
                        <option value="delay">After delay</option>
                        <option value="scroll">On scroll</option>
                        <option value="exit_intent">Exit intent</option>
                        <option value="inactivity">Inactivity</option>
                    </select>
                </div>

                <div class="pm-row">
                    <div class="form-group" id="triggerDelayGroup" style="display:none;">
                        <label class="form-label">Delay (seconds)</label>
                        <input type="number" class="form-input" id="triggerDelay" min="0" max="300" value="0">
                    </div>
                    <div class="form-group" id="triggerScrollGroup" style="display:none;">
                        <label class="form-label">Scroll %</label>
                        <input type="number" class="form-input" id="triggerScroll" min="0" max="100" value="50">
                    </div>
                    <div class="form-group" id="triggerInactivityGroup" style="display:none;">
                        <label class="form-label">Inactivity (seconds)</label>
                        <input type="number" class="form-input" id="triggerInactivity" min="0" max="600" value="30">
                    </div>
                </div>

                <div class="pm-divider">Timing</div>

                <div class="pm-row">
                    <div class="form-group">
                        <label class="form-label">Frequency (days)</label>
                        <input type="number" class="form-input" id="popupFrequency" min="0" max="365" value="0">
                        <p class="form-hint">0 = show every session</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Auto-close (seconds)</label>
                        <input type="number" class="form-input" id="popupAutoClose" min="0" max="300" value="0">
                        <p class="form-hint">0 = no auto-close</p>
                    </div>
                </div>
            </div>

            <!-- ── Targeting tab ── -->
            <div class="pm-panel" data-panel="targeting">
                <div class="pm-row">
                    <div class="form-group">
                        <label class="form-label">Device</label>
                        <select class="form-input" id="targetDevice">
                            <option value="">All devices</option>
                            <option value="desktop">Desktop</option>
                            <option value="mobile">Mobile</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Visitor Type</label>
                        <select class="form-input" id="targetVisitor">
                            <option value="">All visitors</option>
                            <option value="new">New visitors only</option>
                        </select>
                    </div>
                </div>

                <div class="pm-divider">Conditions</div>

                <div class="form-group">
                    <label class="form-label">URL contains</label>
                    <input type="text" class="form-input" id="conditionUrl" placeholder="e.g. /pricing">
                    <p class="form-hint">Leave empty to show on all pages</p>
                </div>
            </div>
        </div>

        <!-- Modal footer -->
        <div class="pm-modal-footer">
            <small id="modalHint">Fill in the details and save</small>
            <div style="display:flex; gap:10px;">
                <button class="btn btn-secondary btn-sm" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary btn-sm" onclick="savePopup()">Save Popup</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="pm-toast" id="toast"></div>

<!-- ─── Scripts ─── -->
<script>
(function () {
    // ─── State ───
    let popups = [];
    let csrfToken = '';
    let editingId = null;

    // ─── Init ───
    async function init() {
        const res = await fetch('?popup_api=get_popups');
        const json = await res.json();
        if (json.success) {
            popups = json.data.popups || [];
            csrfToken = json.csrf_token;
            renderGrid();
        }
    }

    // ─── Render grid ───
    function renderGrid() {
        const grid = document.getElementById('popupGrid');
        const empty = document.getElementById('emptyState');

        if (popups.length === 0) {
            grid.innerHTML = '';
            empty.style.display = 'block';
            return;
        }

        empty.style.display = 'none';
        grid.innerHTML = popups.map(p => `
            <div class="card pm-card ${p.active ? '' : 'inactive'}">
                <div class="card-header">
                    <div class="card-header-left">
                        <strong>${escHtml(p.title || '(untitled)')}</strong>
                        <small>${escHtml(p.id)}</small>
                    </div>
                    <button class="btn btn-ghost btn-sm btn-icon" onclick="deletePopup('${escHtml(p.id)}')" title="Delete">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 4h10m-1 0v7a1 1 0 01-1 1H4a1 1 0 01-1-1V4m2 0V3a1 1 0 011-1h2a1 1 0 011 1v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <div class="pm-tags">
                    <span class="pm-tag ${p.active ? 'pm-tag--active' : 'pm-tag--inactive'}">${p.active ? 'Active' : 'Inactive'}</span>
                    <span class="pm-tag">${escHtml(p.type || 'modal')}</span>
                    <span class="pm-tag">${escHtml(p.style || 'modern')}</span>
                    ${p.cta_url ? '<span class="pm-tag pm-tag--cta">CTA</span>' : ''}
                </div>
                <div class="card-footer">
                    <span class="pm-tag">${escHtml(p.trigger?.type || 'immediate')}</span>
                    <button class="btn btn-secondary btn-sm" onclick="openModal('${escHtml(p.id)}')">Edit</button>
                </div>
            </div>
        `).join('');
    }

    // ─── Modal open/close ───
    window.openModal = function (id) {
        editingId = id || null;

        if (editingId) {
            const p = popups.find(x => x.id === editingId);
            if (!p) return;
            document.getElementById('modalTitle').textContent = 'Edit Popup';
            document.getElementById('modalHint').textContent = 'ID: ' + p.id;
            fillForm(p);
        } else {
            document.getElementById('modalTitle').textContent = 'New Popup';
            document.getElementById('modalHint').textContent = 'Fill in the details and save';
            clearForm();
        }

        // Reset to first tab
        switchTab('content');
        document.getElementById('modalOverlay').classList.add('active');
    };

    window.closeModal = function () {
        document.getElementById('modalOverlay').classList.remove('active');
    };

    // Close on backdrop click
    document.getElementById('modalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });

    // ─── Tabs ───
    function switchTab(name) {
        document.querySelectorAll('.pm-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === name));
        document.querySelectorAll('.pm-panel').forEach(p => p.classList.toggle('active', p.dataset.panel === name));
    }

    document.querySelectorAll('.pm-tab').forEach(tab => {
        tab.addEventListener('click', () => switchTab(tab.dataset.tab));
    });

    // ─── Trigger-type conditional fields ───
    function updateTriggerFields() {
        const type = document.getElementById('triggerType').value;
        document.getElementById('triggerDelayGroup').style.display = type === 'delay' ? 'block' : 'none';
        document.getElementById('triggerScrollGroup').style.display = type === 'scroll' ? 'block' : 'none';
        document.getElementById('triggerInactivityGroup').style.display = type === 'inactivity' ? 'block' : 'none';
    }

    document.getElementById('triggerType').addEventListener('change', updateTriggerFields);

    // ─── Form helpers ───
    function fillForm(p) {
        document.getElementById('popupId').value = p.id || '';
        document.getElementById('popupBadge').value = p.badge || '';
        document.getElementById('popupTitle').value = p.title || '';
        document.getElementById('popupDescription').value = p.description || '';
        document.getElementById('popupCtaUrl').value = p.cta_url || '';
        document.getElementById('popupCtaText').value = p.cta_text || '';
        document.getElementById('popupStyle').value = p.style || 'modern';
        document.getElementById('popupType').value = p.type || 'modal';
        document.getElementById('popupActive').checked = !!p.active;
        document.getElementById('popupShowClose').checked = p.show_close !== false;
        document.getElementById('popupPreventBackdrop').checked = !!p.prevent_backdrop_close;

        // Trigger
        const trigger = p.trigger || {};
        document.getElementById('triggerType').value = trigger.type || 'immediate';
        document.getElementById('triggerDelay').value = trigger.delay || 0;
        document.getElementById('triggerScroll').value = trigger.scroll_percent || 50;
        document.getElementById('triggerInactivity').value = trigger.inactivity_seconds || 30;
        updateTriggerFields();

        // Timing
        document.getElementById('popupFrequency').value = p.frequency_days || 0;
        document.getElementById('popupAutoClose').value = p.auto_close_seconds || 0;

        // Targeting
        document.getElementById('targetDevice').value = (p.targeting && p.targeting.device) || '';
        document.getElementById('targetVisitor').value = (p.targeting && p.targeting.visitor_type) || '';
        document.getElementById('conditionUrl').value = (p.conditions && p.conditions.url_contains) || '';
    }

    function clearForm() {
        const newId = 'popup_' + Date.now().toString(36).slice(-6);
        document.getElementById('popupId').value = newId;
        document.getElementById('popupBadge').value = '';
        document.getElementById('popupTitle').value = '';
        document.getElementById('popupDescription').value = '';
        document.getElementById('popupCtaUrl').value = '';
        document.getElementById('popupCtaText').value = '';
        document.getElementById('popupStyle').value = 'modern';
        document.getElementById('popupType').value = 'modal';
        document.getElementById('popupActive').checked = true;
        document.getElementById('popupShowClose').checked = true;
        document.getElementById('popupPreventBackdrop').checked = false;
        document.getElementById('triggerType').value = 'immediate';
        document.getElementById('triggerDelay').value = 0;
        document.getElementById('triggerScroll').value = 50;
        document.getElementById('triggerInactivity').value = 30;
        updateTriggerFields();
        document.getElementById('popupFrequency').value = 0;
        document.getElementById('popupAutoClose').value = 0;
        document.getElementById('targetDevice').value = '';
        document.getElementById('targetVisitor').value = '';
        document.getElementById('conditionUrl').value = '';
    }

    function readForm() {
        return {
            id: document.getElementById('popupId').value,
            badge: document.getElementById('popupBadge').value,
            title: document.getElementById('popupTitle').value,
            description: document.getElementById('popupDescription').value,
            cta_url: document.getElementById('popupCtaUrl').value,
            cta_text: document.getElementById('popupCtaText').value,
            style: document.getElementById('popupStyle').value,
            type: document.getElementById('popupType').value,
            active: document.getElementById('popupActive').checked,
            show_close: document.getElementById('popupShowClose').checked,
            prevent_backdrop_close: document.getElementById('popupPreventBackdrop').checked,
            trigger: {
                type: document.getElementById('triggerType').value,
                delay: parseInt(document.getElementById('triggerDelay').value) || 0,
                scroll_percent: parseInt(document.getElementById('triggerScroll').value) || 0,
                inactivity_seconds: parseInt(document.getElementById('triggerInactivity').value) || 0,
            },
            frequency_days: parseInt(document.getElementById('popupFrequency').value) || 0,
            auto_close_seconds: parseInt(document.getElementById('popupAutoClose').value) || 0,
            targeting: {
                device: document.getElementById('targetDevice').value,
                visitor_type: document.getElementById('targetVisitor').value,
            },
            conditions: {
                url_contains: document.getElementById('conditionUrl').value,
            },
            csrf_token: csrfToken,
        };
    }

    // ─── Save ───
    window.savePopup = async function () {
        const data = readForm();
        if (!data.title.trim()) { showToast('Title is required', 'error'); return; }

        const res = await fetch('?popup_api=save_popup', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
        const json = await res.json();

        if (json.success) {
            showToast('Popup saved', 'success');
            closeModal();
            await init();
        } else {
            showToast(json.error || 'Save failed', 'error');
        }
    };

    // ─── Delete ───
    window.deletePopup = async function (id) {
        if (!confirm('Delete this popup?')) return;

        const res = await fetch('?popup_api=delete_popup', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, csrf_token: csrfToken }),
        });
        const json = await res.json();

        if (json.success) {
            showToast('Popup deleted', 'success');
            await init();
        } else {
            showToast(json.error || 'Delete failed', 'error');
        }
    };

    // ─── Toast ───
    let toastTimer;
    function showToast(msg, type) {
        const el = document.getElementById('toast');
        el.textContent = msg;
        el.className = 'pm-toast pm-toast--' + type + ' show';
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => el.classList.remove('show'), 2400);
    }

    // ─── Util ───
    function escHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // ─── Boot ───
    init();
})();
</script>
</body>
</html>