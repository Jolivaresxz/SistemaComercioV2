<?php

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['POST', 'DELETE']);

$datos = obtenerDatosPeticion();
$id = obtenerIdPeticion($datos);
$eliminado = $clienteController->eliminar($id);

if (!$eliminado) {
    responderJson(
        ['exito' => false, 'mensaje' => 'No existe un cliente con ese ID.'],
        404
    );
}

responderJson([
    'exito' => true,
    'mensaje' => 'Cliente eliminado correctamente.'
]);
