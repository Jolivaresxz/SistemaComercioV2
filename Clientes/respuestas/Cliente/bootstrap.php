<?php

use SistemaComercio\Clientes\Controller\Cliente\ClienteController;
use SistemaComercio\Clientes\Repository\Cliente\ClienteRepositoryMySql;
use SistemaComercio\Clientes\Service\Cliente\ClienteService;

require_once dirname(__DIR__) . '/bootstrap.php';

$clienteRepository = new ClienteRepositoryMySql($conexion);
$clienteService = new ClienteService($clienteRepository);
$clienteController = new ClienteController($clienteService);
