<?php
require_once 'api.php';

function match_user_filter(string $stored_user, string $filter): bool {
    if ($filter === '*') return true;

    $parts = explode(':', $stored_user);
    $stored_ip   = $parts[0] ?? '';
    $stored_port = $parts[1] ?? '';

    if (str_contains($filter, ':')) {
        $fparts = explode(':', $filter);
        $filter_ip   = $fparts[0] ?? '';
        $filter_port = $fparts[1] ?? '';

        if ($filter_ip && $filter_port) {
            return $stored_ip === $filter_ip && $stored_port === $filter_port;
        }
        if ($filter_ip) {
            return $stored_ip === $filter_ip;
        }
        if ($filter_port) {
            return $stored_port === $filter_port;
        }
    }

    return str_contains($stored_ip, $filter) || str_contains($stored_port, $filter);
}

$budget_history = api_get(BUDGET_MANAGER_URL . '/api/campaigns/history');
$crm_history    = api_get(LANDING_CRM_URL . '/api/history');

$filter_entity    = $_GET['entity']    ?? '';
$filter_action    = $_GET['action']    ?? '';
$filter_user      = $_GET['user']      ?? '';
$filter_client    = $_GET['client']    ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to   = $_GET['date_to']   ?? '';

$all_history = array_merge($budget_history, $crm_history);

foreach ($all_history as &$entry) {
    $state = $entry['afterState'] ?? $entry['beforeState'] ?? [];
    $entry['_client'] = $state['client'] ?? '-';
}
unset($entry);

if ($filter_entity) {
    $all_history = array_values(array_filter($all_history, fn($e) => ($e['entityType'] ?? '') === $filter_entity));
}
if ($filter_action) {
    $all_history = array_values(array_filter($all_history, fn($e) => ($e['action'] ?? '') === $filter_action));
}
if ($filter_user && $filter_user !== '*') {
    $all_history = array_values(array_filter($all_history, function($e) use ($filter_user) {
        return match_user_filter($e['user'] ?? '', $filter_user);
    }));
}
if ($filter_client) {
    $all_history = array_values(array_filter($all_history, function($e) use ($filter_client) {
        return ($e['_client'] ?? '') === $filter_client;
    }));
}
if ($filter_date_from) {
    $all_history = array_values(array_filter($all_history, fn($e) => ($e['timestamp'] ?? '') >= $filter_date_from));
}
if ($filter_date_to) {
    $to = $filter_date_to . 'T23:59:59Z';
    $all_history = array_values(array_filter($all_history, fn($e) => ($e['timestamp'] ?? '') <= $to));
}

usort($all_history, fn($a, $b) => strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? ''));
$all_history = array_values($all_history);

$action_labels = [
    'create'        => 'Creación',
    'update'        => 'Actualización',
    'status_change' => 'Cambio de estado',
    'expense_added' => 'Gasto registrado',
    'lead_added'    => 'Lead captado',
];

$entity_labels = [
    'campaign' => 'Campaña',
    'landing'  => 'Landing',
];

