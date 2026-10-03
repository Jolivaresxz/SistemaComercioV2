<?php

use SistemaComercio\DTO\Pedido\PedidoRequestDto;

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['POST', 'PUT']);

$datos = obtenerDatosPeticion();
$solicitud = PedidoRequestDto::desdeArray($datos);
$respuesta = $pedidoController->modificar(
    obtenerIdPeticion($datos),
    $solicitud
);

responderJson([
    'exito' => true,
    'mensaje' => 'Pedido modificado correctamente.',
    'datos' => $respuesta->toArray()
]);
