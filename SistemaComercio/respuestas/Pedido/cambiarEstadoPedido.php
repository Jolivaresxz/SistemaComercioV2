<?php

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['POST', 'PATCH']);

$datos = obtenerDatosPeticion();
$respuesta = $pedidoController->cambiarEstado(
    obtenerIdPeticion($datos),
    obtenerTextoPeticion($datos, 'estado')
);

responderJson([
    'exito' => true,
    'mensaje' => 'Estado del pedido actualizado correctamente.',
    'datos' => $respuesta->toArray()
]);
