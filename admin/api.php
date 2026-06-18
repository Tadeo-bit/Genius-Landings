<?php
/**
 * Genius Landings Admin — helper para consumir las APIs internas.
 * Usar: require_once 'api.php';
 */

define('BUDGET_MANAGER_URL', 'http://localhost:8080');
define('LANDING_CRM_URL',    'http://localhost:3000');

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
    return api_get($url);
}

function get_leads(int $landing_id): array {
    return api_get(LANDING_CRM_URL . '/api/landings/' . $landing_id . '/leads');
}
