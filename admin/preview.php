<?php
require_once __DIR__ . '/../config.php';

// Only allow logged in admins
require_login();

// Get blocks data from POST or GET
$blocks = [];
if (isset($_POST['blocks'])) {
    $blocks = json_decode($_POST['blocks'], true) ?: [];
} elseif (isset($_GET['blocks'])) {
    $blocks = json_decode($_GET['blocks'], true) ?: [];
}

$site_settings = get_site_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview</title>
    <link rel="preload" href="/assets/fonts/inter/inter.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/css/style.css?v=<?= date('YmdH') ?>">
    <script src="/assets/js/admin-theme.js?v=<?= date('YmdH') ?>"></script>
    <style>
        :root {
            --primary: <?= e($site_settings['primary_color'] ?? '#ffffff') ?>;
        }
        body {
            font-family: '<?= e($site_settings['website_font'] ?? 'Inter') ?>', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .preview-scale {
            transform-origin: top left;
        }

        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            background: #000;
            border-radius: 8px;
        }
        .video-wrapper iframe,
        .video-wrapper video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .video-caption {
            text-align: center;
            margin-top: 12px;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 32px;
        }
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            aspect-ratio: 4/3;
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .gallery-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
            padding: 12px;
            font-size: 0.875rem;
        }

        .faq-list {
            max-width: 800px;
            margin: 32px auto 0;
        }
        .faq-item {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .faq-question {
            padding: 20px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.125rem;
            list-style: none;
            user-select: none;
        }
        .faq-question::-webkit-details-marker {
            display: none;
        }
        .faq-answer {
            padding: 0 20px 20px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 32px;
            margin-top: 32px;
        }
        .team-member {
            text-align: center;
        }
        .team-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 16px;
        }
        .team-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 600;
            margin: 0 auto 16px;
        }
        .team-member h3 {
            margin: 0 0 4px;
            font-size: 1.25rem;
        }
        .team-role {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin: 0 0 12px;
        }
        .team-bio {
            color: var(--text-muted);
            font-size: 0.9375rem;
            line-height: 1.6;
            margin: 12px 0;
        }
        .team-social {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 16px;
        }
        .team-social-link {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text);
            transition: all 0.2s;
        }
        .team-social-link:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .team-social-link svg {
            width: 16px;
            height: 16px;
        }
    </style>
</head>
<body>
    <?php if (empty($blocks)): ?>
        <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; color: var(--text-muted); font-size: 0.875rem;">
            No content to preview
        </div>
    <?php else: ?>
        <?php foreach ($blocks as $block): ?>
            <?= render_block($block) ?>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
