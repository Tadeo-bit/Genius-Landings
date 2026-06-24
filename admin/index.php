<?php
require_once 'api.php';

// Clientes registrados en el panel (fuente de verdad local)
// TODO GL-F07: reemplazar este array por un CRUD real (formulario de alta/baja de clientes)
$clientes = [
    ['id' => 1, 'nombre' => 'SueñoSimple', 'carpeta' => 'suenosimple'],
    ['id' => 2, 'nombre' => 'TechStore',   'carpeta' => 'techstore'],
    ['id' => 3, 'nombre' => 'ModalAtam',   'carpeta' => 'modalatam'],
];

$campaigns = get_campaigns();
$landings  = get_landings();

$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = rtrim($script_dir, '/');
if ($base_path === '' || $base_path === '.') {
  $base_path = '';
}

function count_landings_for(array $landings, string $client): int {
  $expected = normalize_text($client);
  return count(array_filter($landings, function ($landing) use ($expected) {
    $candidate = normalize_text((string)($landing['client'] ?? ''));
    return $candidate === $expected;
  }));
}

function count_campaigns_for(array $campaigns, string $client): int {
  $expected = normalize_text($client);
  return count(array_filter($campaigns, function ($campaign) use ($expected) {
    $candidate = normalize_text((string)($campaign['client'] ?? ''));
    return $candidate === $expected;
  }));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Genius Landings</title>
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    .admin-header { background:#0f172a; color:#fff; padding:0 32px; height:56px; display:flex; align-items:center; justify-content:space-between; }
    .admin-header a { color:#94a3b8; text-decoration:none; font-size:.85rem; }
    .admin-header a:hover { color:#fff; }
    .admin-main  { max-width:1100px; margin:0 auto; padding:32px 24px; }
    .admin-grid  { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:20px; margin-top:24px; }
    .admin-card  { background:#fff; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,.1); padding:20px 24px; }
    .admin-card h3 { font-size:1rem; font-weight:700; margin-bottom:8px; }
    .admin-card .meta { font-size:.82rem; color:#64748b; margin-bottom:14px; }
    .btn { display:inline-block; padding:6px 16px; border-radius:4px; font-size:.82rem; font-weight:600; text-decoration:none; }
    .btn-primary { background:#0d6efd; color:#fff; }
    .btn-secondary { background:#e9ecef; color:#333; margin-left:6px; }
    .page-title { font-size:1.3rem; font-weight:700; }
    .page-sub   { color:#64748b; font-size:.88rem; margin-top:4px; }
  </style>
</head>
<body>
  <div class="admin-header">
    <strong>Genius Landings — Admin</strong>
    <div>
      <a href="../index.html">← Ver panel público</a>
      <a href="clientes.php" style="margin-left:16px;">Gestionar clientes</a>
    </div>
  </div>

  <div class="admin-main">
    <div class="page-title">Clientes</div>
    <div class="page-sub">Seleccioná un cliente para gestionar sus landings.</div>

    <?php if (empty($landings) && empty($campaigns)): ?>
      <p style="margin-top:20px;color:#dc3545;">⚠ No se pudo conectar con las APIs. Verificá que Budget Manager (puerto 8080) y Landing CRM (puerto 3000) estén corriendo.</p>
    <?php endif; ?>

    <div class="admin-grid">
      <?php foreach ($clientes as $c): ?>
        <?php
          $total_landings  = count_landings_for($landings, $c['nombre']);
          $total_campaigns = count_campaigns_for($campaigns, $c['nombre']);
        ?>
        <div class="admin-card">
          <h3><?= htmlspecialchars($c['nombre']) ?></h3>
          <div class="meta">
            <?= $total_landings ?> landing<?= $total_landings !== 1 ? 's' : '' ?> ·
            <?= $total_campaigns ?> campaña<?= $total_campaigns !== 1 ? 's' : '' ?>
          </div>
          <a href="<?= $base_path ?>/landings.php?cliente=<?= urlencode($c['nombre']) ?>" class="btn btn-primary">Gestionar landings</a>
          <a href="<?= $base_path ?>/panel.php?carpeta=<?= urlencode($c['carpeta']) ?>" class="btn btn-secondary">Ver panel</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
