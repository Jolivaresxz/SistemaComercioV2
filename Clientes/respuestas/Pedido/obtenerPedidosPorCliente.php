<?php

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['GET']);

$clienteId = obtenerIdPeticion([], 'clienteId');

responderJson([
    'exito' => true,
    'datos' => listaDtoAArray(
        $pedidoController->obtenerPorCliente($clienteId)
    )
]);
