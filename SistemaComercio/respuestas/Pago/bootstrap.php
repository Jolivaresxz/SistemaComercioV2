<?php

use SistemaComercio\Controller\Pago\PagoController;
use SistemaComercio\Repository\Pago\PagoRepositoryMySql;
use SistemaComercio\Repository\Pedido\PedidoRepositoryMySql;
use SistemaComercio\Service\Pago\PagoService;

require_once dirname(__DIR__) . '/bootstrap.php';

$pedidoRepository = new PedidoRepositoryMySql($conexion);
$pagoRepository = new PagoRepositoryMySql($conexion);
$pagoService = new PagoService($pagoRepository, $pedidoRepository);
$pagoController = new PagoController($pagoService);
