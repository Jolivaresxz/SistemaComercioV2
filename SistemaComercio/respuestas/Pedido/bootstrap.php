<?php

use SistemaComercio\Controller\Pedido\PedidoController;
use SistemaComercio\Repository\Cliente\ClienteRepositoryMySql;
use SistemaComercio\Repository\Pedido\PedidoRepositoryMySql;
use SistemaComercio\Repository\Producto\ProductoRepositoryMySql;
use SistemaComercio\Service\Pedido\PedidoService;

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
