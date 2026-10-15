<?php

declare(strict_types=1);

namespace SistemaComercio\Gateway\Balancer;

class LeastConnectionsStrategy implements Strategy
{
    public function select(array $instances, string $clientIp, string $hashKey): array
    {
        usort(
            $instances,
            static function (array $a, array $b): int {
                $comparison = (int) $a['active_requests'] <=> (int) $b['active_requests'];

                return $comparison !== 0
                    ? $comparison
                    : strcmp($a['instance_id'], $b['instance_id']);
            }
        );

        return $instances[0];
    }
}
