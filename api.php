<?php

declare(strict_types=1);

use SistemaComercio\Gateway\HttpProxy;
use SistemaComercio\Gateway\RouteMap;
use SistemaComercio\Gateway\Balancer\StrategyFactory;
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
$algorithm = trim((string) ($_GET['algorithm'] ?? getenv('LOAD_BALANCER') ?: 'random'));

try {
    StrategyFactory::make($algorithm);
} catch (InvalidArgumentException $error) {
    gatewayResponse(['error' => $error->getMessage()], 400);
}

header('X-Algorithm: ' . $algorithm);

try {
    $store = new RegistryStore(trim((string) getenv('REGISTRY_DB_PATH')));

    if ($store->discover() === []) {
        gatewayResponse(['error' => 'No hay instancias disponibles.'], 503);
    }

    $proxy = new HttpProxy();
    $lastError = null;
    $excludedIds = [];

    for ($attempt = 1; $attempt <= 16; $attempt++) {
        $attemptId = $requestId . '-' . $attempt;
        $instance = $store->reserve(
            $attemptId,
            $clientIp,
            $route['route'],
            $algorithm,
            $route['hashKey'],
            $excludedIds
        );

        if ($instance === null) {
            break;
        }

        $instanceId = $instance['instance_id'];
        $excludedIds[] = $instanceId;
        $url = rtrim($instance['base_url'], '/') . $route['backendPath'];
        $started = microtime(true);
        $response = null;
        $proxyError = null;

        try {
            $response = $proxy->get(
                $url,
                $requestId,
                trim((string) getenv('BACKEND_GATEWAY_TOKEN'))
            );

            if (in_array($response['status'], [502, 503, 504], true)) {
                throw new RuntimeException('La instancia devolvió HTTP ' . $response['status']);
            }
        } catch (RuntimeException $error) {
            $proxyError = $error;
            $lastError = $error;
        } finally {
            $store->finish(
                $attemptId,
                $proxyError === null ? 'COMPLETED' : 'FAILED',
                $response['status'] ?? 0,
                (int) round((microtime(true) - $started) * 1000),
                $proxyError?->getMessage()
            );
        }

        if ($proxyError !== null) {
            $store->markDown($instanceId);
            continue;
        }

        header('X-Selected-Instance: ' . $instanceId);
        http_response_code($response['status']);
        echo $response['body'];
        exit;
    }

    error_log((string) $lastError);
    gatewayResponse(['error' => 'Ninguna instancia respondió correctamente.'], 502);
} catch (Throwable $error) {
    error_log((string) $error);
    gatewayResponse(['error' => 'El Gateway no pudo procesar la solicitud.'], 503);
}
