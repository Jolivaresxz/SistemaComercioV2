<?php

use SistemaComercio\Clientes\DTO\Producto\ProductoRequestDto;

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['POST']);

$solicitud = ProductoRequestDto::desdeArray(obtenerDatosPeticion());
$respuesta = $productoController->crear($solicitud);

responderJson(
    [
        'exito' => true,
        'mensaje' => 'Producto creado correctamente.',
        'datos' => $respuesta->toArray()
    ],
    201
);
