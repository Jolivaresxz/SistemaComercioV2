<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$instanceId = trim((string) (getenv('INSTANCE_ID') ?: gethostname() ?: 'local'));

try {
    /** @var mysqli $conexion */
    $conexion = require __DIR__ . '/SistemaComercio/Configuracion/db_connect.php';
    $hash = hash_init('sha256');

    foreach (['cliente', 'producto', 'pedido', 'pago'] as $tabla) {
        hash_update($hash, $tabla . "\n");
        $resultado = $conexion->query("SELECT * FROM `{$tabla}` ORDER BY `Id`");

        while ($fila = $resultado->fetch_assoc()) {
            hash_update(
                $hash,
                json_encode($fila, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
                . "\n"
            );
        }

        $resultado->free();
    }

    $conexion->close();

    echo json_encode(
        [
            'status' => 'ok',
            'service' => 'sistema-comercio',
            'instanceId' => $instanceId,
            'datasetHash' => hash_final($hash),
        ],
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
    );
} catch (Throwable $error) {
    error_log((string) $error);
    http_response_code(503);
    echo json_encode(
        [
            'status' => 'error',
            'service' => 'sistema-comercio',
            'instanceId' => $instanceId,
            'message' => 'La instancia o su base de datos no está disponible.',
        ],
        JSON_UNESCAPED_UNICODE
    );
}
