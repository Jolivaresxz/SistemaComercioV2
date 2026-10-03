<?php

use SistemaComercio\DTO\Cliente\ClienteRequestDto;

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['POST']);

$solicitud = ClienteRequestDto::desdeArray(obtenerDatosPeticion());
$respuesta = $clienteController->crear($solicitud);

responderJson(
    [
        'exito' => true,
        'mensaje' => 'Cliente creado correctamente.',
        'datos' => $respuesta->toArray()
    ],
    201
);
