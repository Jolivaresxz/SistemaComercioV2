<?php

declare(strict_types=1);

namespace SistemaComercio\Gateway\Balancer;

class IpHashStrategy implements Strategy
{
    public function select(array $instances, string $clientIp, string $hashKey): array
    {
        usort(
            $instances,
            static fn (array $a, array $b): int =>
                strcmp($a['instance_id'], $b['instance_id'])
        );

        $number = (int) hexdec(substr(hash('sha256', $clientIp), 0, 8));
        return $instances[$number % count($instances)];
    }
}
