<?php

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['GET']);

$rut = isset($_GET['rut']) && is_string($_GET['rut'])
    ? trim($_GET['rut'])
    : '';

if ($rut === '') {
    throw new InvalidArgumentException('El RUT es obligatorio.');
}

$respuesta = $clienteController->obtenerPorRut($rut);

if ($respuesta === null) {
    responderJson(
        ['exito' => false, 'mensaje' => 'No existe un cliente con ese RUT.'],
        404
    );
}

responderJson([
    'exito' => true,
    'datos' => $respuesta->toArray()
]);
