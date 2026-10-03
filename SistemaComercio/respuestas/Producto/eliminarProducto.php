<?php

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['POST', 'DELETE']);

$datos = obtenerDatosPeticion();
$eliminado = $productoController->eliminar(
    obtenerIdPeticion($datos)
);

if (!$eliminado) {
    responderJson(
        ['exito' => false, 'mensaje' => 'No existe un producto con ese ID.'],
        404
    );
}

responderJson([
    'exito' => true,
    'mensaje' => 'Producto eliminado correctamente.'
]);
