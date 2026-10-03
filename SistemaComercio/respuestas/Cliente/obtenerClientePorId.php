<?php

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['GET']);

$id = obtenerIdPeticion();
$respuesta = $clienteController->obtenerPorId($id);

if ($respuesta === null) {
    responderJson(
        ['exito' => false, 'mensaje' => 'No existe un cliente con ese ID.'],
        404
    );
}

responderJson([
    'exito' => true,
    'datos' => $respuesta->toArray()
]);
