<?php
/**
 * Serve de puente para abrir paneles públicos de clientes aunque el docroot sea /admin.
 */

$carpeta = trim($_GET['carpeta'] ?? '');

if (!preg_match('/^[a-z0-9\-]+$/i', $carpeta)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Parámetro de carpeta inválido.';
    exit;
}

$base_dir = realpath(__DIR__ . '/..');
$target = realpath(__DIR__ . '/../' . $carpeta . '/index.html');

if ($base_dir === false || $target === false || strpos($target, $base_dir) !== 0 || !is_file($target)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Panel no encontrado para el cliente solicitado.';
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
readfile($target);
