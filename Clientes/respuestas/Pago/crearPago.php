<?php

use SistemaComercio\Clientes\DTO\Pago\PagoRequestDto;

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['POST']);

$solicitud = PagoRequestDto::desdeArray(obtenerDatosPeticion());
$respuesta = $pagoController->crear($solicitud);

responderJson(
    [
        'exito' => true,
        'mensaje' => 'Pago creado correctamente.',
        'datos' => $respuesta->toArray()
    ],
    201
);
