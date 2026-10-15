<?php

declare(strict_types=1);

namespace SistemaComercio\Gateway\Balancer;

class RandomStrategy implements Strategy
{
    public function select(array $instances, string $clientIp, string $hashKey): array
    {
        return $instances[random_int(0, count($instances) - 1)];
    }
}
