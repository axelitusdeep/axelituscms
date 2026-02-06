<?php
function safe_version($url) {
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
    
    $fullPath = $docRoot . '/' . ltrim($url, '/');

    if (file_exists($fullPath)) {
        return $url . '?v=' . filemtime($fullPath);
    }
    
    return $url;
}