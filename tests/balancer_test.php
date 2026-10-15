<?php

declare(strict_types=1);

use SistemaComercio\Gateway\Balancer\StrategyFactory;

require_once dirname(__DIR__) . '/SistemaComercio/Support/autoload.php';

$instances = [
    ['instance_id' => 'pc-a', 'active_requests' => 3],
    ['instance_id' => 'pc-b', 'active_requests' => 0],
    ['instance_id' => 'pc-c', 'active_requests' => 1],
];

if (
    StrategyFactory::make('least_connections')
        ->select($instances, '192.168.1.10', 'cliente:1')['instance_id'] !== 'pc-b'
) {
    throw new RuntimeException('Least Connections no eligió la menor carga.');
}

$ipHash = StrategyFactory::make('ip_hash');
$first = $ipHash->select($instances, '192.168.1.10', 'cliente:1');
$second = $ipHash->select(array_reverse($instances), '192.168.1.10', 'cliente:99');

if ($first['instance_id'] !== $second['instance_id']) {
    throw new RuntimeException('IP Hash cambió de instancia para la misma IP.');
}

$consistent = StrategyFactory::make('consistent_hashing');
$withoutB = array_values(array_filter(
    $instances,
    static fn (array $instance): bool => $instance['instance_id'] !== 'pc-b'
));
$moved = 0;

for ($id = 1; $id <= 100; $id++) {
    $key = 'cliente:' . $id;
    $before = $consistent->select($instances, '192.168.1.10', $key);
    $after = $consistent->select($withoutB, '192.168.1.10', $key);

    if ($before['instance_id'] !== 'pc-b' && $before['instance_id'] !== $after['instance_id']) {
        throw new RuntimeException('Consistent Hashing movió una clave que no pertenecía al nodo retirado.');
    }

    if ($before['instance_id'] !== $after['instance_id']) {
        $moved++;
    }
}

if ($moved === 0 || $moved === 100) {
    throw new RuntimeException('Consistent Hashing no distribuyó las claves.');
}

$random = StrategyFactory::make('random')->select($instances, '192.168.1.10', 'cliente:1');

if (!in_array($random['instance_id'], ['pc-a', 'pc-b', 'pc-c'], true)) {
    throw new RuntimeException('Random seleccionó una instancia desconocida.');
}

echo "BALANCER_TEST_OK\n";
