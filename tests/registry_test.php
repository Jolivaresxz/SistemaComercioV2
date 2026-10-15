<?php

declare(strict_types=1);

use SistemaComercio\Registry\RegistryStore;

require_once dirname(__DIR__) . '/SistemaComercio/Support/autoload.php';

putenv('EXPECTED_DATASET_HASH');
$path = sys_get_temp_dir() . '/registry-test-' . bin2hex(random_bytes(8)) . '.json';

try {
    $store = new RegistryStore($path);
    $sameHash = str_repeat('a', 64);
    $otherHash = str_repeat('b', 64);

    $first = $store->register('pc-a', 'http://127.0.0.1:8001', $sameHash);
    $store->register('pc-b', 'http://127.0.0.1:8002', $sameHash);
    $different = $store->register('pc-c', 'http://127.0.0.1:8003', $otherHash);

    if ($first['status'] !== 'UP' || $different['status'] !== 'MISMATCH') {
        throw new RuntimeException('El Registry no clasificó las copias de datos.');
    }

    $saved = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

    if (
        $saved['expectedDatasetHash'] !== $sameHash
        || $saved['instances']['pc-a']['base_url'] !== 'http://127.0.0.1:8001'
    ) {
        throw new RuntimeException('El archivo JSON no guardó las instancias.');
    }

    if (count($store->discover()) !== 2) {
        throw new RuntimeException('Discovery debe devolver solo instancias compatibles.');
    }

    $reservedA = $store->reserve(
        'request-1', '192.168.1.10', 'clientes',
        'least_connections', 'client:192.168.1.10'
    );
    $reservedB = $store->reserve(
        'request-2', '192.168.1.11', 'clientes',
        'least_connections', 'client:192.168.1.11'
    );

    if ($reservedA['instance_id'] !== 'pc-a' || $reservedB['instance_id'] !== 'pc-b') {
        throw new RuntimeException('Least Connections no usó el contador activo.');
    }

    $store->finish('request-1', 'COMPLETED', 200, 15);
    $store->finish('request-2', 'FAILED', 0, 20, 'Prueba de error.');

    if (
        (int) $store->getInstance('pc-a')['active_requests'] !== 0
        || (int) $store->getInstance('pc-b')['active_requests'] !== 0
        || count($store->recentRequests()) !== 2
    ) {
        throw new RuntimeException('Las peticiones activas no se liberaron.');
    }

    $store->markDown('pc-b');

    if (count($store->discover()) !== 1) {
        throw new RuntimeException('Discovery incluyó una instancia caída.');
    }

    $file = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $file['instances']['pc-a']['last_seen_at'] = 1;
    file_put_contents($path, json_encode($file, JSON_THROW_ON_ERROR));

    if ($store->discover() !== []) {
        throw new RuntimeException('Discovery incluyó una instancia vencida.');
    }

    file_put_contents($path, '{JSON incompleto');

    try {
        new RegistryStore($path);
        throw new RuntimeException('El Registry aceptó un JSON inválido.');
    } catch (JsonException $expectedError) {
        // Un archivo inválido no se debe reemplazar silenciosamente.
    }

    if (file_get_contents($path) !== '{JSON incompleto') {
        throw new RuntimeException('El Registry sobrescribió un archivo inválido.');
    }

    echo "REGISTRY_TEST_OK\n";
} finally {
    unset($store);

    if (is_file($path)) {
        unlink($path);
    }
}
