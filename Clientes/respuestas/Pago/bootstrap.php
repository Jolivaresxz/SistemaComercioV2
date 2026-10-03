<?php

use SistemaComercio\Clientes\Controller\Pago\PagoController;
use SistemaComercio\Clientes\Repository\Pago\PagoRepositoryMySql;
use SistemaComercio\Clientes\Repository\Pedido\PedidoRepositoryMySql;
use SistemaComercio\Clientes\Service\Pago\PagoService;

require_once dirname(__DIR__) . '/bootstrap.php';

$pedidoRepository = new PedidoRepositoryMySql($conexion);
$pagoRepository = new PagoRepositoryMySql($conexion);
$pagoService = new PagoService($pagoRepository, $pedidoRepository);
$pagoController = new PagoController($pagoService);
