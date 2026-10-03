<?php

use SistemaComercio\Controller\Cliente\ClienteController;
use SistemaComercio\Repository\Cliente\ClienteRepositoryMySql;
use SistemaComercio\Service\Cliente\ClienteService;

require_once dirname(__DIR__) . '/bootstrap.php';

$clienteRepository = new ClienteRepositoryMySql($conexion);
$clienteService = new ClienteService($clienteRepository);
$clienteController = new ClienteController($clienteService);
