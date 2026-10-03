<?php

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['GET']);

$respuesta = $productoController->obtenerPorId(obtenerIdPeticion());

if ($respuesta === null) {
    responderJson(
        ['exito' => false, 'mensaje' => 'No existe un producto con ese ID.'],
        404
    );
}

responderJson([
    'exito' => true,
    'datos' => $respuesta->toArray()
]);
