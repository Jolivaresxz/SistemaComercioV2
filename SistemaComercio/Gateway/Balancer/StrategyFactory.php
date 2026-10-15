<?php

declare(strict_types=1);

namespace SistemaComercio\Gateway\Balancer;

use InvalidArgumentException;

class StrategyFactory
{
    public static function make(string $name): Strategy
    {
        return match ($name) {
            'random' => new RandomStrategy(),
            'ip_hash' => new IpHashStrategy(),
            'least_connections' => new LeastConnectionsStrategy(),
            'consistent_hashing' => new ConsistentHashStrategy(),
            default => throw new InvalidArgumentException('Algoritmo desconocido.'),
        };
    }
}
