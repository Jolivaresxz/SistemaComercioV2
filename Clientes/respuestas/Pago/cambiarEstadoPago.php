<?php

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['POST', 'PATCH']);

$datos = obtenerDatosPeticion();
$respuesta = $pagoController->cambiarEstado(
    obtenerIdPeticion($datos),
    obtenerTextoPeticion($datos, 'estado')
);

responderJson([
    'exito' => true,
    'mensaje' => 'Estado del pago actualizado correctamente.',
    'datos' => $respuesta->toArray()
]);
