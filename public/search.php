<?php

require __DIR__ . '/../app/bootstrap.php';

$query = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
if ($query === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Consulta vacia.']);
    exit;
}

$endpoint = 'https://photon.komoot.io/api/';
$params = http_build_query([
    'q' => $query,
    'limit' => 1,
]);
$url = $endpoint . '?' . $params;

$userAgent = 'GeoVisor/1.0 (contacto@example.com)';

$response = null;
$status = 0;

if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    // En entornos locales sin CA configurada, evitamos fallo por SSL.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false || $status < 200 || $status >= 300) {
        $response = null;
    }
}

if ($response === null) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 8,
            'header' => "User-Agent: {$userAgent}\r\nAccept: application/json\r\n",
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    $headers = function_exists('http_get_last_response_headers')
        ? http_get_last_response_headers()
        : (isset($http_response_header) ? $http_response_header : []);

    if (!empty($headers[0]) && preg_match('/\s(\d{3})\s/', $headers[0], $matches)) {
        $status = (int)$matches[1];
    }

    if ($response === false || $status < 200 || $status >= 300) {
        $response = null;
    }
}

if ($response === null || $response === false) {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No se pudo consultar el servicio.']);
    exit;
}

$payload = json_decode($response, true);
if (!is_array($payload) || empty($payload['features'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([]);
    exit;
}

$feature = $payload['features'][0];
$coordinates = $feature['geometry']['coordinates'] ?? null;
if (!is_array($coordinates) || count($coordinates) < 2) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([]);
    exit;
}

$lon = $coordinates[0];
$lat = $coordinates[1];
$labelParts = [];
if (!empty($feature['properties']['name'])) {
    $labelParts[] = $feature['properties']['name'];
}
if (!empty($feature['properties']['city'])) {
    $labelParts[] = $feature['properties']['city'];
}
if (!empty($feature['properties']['country'])) {
    $labelParts[] = $feature['properties']['country'];
}
$label = !empty($labelParts) ? implode(', ', $labelParts) : $query;

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    [
        'lat' => (string)$lat,
        'lon' => (string)$lon,
        'display_name' => $label,
    ],
]);
