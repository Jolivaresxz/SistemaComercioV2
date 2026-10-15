<?php

declare(strict_types=1);

namespace SistemaComercio\Gateway;

class RouteMap
{
    public function resolve(string $path, string $clientIp): ?array
    {
        $path = trim($path, '/');

        $lists = [
            'clientes' => '/obtenertodoslosclientes',
            'productos' => '/obtenertodoslosproductos',
            'pedidos' => '/obtenertodoslospedidos',
            'pagos' => '/obtenertodoslospagos',
        ];

        if (isset($lists[$path])) {
            return [
                'route' => $path,
                'backendPath' => $lists[$path],
                'hashKey' => 'client:' . $clientIp,
            ];
        }

        if (preg_match('~^clientes/rut/([^/]+)$~', $path, $match)) {
            return [
                'route' => 'clientes.rut',
                'backendPath' => '/obtenerclienteporrut/' . rawurlencode($match[1]),
                'hashKey' => 'cliente-rut:' . $match[1],
            ];
        }

        $byId = [
            'clientes' => '/obtenerclienteporid/',
            'productos' => '/obtenerproductoporid/',
            'pedidos' => '/obtenerpedidoporid/',
            'pagos' => '/obtenerpagoporid/',
        ];

        if (preg_match('~^(clientes|productos|pedidos|pagos)/([1-9][0-9]*)$~', $path, $match)) {
            return [
                'route' => $match[1] . '.id',
                'backendPath' => $byId[$match[1]] . $match[2],
                'hashKey' => $match[1] . ':' . $match[2],
            ];
        }

        $byRelation = [
            'pedidos/cliente' => '/obtenerpedidosporcliente/',
            'pagos/pedido' => '/obtenerpagoporpedido/',
        ];

        if (preg_match('~^(pedidos/cliente|pagos/pedido)/([1-9][0-9]*)$~', $path, $match)) {
            return [
                'route' => str_replace('/', '.', $match[1]),
                'backendPath' => $byRelation[$match[1]] . $match[2],
                'hashKey' => $match[1] . ':' . $match[2],
            ];
        }

        return null;
    }
}
