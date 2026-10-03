<?php

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['GET']);

$pedidoId = obtenerIdPeticion([], 'pedidoId');
$respuesta = $pagoController->obtenerPorPedido($pedidoId);

if ($respuesta === null) {
    responderJson(
        ['exito' => false, 'mensaje' => 'No existe un pago para ese pedido.'],
        404
    );
}

responderJson([
    'exito' => true,
    'datos' => $respuesta->toArray()
]);
