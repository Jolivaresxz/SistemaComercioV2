<?php

require_once __DIR__ . '/bootstrap.php';

validarMetodo(['GET']);

responderJson([
    'exito' => true,
    'datos' => listaDtoAArray($pedidoController->obtenerTodos())
]);
