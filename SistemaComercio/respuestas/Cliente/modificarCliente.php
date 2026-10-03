<?php

use SistemaComercio\DTO\Cliente\ClienteRequestDto;

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['POST', 'PUT']);

$datos = obtenerDatosPeticion();
$solicitud = ClienteRequestDto::desdeArray($datos);
$respuesta = $clienteController->modificar(
    obtenerIdPeticion($datos),
    $solicitud
);

responderJson([
    'exito' => true,
    'mensaje' => 'Cliente modificado correctamente.',
    'datos' => $respuesta->toArray()
]);
