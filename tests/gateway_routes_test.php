<?php

declare(strict_types=1);

use SistemaComercio\Gateway\RouteMap;

require_once dirname(__DIR__) . '/SistemaComercio/Support/autoload.php';

$routes = new RouteMap();
$cases = [
    'clientes' => '/obtenertodoslosclientes',
    'clientes/7' => '/obtenerclienteporid/7',
    'clientes/rut/12345678-9' => '/obtenerclienteporrut/12345678-9',
    'productos' => '/obtenertodoslosproductos',
    'productos/2' => '/obtenerproductoporid/2',
    'pedidos' => '/obtenertodoslospedidos',
    'pedidos/4' => '/obtenerpedidoporid/4',
    'pedidos/cliente/7' => '/obtenerpedidosporcliente/7',
    'pagos' => '/obtenertodoslospagos',
    'pagos/3' => '/obtenerpagoporid/3',
    'pagos/pedido/4' => '/obtenerpagoporpedido/4',
];

foreach ($cases as $path => $expected) {
    $route = $routes->resolve($path, '192.168.1.10');

    if ($route === null || $route['backendPath'] !== $expected) {
        throw new RuntimeException("Ruta incorrecta: {$path}");
    }
}

foreach (['clientes/0', 'clientes/../pagos', 'clientes/crear', 'pagos/pedido/0'] as $path) {
    if ($routes->resolve($path, '192.168.1.10') !== null) {
        throw new RuntimeException("Se aceptó una ruta no permitida: {$path}");
    }
}

echo "GATEWAY_ROUTES_TEST_OK\n";
