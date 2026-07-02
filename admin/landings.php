<?php
/**
 * GL-F08 — Gestión de landings por cliente.
 * Muestra las landings del cliente seleccionado y permite registrar nuevas.
 */
require_once 'api.php';

// Mapa de estados para usar en la tabla
$status_list = get_landing_statuses();
$status_map  = array_column($status_list, 'label', 'value');
$status_class_map = array_column($status_list, 'class', 'value');

// ─── AJAX handlers ───────────────────────────────────────
$action = $_GET['action'] ?? '';

if ($action === 'get_statuses') {
    header('Content-Type: application/json');
    echo json_encode($status_list);
    exit;
}

if ($action === 'update_status') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $id     = (int)($input['id'] ?? 0);
    $status = $input['status'] ?? '';

    if ($id && $status && isset($status_map[$status])) {
        $result = update_landing_status($id, $status);
    } else {
        $result = ['success' => false, 'message' => 'Faltan id o status, o el status no es válido.'];
    }

    echo json_encode($result);
    exit;
}

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
    .badge-wrap { display:inline-flex; align-items:center; gap:2px; cursor:pointer; }
    .badge-wrap:hover .badge-edit-icon { visibility:visible; }
    .badge-edit-icon { visibility:hidden; display:inline-block; width:14px; text-align:center; font-size:.62rem; opacity:.5; }
    .badge-select { padding:2px 8px; border-radius:12px; font-size:.72rem; font-weight:700; border:1px solid #cbd5e1; background:#fff; cursor:pointer; outline:none; }
    .badge-select:focus { border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,.2); }
    .badge-updating { opacity:.6; pointer-events:none; }
    .badge-spinner { display:inline-block; width:12px; height:12px; border:2px solid #e2e8f0; border-top-color:#3b82f6; border-radius:50%; animation:bspin .6s linear infinite; margin-left:6px; vertical-align:middle; }
    @keyframes bspin { to { transform:rotate(360deg); } }
    .toast-error { position:fixed; top:16px; right:16px; background:#dc2626; color:#fff; padding:10px 18px; border-radius:6px; font-size:.85rem; font-weight:600; box-shadow:0 4px 12px rgba(0,0,0,.15); z-index:9999; animation:toast-in .3s ease; }
    @keyframes toast-in { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    .confirm-overlay { position:fixed; inset:0; background:rgba(0,0,0,.4); display:flex; align-items:center; justify-content:center; z-index:9998; }
    .confirm-modal { background:#fff; border-radius:10px; padding:28px; max-width:420px; width:90%; box-shadow:0 8px 30px rgba(0,0,0,.15); }
    .confirm-modal h3 { font-size:1rem; font-weight:700; margin-bottom:8px; }
    .confirm-info { font-size:.85rem; color:#64748b; margin-bottom:16px; }
    .confirm-message { width:100%; padding:10px 12px; border:1px solid #dee2e6; border-radius:6px; font-size:.85rem; resize:vertical; min-height:60px; margin-bottom:16px; box-sizing:border-box; font-family:inherit; }
    .confirm-actions { display:flex; gap:10px; justify-content:flex-end; }
    .btn-cancel { background:#f1f5f9; color:#475569; }
    .btn-cancel:hover { background:#e2e8f0; }
    .btn-confirm { background:#2563eb; color:#fff; }
    .btn-confirm:hover { background:#1d4ed8; }
    .btn-confirm:disabled { opacity:.4; cursor:not-allowed; }
    .no-api-warning { color:#dc3545; background:#f8d7da; padding:12px 16px; border-radius:4px; margin-bottom:20px; font-size:.88rem; }
  </style>
</head>
<body>
  <div class="admin-header">
    <strong>Genius Admin</strong>
    <a href="index.php">← Inicio</a>
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
              <td><span class="badge-wrap" data-landing-id="<?= $l['id'] ?>" data-landing-name="<?= htmlspecialchars($l['name'] ?? $l['title'] ?? '—') ?>" data-status="<?= htmlspecialchars($l['status'] ?? 'draft') ?>"><span class="badge <?= htmlspecialchars($status_class_map[$l['status'] ?? 'draft'] ?? 'badge-draft') ?>"><?= htmlspecialchars($status_map[$l['status'] ?? 'draft'] ?? 'Borrador') ?></span><span class="badge-edit-icon">✏️</span></span></td>
              <td>
                <?= $leads_by_landing[$l['id']] ?? '—' ?>
                <!-- TODO GL-F09: mostrar conteo real -->
              </td>
              <td><a href="<?= LANDING_CRM_URL ?>/api/landings/<?= $l['id'] ?>/preview" target="_blank">Preview</a></td>
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

<script>
(function() {
  var STATUSES = [];

  fetch('?action=get_statuses')
    .then(function(r) { return r.json(); })
    .then(function(data) { STATUSES = data; })
    .catch(function() { console.warn('No se pudieron cargar los estados'); });

  function getStatusLabel(val) {
    var s = STATUSES.find(function(x) { return x.value === val; });
    return s ? s.label : val;
  }

  document.addEventListener('click', function(e) {
    var wrap = e.target.closest('.badge-wrap');
    if (!wrap || wrap.querySelector('.badge-select')) return;

    e.preventDefault();

    var id = wrap.dataset.landingId;
    var landingName = wrap.dataset.landingName;
    var currentStatus = wrap.dataset.status;
    var isUpdating = wrap.dataset.updating === 'true';

    if (isUpdating || STATUSES.length === 0) return;

    delete wrap.dataset.saving;
    wrap.dataset.updating = 'true';

    var select = document.createElement('select');
    select.className = 'badge-select';

    STATUSES.forEach(function(s) {
      var opt = document.createElement('option');
      opt.value = s.value;
      opt.textContent = s.label;
      if (s.value === currentStatus) opt.selected = true;
      select.appendChild(opt);
    });

    wrap.textContent = '';
    wrap.appendChild(select);
    select.focus();

    var restoreBadge = function(statusValue) {
      var s = STATUSES.find(function(x) { return x.value === statusValue; }) || STATUSES[0];
      wrap.className = 'badge-wrap';
      wrap.dataset.status = statusValue;
      wrap.innerHTML = '<span class="badge ' + (s.class || 'badge-draft') + '">' + s.label + '</span><span class="badge-edit-icon">\u270F\uFE0F</span>';
      wrap.dataset.updating = 'false';
      delete wrap.dataset.saving;
    };

    var outClick = function(ev) {
      if (!wrap.contains(ev.target) && wrap.dataset.saving !== 'true') {
        restoreBadge(currentStatus);
        document.removeEventListener('click', outClick);
      }
    };
    setTimeout(function() { document.addEventListener('click', outClick); }, 0);

    select.addEventListener('change', function() {
      if (wrap.dataset.saving === 'true') return;
      var newStatus = select.value;
      if (newStatus === currentStatus) {
        restoreBadge(currentStatus);
        return;
      }
      showConfirm(wrap, id, landingName, currentStatus, newStatus, restoreBadge);
    });
  });

  function showConfirm(wrap, id, landingName, currentStatus, newStatus, restoreBadge) {
    var existing = document.querySelector('.confirm-overlay');
    if (existing) existing.remove();

    var overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';
    overlay.innerHTML =
      '<div class="confirm-modal">' +
        '<h3>\u00BFEst\u00E1 seguro que desea realizar cambios sobre <strong>' + escapeHtml(landingName) + '</strong>?</h3>' +
        '<p class="confirm-info">Estado actual: <strong>' + getStatusLabel(currentStatus) + '</strong> \u2192 <strong>' + getStatusLabel(newStatus) + '</strong></p>' +
        '<label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:4px;color:#0f172a;">Escrib\u00ED el nombre de la landing para confirmar</label>' +
        '<textarea class="confirm-message" placeholder="Copi\u00E1 y peg\u00E1 el nombre de la landing para confirmar"></textarea>' +
        '<div class="confirm-actions">' +
          '<button class="btn btn-cancel">Cancelar</button>' +
          '<button class="btn btn-confirm" disabled>Confirmar</button>' +
        '</div>' +
      '</div>';

    document.body.appendChild(overlay);

    var textarea = overlay.querySelector('.confirm-message');
    var confirmBtn = overlay.querySelector('.btn-confirm');

    textarea.addEventListener('input', function() {
      confirmBtn.disabled = textarea.value.trim() !== landingName;
    });

    overlay.querySelector('.btn-cancel').addEventListener('click', function() {
      restoreBadge(currentStatus);
      overlay.remove();
    });

    confirmBtn.addEventListener('click', function() {
      if (textarea.value.trim() !== landingName) return;
      overlay.remove();
      wrap.dataset.saving = 'true';
      wrap.innerHTML = '<span class="badge">' + getStatusLabel(newStatus) + '</span><span class="badge-spinner"></span>';

      fetch('?action=update_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: Number(id), status: newStatus })
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success) throw new Error(data.message || 'Error desconocido');
        restoreBadge(newStatus);
      })
      .catch(function(err) {
        restoreBadge(currentStatus);
        var toast = document.createElement('div');
        toast.className = 'toast-error';
        toast.textContent = 'Error al actualizar estado: ' + err.message;
        document.body.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 4000);
      });
    });

    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) {
        restoreBadge(currentStatus);
        overlay.remove();
      }
    });
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }
})();
</script>
</body>
</html>
