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
    $context  = stream_context_create(['http' => ['timeout' => 3]]);
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
