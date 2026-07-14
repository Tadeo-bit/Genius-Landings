<?php
/**
 * Genius Landings Admin — helper para consumir las APIs internas.
 * Usar: require_once 'api.php';
 */

define('BUDGET_MANAGER_URL', 'http://localhost:8080');
define('LANDING_CRM_URL',    'http://localhost:3000');

function normalize_text(string $value): string {
    $value = trim($value);
    $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);

    // Normalización explícita para caracteres frecuentes en español.
    $value = strtr($value, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
        'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c'
    ]);

    $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($transliterated !== false) {
        $value = $transliterated;
    }

    // Eliminar caracteres no alfanuméricos residuales (por ejemplo '~').
    $value = preg_replace('/[^a-z0-9\s]/', '', $value) ?? $value;
    return preg_replace('/\s+/', ' ', $value) ?? $value;
}

function api_get(string $url): array {
    $real_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $context  = stream_context_create(['http' => [
        'timeout' => 3,
        'header' => "X-Forwarded-For: $real_ip\r\n"
    ]]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) return [];
    return json_decode($response, true) ?? [];
}

function get_campaigns(?string $client = null): array {
    $url = BUDGET_MANAGER_URL . '/api/campaigns';
    if ($client) $url .= '?client=' . urlencode($client);
    return api_get($url);
}

function get_landings(?string $client = null): array {
    $url = LANDING_CRM_URL . '/api/landings';
    if ($client) $url .= '?client=' . urlencode($client);
    $landings = api_get($url);

    // Fallback defensivo: si el filtro por cliente no devolvió resultados,
    // intentamos sobre el listado completo y filtramos localmente.
    if ($client && empty($landings)) {
        $all_landings = api_get(LANDING_CRM_URL . '/api/landings');
        $needle = normalize_text($client);
        $landings = array_values(array_filter($all_landings, function ($landing) use ($needle) {
            $candidate = normalize_text((string)($landing['client'] ?? ''));
            return $candidate === $needle;
        }));
    }

    return $landings;
}

function get_leads(int $landing_id): array {
    return api_get(LANDING_CRM_URL . '/api/landings/' . $landing_id . '/leads');
}

function infer_client_folder(string $client_name): string {
    $normalized = normalize_text($client_name);
    return str_replace(' ', '-', $normalized);
}

function canonical_client_name(string $client_name): string {
    return trim($client_name);
}

function get_landing_statuses(): array {
    return [
        ['value' => 'active',   'label' => 'Activa',   'class' => 'badge-active'],
        ['value' => 'draft',    'label' => 'Borrador', 'class' => 'badge-draft'],
        ['value' => 'inactive', 'label' => 'Inactiva', 'class' => 'badge-inactive'],
    ];
}

function update_landing_status(int $id, string $new_status): array {
    $payload = json_encode(['status' => $new_status]);
    $url = LANDING_CRM_URL . '/api/landings/' . $id . '/status';
    $real_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $context = stream_context_create([
        'http' => [
            'method'  => 'PATCH',
            'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\nX-Forwarded-For: $real_ip",
            'content' => $payload,
            'timeout' => 4,
            'ignore_errors' => true,
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    $http_code = 0;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#HTTP/\S+\s+(\d+)#', $h, $m)) {
            $http_code = (int)$m[1];
        }
    }

    if ($response !== false && $http_code === 200) {
        return ['success' => true, 'message' => 'Estado actualizado correctamente.'];
    }

    $error_msg = 'Error al actualizar el estado';
    if ($response !== false) {
        $decoded = json_decode($response, true);
        $error_msg .= ': ' . ($decoded['error'] ?? "HTTP $http_code");
    } else {
        $error_msg .= ': no se pudo conectar con el CRM (HTTP ' . $http_code . ')';
    }

    return ['success' => false, 'message' => $error_msg];
}

function build_clients_catalog(array $campaigns, array $landings): array {
    $catalog = [];

    $sources = array_merge($campaigns, $landings);
    foreach ($sources as $item) {
        $raw_client = trim((string)($item['client'] ?? ''));
        if ($raw_client === '') {
            continue;
        }

        $key = normalize_text($raw_client);
        if ($key === '') {
            continue;
        }

        if (!isset($catalog[$key])) {
            $catalog[$key] = [
                'id' => count($catalog) + 1,
                'nombre' => canonical_client_name($raw_client),
                'carpeta' => infer_client_folder($raw_client),
            ];
        }
    }

    usort($catalog, function ($a, $b) {
        return strcmp(normalize_text($a['nombre']), normalize_text($b['nombre']));
    });

    // Reindexar IDs luego del ordenamiento.
    foreach ($catalog as $index => $client) {
        $catalog[$index]['id'] = $index + 1;
    }

    return $catalog;
}

function get_campaign_history(array $filters = []): array {
    $url = BUDGET_MANAGER_URL . '/api/campaigns/history';
    if (!empty($filters)) $url .= '?' . http_build_query($filters);
    return api_get($url);
}

function get_landing_history(array $filters = []): array {
    $url = LANDING_CRM_URL . '/api/history';
    if (!empty($filters)) $url .= '?' . http_build_query($filters);
    return api_get($url);
}

function get_all_history(array $filters = []): array {
    $budget = get_campaign_history($filters);
    $crm    = get_landing_history($filters);
    return array_merge($budget, $crm);
}
