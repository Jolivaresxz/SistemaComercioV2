<?php

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['GET']);

$respuestas = $clienteController->obtenerTodos();
$clientes = array_map(
    static fn ($respuesta): array => $respuesta->toArray(),
    $respuestas
);

responderJson([
    'exito' => true,
    'datos' => $clientes
]);
