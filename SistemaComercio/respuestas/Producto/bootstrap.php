<?php

use SistemaComercio\Controller\Producto\ProductoController;
use SistemaComercio\Repository\Producto\ProductoRepositoryMySql;
use SistemaComercio\Service\Producto\ProductoService;

require_once dirname(__DIR__) . '/bootstrap.php';

$productoRepository = new ProductoRepositoryMySql($conexion);
$productoService = new ProductoService($productoRepository);
$productoController = new ProductoController($productoService);
