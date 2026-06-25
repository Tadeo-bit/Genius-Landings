<?php
/**
 * Sirve HTML de carpetas de clientes desde el docroot de admin.
 * Reescribe rutas relativas para que funcionen correctamente cuando
 * el PHP built-in server usa admin/ como docroot.
 */

$carpeta = trim($_GET['carpeta'] ?? '');
$file    = trim($_GET['file']    ?? 'index.html');

// Validar carpeta: solo letras, números y guiones
if (!preg_match('/^[a-z0-9][a-z0-9\-]*$/i', $carpeta)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Parámetro carpeta inválido.';
    exit;
}

// Validar archivo: solo .html sin barras ni segmentos peligrosos
if (!preg_match('/^[a-z0-9][a-z0-9\-]*\.html$/i', $file)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Archivo inválido.';
    exit;
}

$base_dir = realpath(__DIR__ . '/..');
$target   = realpath(__DIR__ . '/../' . $carpeta . '/' . $file);

if ($base_dir === false || $target === false
    || strpos($target, $base_dir) !== 0
    || !is_file($target)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Archivo no encontrado.';
    exit;
}

$html = file_get_contents($target);

// 1. Reescribir CSS externo (fuera del docroot admin/) → assets.php
$html = str_replace(
    'href="../css/styles.css"',
    'href="assets.php?f=css/styles.css"',
    $html
);

// 2. Reescribir links estáticos al índice público → home de admin
$html = preg_replace('/href="\.\.\/index\.html"/', 'href="index.php"', $html);

// 3. Inyectar script que parchea links de landings generados async por JS
$carpeta_js = json_encode($carpeta, JSON_UNESCAPED_UNICODE);
$inject = <<<SCRIPT
<script>
(function () {
  var CARPETA = {$carpeta_js};
  function patchLinks(root) {
    root.querySelectorAll('a[href$=".html"]').forEach(function (a) {
      var href = a.getAttribute('href');
      if (!href || href === '#'
          || href.indexOf('panel.php') !== -1
          || href.indexOf('://') !== -1) return;
      a.setAttribute(
        'href',
        'panel.php?carpeta=' + encodeURIComponent(CARPETA) +
        '&file=' + encodeURIComponent(href)
      );
    });
  }
  // Links generados async (grid de landings)
  var grid = document.getElementById('landing-grid');
  if (grid) {
    new MutationObserver(function () { patchLinks(grid); })
      .observe(grid, { childList: true, subtree: true });
  }
  // Links estáticos ya presentes al cargar
  document.addEventListener('DOMContentLoaded', function () {
    patchLinks(document.body);
  });
})();
</script>
SCRIPT;

$html = str_replace('</body>', $inject . "\n</body>", $html);

header('Content-Type: text/html; charset=UTF-8');
echo $html;
