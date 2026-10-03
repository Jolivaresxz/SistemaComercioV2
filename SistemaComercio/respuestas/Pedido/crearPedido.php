<?php

use SistemaComercio\DTO\Pedido\PedidoRequestDto;

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['POST']);

$solicitud = PedidoRequestDto::desdeArray(obtenerDatosPeticion());
$respuesta = $pedidoController->crear($solicitud);

responderJson(
    [
        'exito' => true,
        'mensaje' => 'Pedido creado correctamente.',
        'datos' => $respuesta->toArray()
    ],
    201
);
