<?php

declare(strict_types=1);

use SistemaComercio\Registry\RegistryStore;

require_once dirname(__DIR__) . '/SistemaComercio/Support/autoload.php';

putenv('EXPECTED_DATASET_HASH');
$path = sys_get_temp_dir() . '/registry-test-' . bin2hex(random_bytes(8)) . '.sqlite';

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

    $db = new PDO('sqlite:' . $path);
    $db->exec("UPDATE instances SET last_seen_at = 1 WHERE instance_id = 'pc-a'");

    if ($store->discover() !== []) {
        throw new RuntimeException('Discovery incluyó una instancia vencida.');
    }

    echo "REGISTRY_TEST_OK\n";
} finally {
    unset($store, $db);

    foreach ([$path, $path . '-wal', $path . '-shm'] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}
