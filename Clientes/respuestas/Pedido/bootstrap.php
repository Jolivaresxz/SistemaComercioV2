<?php

use SistemaComercio\Clientes\Controller\Pedido\PedidoController;
use SistemaComercio\Clientes\Repository\Cliente\ClienteRepositoryMySql;
use SistemaComercio\Clientes\Repository\Pedido\PedidoRepositoryMySql;
use SistemaComercio\Clientes\Repository\Producto\ProductoRepositoryMySql;
use SistemaComercio\Clientes\Service\Pedido\PedidoService;

require_once dirname(__DIR__) . '/bootstrap.php';

$clienteRepository = new ClienteRepositoryMySql($conexion);
$productoRepository = new ProductoRepositoryMySql($conexion);
$pedidoRepository = new PedidoRepositoryMySql($conexion);
$pedidoService = new PedidoService(
    $pedidoRepository,
    $clienteRepository,
    $productoRepository
);
$pedidoController = new PedidoController($pedidoService);
