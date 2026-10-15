<?php

declare(strict_types=1);

namespace SistemaComercio\Gateway\Balancer;

class ConsistentHashStrategy implements Strategy
{
    private const VIRTUAL_NODES = 64;

    public function select(array $instances, string $clientIp, string $hashKey): array
    {
        $ring = [];

        foreach ($instances as $instance) {
            for ($index = 0; $index < self::VIRTUAL_NODES; $index++) {
                $position = hash('sha256', $instance['instance_id'] . '#' . $index);
                $ring[$position] = $instance;
            }
        }

        ksort($ring, SORT_STRING);
        $keyPosition = hash('sha256', $hashKey);

        foreach ($ring as $position => $instance) {
            if (strcmp($position, $keyPosition) >= 0) {
                return $instance;
            }
        }

        return reset($ring);
    }
}
