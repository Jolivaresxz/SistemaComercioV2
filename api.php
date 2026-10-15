<?php

declare(strict_types=1);

use SistemaComercio\Gateway\HttpProxy;
use SistemaComercio\Gateway\RouteMap;
use SistemaComercio\Registry\RegistryStore;

require_once __DIR__ . '/SistemaComercio/Support/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function gatewayResponse(array $body, int $status): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (getenv('GATEWAY_ENABLED') !== '1') {
    gatewayResponse(['error' => 'Gateway deshabilitado en este computador.'], 503);
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    gatewayResponse(['error' => 'La API de demostración solo permite GET.'], 405);
}

$path = (string) ($_GET['path'] ?? '');
$clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$route = (new RouteMap())->resolve($path, $clientIp);

if ($route === null) {
    gatewayResponse(['error' => 'Ruta de consulta desconocida.'], 404);
}

$requestId = bin2hex(random_bytes(8));
header('X-Request-Id: ' . $requestId);

try {
    $store = new RegistryStore(trim((string) getenv('REGISTRY_DB_PATH')));
    $instances = $store->discover();

    if ($instances === []) {
        gatewayResponse(['error' => 'No hay instancias disponibles.'], 503);
    }

    shuffle($instances);
    $proxy = new HttpProxy();
    $lastError = null;

    foreach ($instances as $instance) {
        $instanceId = $instance['instance_id'];
        $url = rtrim($instance['base_url'], '/') . $route['backendPath'];

        try {
            $response = $proxy->get(
                $url,
                $requestId,
                trim((string) getenv('BACKEND_GATEWAY_TOKEN'))
            );

            if (in_array($response['status'], [502, 503, 504], true)) {
                $store->markDown($instanceId);
                continue;
            }

            header('X-Selected-Instance: ' . $instanceId);
            http_response_code($response['status']);
            echo $response['body'];
            exit;
        } catch (RuntimeException $error) {
            $lastError = $error;
            $store->markDown($instanceId);
        }
    }

    error_log((string) $lastError);
    gatewayResponse(['error' => 'Ninguna instancia respondió correctamente.'], 502);
} catch (Throwable $error) {
    error_log((string) $error);
    gatewayResponse(['error' => 'El Gateway no pudo procesar la solicitud.'], 503);
}
