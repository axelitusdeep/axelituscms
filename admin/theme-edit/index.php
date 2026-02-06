<?php
require_once __DIR__ . '/../../config.php';
require_login();
session_start();
?>

<!-- Powered by Renderion Engine -->
<!DOCTYPE html>
<html lang="en" data-cms="axelitus">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $domain = $_SERVER['HTTP_HOST'] ?? 'localhost'; $domain = preg_replace('/[^a-z0-9\.\-]/i', '', $domain); $domain = ucfirst(strtolower($domain)); ?>
    <title>Create your own Theme</title>
    <meta name="generator" content="AxElitus CMS"> 
    <meta name="author" content="AxElitus Team">
    <meta name="robots" content="noindex, nofollow">

    <link rel="stylesheet" href="/css/styles.css?v=<?= date('YmdH') ?>">
    <link rel="stylesheet" href="/css/theme.css?v=<?= date('YmdH') ?>">

    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
    </style>

</head>

<body>

    <main>

        <section class="hero" id="hero-1">
            <div class="hero-grid"></div>
            <div class="container hero-content">
                <h1>Theme Editor</h1>
                <p>To use the <strong>Theme Builder</strong>, you must download it from the AxElitus Marketplace and extract it into this folder.</p>
                <div class="hero-buttons">
                    <a href="https://marketplace.flatlypage.com/product.php?id=theme-builder" class="btn btn-primary" target="_blank">Download from Marketplace</a>
                </div>
            </div>
        </section>

    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-brand">
                <h3>
                    AxElitus CMS
                </h3>
                <p>Build websites faster and easier.</p>
            </div>
            <div class="footer-bottom">
                <div style="flex: 1;">
                    <p>© 2026 AxElitus CMS. All rights reserved.</p>
                </div>
                <button class="theme-toggle" aria-label="Toggle theme" title="Toggle light/dark theme">
                    <svg class="theme-icon-sun" viewBox="0 0 24 24" style="display: none;">
                        <circle cx="12" cy="12" r="5" />
                        <line x1="12" y1="1" x2="12" y2="3" />
                        <line x1="12" y1="21" x2="12" y2="23" />
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
                        <line x1="1" y1="12" x2="3" y2="12" />
                        <line x1="21" y1="12" x2="23" y2="12" />
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
                    </svg>
                    <svg class="theme-icon-moon" viewBox="0 0 24 24" style="display: block;">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                    </svg>
                </button>
            </div>
        </div>
    </footer>

    <script>
        // Theme toggle functionality
        const themeToggle = document.querySelector('.theme-toggle');
        const sunIcon = document.querySelector('.theme-icon-sun');
        const moonIcon = document.querySelector('.theme-icon-moon');
        const html = document.documentElement;

        function initTheme() {
            let theme = localStorage.getItem('theme');
            if (!theme) {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                theme = prefersDark ? 'dark' : 'light';
            }
            setTheme(theme);
        }

        function setTheme(theme) {
            if (theme === 'light') {
                html.setAttribute('data-theme', 'light');
                sunIcon.style.display = 'block';
                moonIcon.style.display = 'none';
                localStorage.setItem('theme', 'light');
            } else {
                html.removeAttribute('data-theme');
                sunIcon.style.display = 'none';
                moonIcon.style.display = 'block';
                localStorage.setItem('theme', 'dark');
            }
        }

        function toggleTheme() {
            const currentTheme = html.getAttribute('data-theme') || 'dark';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            setTheme(newTheme);
        }

        if (themeToggle) {
            themeToggle.addEventListener('click', toggleTheme);
        }

        initTheme();

        document.querySelectorAll('.mobile-nav a').forEach(link => {
            link.addEventListener('click', () => {
                document.querySelector('.mobile-nav').classList.remove('active');
            });
        });
    </script>


</body>

</html>