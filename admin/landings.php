<?php
/**
 * GL-F08 — Gestión de landings por cliente.
 * Muestra las landings del cliente seleccionado y permite registrar nuevas.
 */
require_once 'api.php';

$cliente  = $_GET['cliente'] ?? '';
$landings = $cliente ? get_landings($cliente) : [];

if ($cliente && !empty($landings)) {
  $expected_client = normalize_text($cliente);
  $landings = array_values(array_filter($landings, function ($landing) use ($expected_client) {
    $candidate_client = normalize_text((string)($landing['client'] ?? ''));
    return $candidate_client === $expected_client;
  }));
}

$leads_by_landing = [];

// TODO GL-F09: cargar conteo de leads por landing desde el Landing CRM
// foreach ($landings as $l) {
//     $leads_by_landing[$l['id']] = count(get_leads($l['id']));
// }

$mensaje = '';
$mensaje_tipo = 'ok'; // 'ok' | 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']   ?? '');
  $archivo  = trim($_POST['archivo']  ?? '');
    $template = trim($_POST['template'] ?? '');

  if ($nombre && $archivo && $template && $cliente) {
        $template_ids = ['promo-event' => 1, 'lead-capture' => 3, 'product-launch' => 2];
        $template_id  = $template_ids[$template] ?? 1;

        $payload = json_encode([
            'name'       => $nombre,
            'client'     => $cliente,
            'templateId' => $template_id,
      'fields'     => [
        'fileName' => $archivo
      ]
        ]);

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload),
                'content' => $payload,
                'timeout' => 4,
                'ignore_errors' => true,
            ]
        ]);

        $response = @file_get_contents(LANDING_CRM_URL . '/api/landings', false, $context);
        $http_code = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('#HTTP/\S+\s+(\d+)#', $h, $m)) {
                $http_code = (int)$m[1];
            }
        }

        if ($response !== false && $http_code === 201) {
            $created = json_decode($response, true);
          $mensaje = "Landing '{$created['name']}' creada en Landing CRM con ID {$created['id']} y archivo sugerido '{$archivo}'.";
        } else {
            $mensaje_tipo = 'error';
            $mensaje = "Error al crear la landing en Landing CRM (HTTP $http_code). Verificá que el servidor esté corriendo.";
        }
    } else {
        $mensaje_tipo = 'error';
        $mensaje = 'Error: nombre, archivo, template y cliente son obligatorios.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Landings — <?= htmlspecialchars($cliente) ?> — Genius Admin</title>
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    .admin-header { background:#0f172a; color:#fff; padding:0 32px; height:56px; display:flex; align-items:center; gap:16px; }
    .admin-header a { color:#94a3b8; text-decoration:none; font-size:.85rem; }
    .admin-main  { max-width:900px; margin:0 auto; padding:32px 24px; }
    .form-card   { background:#fff; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,.1); padding:24px; margin-bottom:28px; }
    .form-card h2 { font-size:1rem; font-weight:700; margin-bottom:16px; }
    .field { margin-bottom:14px; }
    .field label { display:block; font-size:.82rem; font-weight:600; margin-bottom:4px; }
    .field input, .field select { width:100%; padding:8px 12px; border:1px solid #dee2e6; border-radius:4px; font-size:.88rem; }
    .btn { padding:8px 20px; border-radius:4px; font-size:.82rem; font-weight:600; border:none; cursor:pointer; }
    .btn-primary { background:#198754; color:#fff; }
    .msg-ok    { padding:10px 14px; border-radius:4px; margin-bottom:16px; background:#d1e7dd; color:#0a3622; font-size:.85rem; }
    .msg-error { padding:10px 14px; border-radius:4px; margin-bottom:16px; background:#f8d7da; color:#842029; font-size:.85rem; }
    table { width:100%; border-collapse:collapse; background:#fff; border-radius:6px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.08); }
    thead { background:#212529; color:#fff; }
    thead th { padding:10px 14px; text-align:left; font-size:.8rem; }
    tbody td { padding:10px 14px; border-bottom:1px solid #e9ecef; font-size:.88rem; }
    .badge { display:inline-block; padding:2px 10px; border-radius:12px; font-size:.72rem; font-weight:700; }
    .badge-activa   { background:#d1e7dd; color:#0a3622; }
    .badge-borrador { background:#e9ecef; color:#495057; }
    .badge-inactiva { background:#f8d7da; color:#842029; }
    .no-api-warning { color:#dc3545; background:#f8d7da; padding:12px 16px; border-radius:4px; margin-bottom:20px; font-size:.88rem; }
  </style>
</head>
<body>
  <div class="admin-header">
    <strong>Genius Admin</strong>
    <a href="index.php">← Clientes</a>
  </div>

  <div class="admin-main">
    <h1 style="font-size:1.2rem;font-weight:700;margin-bottom:4px;">
      Landings — <?= htmlspecialchars($cliente ?: 'Sin cliente') ?>
    </h1>
    <p style="color:#64748b;font-size:.85rem;margin-bottom:20px;">Registrá y administrá las landing pages del cliente.</p>

    <?php if (!$cliente): ?>
      <p style="color:#dc3545;">No se indicó ningún cliente. <a href="index.php">Volver al inicio.</a></p>
    <?php else: ?>

      <?php if ($mensaje): ?>
        <div class="msg-<?= $mensaje_tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>

      <!-- TODO GL-B04: mostrar mensaje de error si la API no responde en lugar de tabla vacía -->
      <?php if (empty($landings)): ?>
        <div class="no-api-warning">
          ⚠ No se obtuvieron landings desde la API. Verificá que el Landing CRM esté corriendo (puerto 3000) o que el cliente tenga landings registradas.
        </div>
      <?php endif; ?>

      <!-- Listado de landings -->
      <table style="margin-bottom:28px;">
        <thead><tr><th>ID</th><th>Nombre</th><th>Template</th><th>Estado</th><th>Leads</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($landings as $l): ?>
            <tr>
              <td><?= $l['id'] ?></td>
              <td><?= htmlspecialchars($l['name'] ?? $l['title'] ?? '—') ?></td>
              <td><?= htmlspecialchars($l['template'] ?? '—') ?></td>
              <td><span class="badge badge-<?= htmlspecialchars($l['status'] ?? 'borrador') ?>"><?= htmlspecialchars($l['status'] ?? 'borrador') ?></span></td>
              <td>
                <?= $leads_by_landing[$l['id']] ?? '—' ?>
                <!-- TODO GL-F09: mostrar conteo real -->
              </td>
              <td><a href="<?= LANDING_CRM_URL ?>/landings/<?= $l['id'] ?>/preview" target="_blank">Preview</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($landings)): ?>
            <tr><td colspan="6" style="color:#64748b;text-align:center;padding:16px;">Sin landings registradas.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <!-- Formulario de nueva landing — POST real al Landing CRM -->
      <div class="form-card">
        <h2>Registrar nueva landing en Landing CRM</h2>
        <form method="POST">
          <div class="field">
            <label>Nombre de la landing</label>
            <input type="text" name="nombre" placeholder="Ej: Hot Sale 2026" required>
          </div>
          <div class="field">
            <label>Nombre del archivo (sin espacios, sin .html)</label>
            <input type="text" name="archivo" placeholder="Ej: hot-sale-2026" required>
          </div>
          <div class="field">
            <label>Template</label>
            <select name="template">
              <option value="promo-event">Promo Event (ID 1)</option>
              <option value="product-launch">Product Launch (ID 2)</option>
              <option value="lead-capture">Lead Capture (ID 3)</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Crear en Landing CRM</button>
        </form>
      </div>

    <?php endif; ?>
  </div>
</body>
</html>
