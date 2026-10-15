<?php

declare(strict_types=1);

namespace SistemaComercio\Gateway\Balancer;

interface Strategy
{
    public function select(array $instances, string $clientIp, string $hashKey): array;
}
