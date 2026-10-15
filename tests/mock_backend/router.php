<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$instanceId = (string) (getenv('MOCK_INSTANCE_ID') ?: 'mock-a');

if ($path === '/health.php') {
    echo json_encode([
        'status' => 'ok',
        'service' => 'sistema-comercio',
        'instanceId' => $instanceId,
        'datasetHash' => str_repeat('a', 64),
    ]);
    return;
}

if (
    !hash_equals(
        (string) (getenv('BACKEND_GATEWAY_TOKEN') ?: ''),
        (string) ($_SERVER['HTTP_X_GATEWAY_TOKEN'] ?? '')
    )
) {
    http_response_code(403);
    echo json_encode(['error' => 'Token del Gateway requerido.']);
    return;
}

if ($path === '/obtenertodoslosclientes') {
    echo json_encode([
        'exito' => true,
        'datos' => [['id' => 1, 'nombre' => 'Cliente de prueba']],
        'instanceId' => $instanceId,
    ]);
    return;
}

http_response_code(404);
echo json_encode(['exito' => false, 'mensaje' => 'No existe la ruta.']);
