<?php

declare(strict_types=1);

use SistemaComercio\Registry\HealthClient;
use SistemaComercio\Registry\RegistryStore;

require_once __DIR__ . '/SistemaComercio/Support/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function registryResponse(array $body, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (getenv('GATEWAY_ENABLED') !== '1') {
    registryResponse(['error' => 'Registry deshabilitado en este computador.'], 503);
}

try {
    $store = new RegistryStore(trim((string) getenv('REGISTRY_DB_PATH')));
    $action = (string) ($_GET['action'] ?? '');
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if (in_array($action, ['discover', 'instances'], true)) {
        if ($method !== 'GET') {
            registryResponse(['error' => 'Método no permitido.'], 405);
        }

        if ($action === 'discover') {
            registryResponse([
                'service' => 'sistema-comercio',
                'instances' => $store->discover(),
            ]);
        }

        registryResponse([
            'expectedDatasetHash' => $store->expectedHash(),
            'instances' => $store->listInstances(),
        ]);
    }

    if (!in_array($action, ['register', 'heartbeat', 'unregister'], true)) {
        registryResponse(['error' => 'Acción desconocida.'], 404);
    }

    if ($method !== 'POST') {
        registryResponse(['error' => 'Método no permitido.'], 405);
    }

    $token = trim((string) getenv('REGISTRY_TOKEN'));
    $received = (string) ($_SERVER['HTTP_X_REGISTRY_TOKEN'] ?? '');

    if ($token === '' || !hash_equals($token, $received)) {
        registryResponse(['error' => 'Token de registro inválido.'], 401);
    }

    $input = json_decode((string) file_get_contents('php://input'), true);

    if (!is_array($input)) {
        registryResponse(['error' => 'Se esperaba un objeto JSON.'], 400);
    }

    $instanceId = trim((string) ($input['instanceId'] ?? ''));

    if (!preg_match('/^[a-zA-Z0-9._-]{1,80}$/', $instanceId)) {
        registryResponse(['error' => 'instanceId inválido.'], 400);
    }

    if ($action === 'unregister') {
        registryResponse(
            ['removed' => $store->unregister($instanceId)],
            $store->getInstance($instanceId) === null ? 404 : 200
        );
    }

    if ($action === 'register') {
        $baseUrl = rtrim(trim((string) ($input['baseUrl'] ?? '')), '/');
        $parts = parse_url($baseUrl);

        if (
            !is_array($parts)
            || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
        ) {
            registryResponse(['error' => 'baseUrl inválida.'], 400);
        }
    } else {
        $registered = $store->getInstance($instanceId);

        if ($registered === null) {
            registryResponse(['error' => 'Instancia no registrada.'], 404);
        }

        $baseUrl = $registered['base_url'];
    }

    try {
        $health = (new HealthClient())->probe($baseUrl);
    } catch (RuntimeException $error) {
        $store->markDown($instanceId);
        registryResponse(['error' => $error->getMessage()], 503);
    }

    if ($health['instanceId'] !== $instanceId) {
        registryResponse(['error' => 'El ID no coincide con la instancia.'], 409);
    }

    $result = $store->register($instanceId, $baseUrl, $health['datasetHash']);
    registryResponse($result, $result['status'] === 'UP' ? 200 : 409);
} catch (Throwable $error) {
    error_log((string) $error);
    registryResponse(['error' => 'El Registry no pudo procesar la solicitud.'], 503);
}
