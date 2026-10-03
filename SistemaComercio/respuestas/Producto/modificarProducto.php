<?php

use SistemaComercio\DTO\Producto\ProductoRequestDto;

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['POST', 'PUT']);

$datos = obtenerDatosPeticion();
$solicitud = ProductoRequestDto::desdeArray($datos);
$respuesta = $productoController->modificar(
    obtenerIdPeticion($datos),
    $solicitud
);

responderJson([
    'exito' => true,
    'mensaje' => 'Producto modificado correctamente.',
    'datos' => $respuesta->toArray()
]);