$base_path = '';
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = rtrim($script_dir, '/');
if ($base_path === '.' || $base_path === '') $base_path = '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historial de Cambios — Genius Landings Admin</title>
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    .admin-header { background:#0f172a; color:#fff; padding:0 32px; height:56px; display:flex; align-items:center; justify-content:space-between; }
    .admin-header a { color:#94a3b8; text-decoration:none; font-size:.85rem; }
    .admin-header a:hover { color:#fff; }
    .admin-header a.active { color:#fff; font-weight:700; }
    .admin-main  { max-width:1100px; margin:0 auto; padding:32px 24px; }
    .page-title { font-size:1.3rem; font-weight:700; margin-bottom:4px; }
    .page-sub   { color:#64748b; font-size:.88rem; margin-bottom:20px; }

    .filter-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: flex-end;
      margin-bottom: 24px;
      padding: 16px;
      background: #fff;
      border-radius: 8px;
      border: 1px solid #e2e8f0;
    }
    .filter-bar select,
    .filter-bar input[type="date"],
    .filter-bar input[type="text"] {
      padding: 8px 12px;
      border: 1px solid #dee2e6;
      border-radius: 4px;
      font-size: .85rem;
      font-family: inherit;
    }
    .filter-bar label {
      font-size: .78rem;
      font-weight: 600;
      color: #475569;
      display: block;
      margin-bottom: 4px;
    }
    .filter-group { display: flex; flex-direction: column; }
    .filter-actions { display: flex; gap: 8px; align-items: flex-end; margin-left: auto; }

    .history-table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid #e2e8f0;
    }
    .history-table th {
      text-align: left;
      padding: 12px 14px;
      font-size: .78rem;
      font-weight: 700;
      color: #475569;
      background: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
      white-space: nowrap;
    }
    .history-table td {
      padding: 10px 14px;
      font-size: .85rem;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: top;
    }
    .history-table tr:last-child td { border-bottom: none; }
    .history-table tr:hover td { background: #f8fafc; }

    .badge-entity { display:inline-flex; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:700; }
    .badge-campaign { background:#dbeafe; color:#1e40af; }
    .badge-landing  { background:#dcfce7; color:#166534; }

    .badge-action { display:inline-flex; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:700; }
    .badge-create        { background:#dcfce7; color:#166534; }
    .badge-update        { background:#dbeafe; color:#1e40af; }
    .badge-status_change { background:#fef9c3; color:#854d0e; }
    .badge-expense_added { background:#fce7f3; color:#9d174d; }
    .badge-lead_added    { background:#e0e7ff; color:#3730a3; }

    .change-diff { font-size:.82rem; line-height:1.5; }
    .change-field { font-weight:600; color:#0f172a; }
    .diff-before { color:#dc2626; text-decoration:line-through; }
    .diff-after  { color:#16a34a; }
    .diff-arrow  { color:#94a3b8; margin:0 4px; }

    .btn-sm {
      display:inline-block;
      padding:4px 10px;
      border-radius:4px;
      font-size:.75rem;
      font-weight:600;
      text-decoration:none;
      cursor:pointer;
      border:1px solid #cbd5e1;
      background:#fff;
      color:#475569;
      transition: background .15s;
    }
    .btn-sm:hover { background:#f1f5f9; }
    .btn-primary-sm { background:#2563eb; color:#fff; border-color:#2563eb; }
    .btn-primary-sm:hover { background:#1d4ed8; }

    .detail-panel {
      display:none;
      margin-top:8px;
      padding:12px;
      background:#f8fafc;
      border-radius:6px;
      border:1px solid #e2e8f0;
      font-size:.78rem;
      max-height:400px;
      min-width:320px;
      overflow:auto;
    }
    .detail-panel pre {
      margin:0;
      white-space:pre-wrap;
      word-break:break-word;
      font-size:.75rem;
      line-height:1.5;
    }
    .detail-label { font-weight:700; color:#475569; margin-bottom:4px; }

    .empty-state {
      text-align:center;
      padding:40px 20px;
      color:#94a3b8;
      font-size:.9rem;
    }
    .empty-state p { margin-top:8px; font-size:.82rem; }

    .ip-code {
      font-family: monospace;
      font-size:.78rem;
      background:#f1f5f9;
      padding:2px 6px;
      border-radius:3px;
    }

    .timestamp { white-space:nowrap; color:#64748b; }
  </style>
</head>
<body>
  <div class="admin-header">
    <strong>Genius Landings - Admin</strong>
    <div>
      <a href="<?= $base_path ?>/">&#8592; Panel público</a>
      <a href="<?= $base_path ?>/clientes.php" style="margin-left:16px;">Clientes</a>
      <a href="<?= $base_path ?>/history.php" class="active" style="margin-left:16px;">Historial</a>
    </div>
  </div>

  <div class="admin-main">
    <div class="page-title">Historial de Cambios</div>
    <div class="page-sub">Registro de todas las acciones realizadas sobre campañas y landings. Los datos se mantienen en memoria del servidor.</div>

    <form method="GET" class="filter-bar">
      <div class="filter-group">
        <label>Entidad</label>
        <select name="entity">
          <option value="">Todas</option>
          <option value="campaign" <?= $filter_entity === 'campaign' ? 'selected' : '' ?>>Campañas</option>
          <option value="landing" <?= $filter_entity === 'landing' ? 'selected' : '' ?>>Landings</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Cliente</label>
        <select name="client">
          <option value="">Todos</option>
          <option value="SueñoSimple" <?= $filter_client === 'SueñoSimple' ? 'selected' : '' ?>>SueñoSimple</option>
          <option value="TechStore" <?= $filter_client === 'TechStore' ? 'selected' : '' ?>>TechStore</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Acción</label>
        <select name="action">
          <option value="">Todas</option>
          <?php foreach ($action_labels as $k => $v): ?>
            <option value="<?= $k ?>" <?= $filter_action === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-group">
        <label>Desde</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($filter_date_from) ?>">
      </div>
      <div class="filter-group">
        <label>Hasta</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($filter_date_to) ?>">
      </div>
      <div class="filter-group">
        <label>Usuario (IP:port)</label>
        <input type="text" name="user" value="<?= htmlspecialchars($filter_user) ?>" placeholder="Ej: 127.0.0.1">
      </div>
      <div class="filter-actions">
        <button type="submit" class="btn-sm btn-primary-sm">Filtrar</button>
        <a href="<?= $base_path ?>/history.php" class="btn-sm">Limpiar</a>
      </div>
    </form>

    <?php if (empty($all_history)): ?>
      <div class="empty-state">
        <div style="font-size:2rem;margin-bottom:8px;">📋</div>
        <div>No hay registros de historial</div>
        <p>Los registros se generan automáticamente al crear o modificar campañas y landings.</p>
        <p style="margin-top:4px;">Verificá que Budget Manager (puerto 8080) y Landing CRM (puerto 3000) estén corriendo.</p>
      </div>
    <?php else: ?>
      <table class="history-table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Entidad</th>
            <th>ID</th>
            <th>Cliente</th>
            <th>Acción</th>
            <th>Usuario</th>
            <th>Cambios</th>
            <th>Detalle</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($all_history as $entry): ?>
            <tr>
              <td class="timestamp"><?= date('d/m/Y H:i:s', strtotime($entry['timestamp'])) ?></td>
              <td>
                <?php
                  $ent = $entry['entityType'] ?? 'unknown';
                  $ent_class = $ent === 'campaign' ? 'badge-campaign' : 'badge-landing';
                ?>
                <span class="badge-entity <?= $ent_class ?>"><?= $entity_labels[$ent] ?? $ent ?></span>
              </td>
              <td><?= $entry['entityId'] ?? '-' ?></td>
              <td><?= htmlspecialchars($entry['_client'] ?? '-') ?></td>
              <td>
                <?php
                  $act = $entry['action'] ?? 'unknown';
                ?>
                <span class="badge-action badge-<?= $act ?>"><?= $action_labels[$act] ?? $act ?></span>
              </td>
              <td><span class="ip-code"><?= htmlspecialchars($entry['user'] ?? '-') ?></span></td>
              <td>
                <div class="change-diff">
                <?php
                  $changes = $entry['changes'] ?? [];
                  if (!empty($changes)):
                    foreach ($changes as $field => $diff):
                      if ($field === 'id') continue;
                ?>
                  <div>
                    <span class="change-field"><?= htmlspecialchars($field) ?>:</span>
                    <span class="diff-before"><?= htmlspecialchars(is_array($diff['before'] ?? null) ? json_encode($diff['before'], JSON_UNESCAPED_UNICODE) : ($diff['before'] ?? 'null')) ?></span>
                    <span class="diff-arrow">&rarr;</span>
                    <span class="diff-after"><?= htmlspecialchars(is_array($diff['after'] ?? null) ? json_encode($diff['after'], JSON_UNESCAPED_UNICODE) : ($diff['after'] ?? 'null')) ?></span>
                  </div>
                <?php
                    endforeach;
                  else:
                    echo '<span style="color:#94a3b8;">—</span>';
                  endif;
                ?>
                </div>
              </td>
              <td>
                <button class="btn-sm" onclick="toggleDetail(this)">Ver snapshot</button>
                <div class="detail-panel">
                  <div class="detail-label">ANTES:</div>
                  <pre><?= htmlspecialchars(json_encode($entry['beforeState'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                  <div class="detail-label" style="margin-top:10px;">DESPUÉS:</div>
                  <pre><?= htmlspecialchars(json_encode($entry['afterState'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div style="margin-top:12px;font-size:.78rem;color:#94a3b8;">
        <?= count($all_history) ?> registro<?= count($all_history) !== 1 ? 's' : '' ?>
        <?php if ($filter_entity || $filter_action || $filter_user || $filter_date_from || $filter_date_to): ?>
          (filtrado)
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

<script>
function toggleDetail(btn) {
  var panel = btn.nextElementSibling;
  if (panel.style.display === 'none') {
    panel.style.display = 'block';
    btn.textContent = 'Ocultar snapshot';
  } else {
    panel.style.display = 'none';
    btn.textContent = 'Ver snapshot';
  }
}
</script>
</body>
</html>
