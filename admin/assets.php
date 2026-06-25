<?php
/**
 * Sirve archivos estáticos del proyecto (fuera del docroot admin/) de forma segura.
 * Uso: assets.php?f=css/styles.css
 */

$f = trim($_GET['f'] ?? '');

// Lista blanca de archivos permitidos
$allowed = [
    'css/styles.css',
];

if (!in_array($f, $allowed, true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Acceso denegado.';
    exit;
}

$base = realpath(__DIR__ . '/..');
$path = realpath(__DIR__ . '/../' . $f);

if (!$path || !$base || strpos($path, $base) !== 0 || !is_file($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Archivo no encontrado.';
    exit;
}

$ext_types = [
    'css' => 'text/css',
    'js'  => 'application/javascript',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'svg' => 'image/svg+xml',
];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
header('Content-Type: ' . ($ext_types[$ext] ?? 'application/octet-stream') . '; charset=UTF-8');
readfile($path);
