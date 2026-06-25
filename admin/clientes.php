<?php
/**
 * GL-F07 — Gestión de clientes (alta, edición, baja)
 * Fuente de verdad: APIs de Budget Manager y Landing CRM.
 */
require_once 'api.php';

$campaigns = get_campaigns();
$landings = get_landings();
$clientes = build_clients_catalog($campaigns, $landings);

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $mensaje = 'La gestión manual de clientes está deshabilitada: los datos se sincronizan desde Budget Manager y Landing CRM.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Clientes — Genius Admin</title>
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    .admin-header { background:#0f172a; color:#fff; padding:0 32px; height:56px; display:flex; align-items:center; gap:16px; }
    .admin-header a { color:#94a3b8; text-decoration:none; font-size:.85rem; }
    .admin-main  { max-width:800px; margin:0 auto; padding:32px 24px; }
    .form-card   { background:#fff; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,.1); padding:24px; margin-bottom:24px; }
    .form-card h2 { font-size:1rem; font-weight:700; margin-bottom:16px; }
    .field { margin-bottom:14px; }
    .field label { display:block; font-size:.82rem; font-weight:600; margin-bottom:4px; }
    .field input  { width:100%; padding:8px 12px; border:1px solid #dee2e6; border-radius:4px; font-size:.88rem; }
    .btn { padding:8px 20px; border-radius:4px; font-size:.82rem; font-weight:600; border:none; cursor:pointer; }
    .btn-primary { background:#0d6efd; color:#fff; }
    .msg { padding:10px 14px; border-radius:4px; margin-bottom:16px; background:#d1e7dd; color:#0a3622; font-size:.85rem; }
    table { width:100%; border-collapse:collapse; background:#fff; border-radius:6px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.08); }
    thead { background:#212529; color:#fff; }
    thead th { padding:10px 14px; text-align:left; font-size:.8rem; }
    tbody td { padding:10px 14px; border-bottom:1px solid #e9ecef; font-size:.88rem; }
  </style>
</head>
<body>
  <div class="admin-header">
    <strong>Genius Admin</strong>
    <a href="index.php">← Volver</a>
  </div>

  <div class="admin-main">
    <h1 style="font-size:1.2rem;font-weight:700;margin-bottom:20px;">Gestión de clientes</h1>

    <?php if ($mensaje): ?>
      <div class="msg"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <!-- Formulario informativo (sin persistencia local) -->
    <div class="form-card">
      <h2>Sincronización de clientes</h2>
      <form method="POST">
        <div class="field">
          <label>Origen de datos</label>
          <input type="text" value="Budget Manager + Landing CRM" readonly>
        </div>
        <div class="field">
          <label>Estado</label>
          <input type="text" value="Clientes derivados automáticamente desde APIs" readonly>
        </div>
        <button type="submit" class="btn btn-primary">Actualizar aviso</button>
      </form>
    </div>

    <!-- Listado actual -->
    <h2 style="font-size:1rem;font-weight:700;margin-bottom:12px;">Clientes actuales</h2>
    <table>
      <thead><tr><th>ID</th><th>Nombre</th><th>Carpeta</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php if (empty($clientes)): ?>
          <tr>
            <td colspan="4" style="color:#64748b;text-align:center;">No hay clientes disponibles desde las APIs.</td>
          </tr>
        <?php endif; ?>
        <?php foreach ($clientes as $c): ?>
          <tr>
            <td><?= $c['id'] ?></td>
            <td><?= htmlspecialchars($c['nombre']) ?></td>
            <td><code><?= htmlspecialchars($c['carpeta']) ?></code></td>
            <td><a href="landings.php?cliente=<?= urlencode($c['nombre']) ?>">Ver landings</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
