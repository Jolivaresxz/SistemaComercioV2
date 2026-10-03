<?php

use SistemaComercio\Clientes\Controller\Producto\ProductoController;
use SistemaComercio\Clientes\Repository\Producto\ProductoRepositoryMySql;
use SistemaComercio\Clientes\Service\Producto\ProductoService;

require_once dirname(__DIR__) . '/bootstrap.php';

$productoRepository = new ProductoRepositoryMySql($conexion);
$productoService = new ProductoService($productoRepository);
$productoController = new ProductoController($productoService);
