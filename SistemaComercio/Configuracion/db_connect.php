<?php

require_once dirname(__DIR__) . '/Support/autoload.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Este archivo se ejecuta EN EL PC elegido por el Gateway. El autoload carga
// config.local.php de ese PC, por lo que DB_* apunta a su propia base local.
// El Gateway no cambia este archivo ni necesita credenciales MySQL remotas.
$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$dbname = getenv('DB_NAME') ?: 'sistemacomercio';
$port = (int) (getenv('DB_PORT') ?: 3306);

try {
    $conn = new mysqli(
        $servername,
        $username,
        $password,
        $dbname,
        $port
    );
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    throw new RuntimeException(
        'No fue posible conectarse a la base de datos.',
        0,
        $exception
    );
}

return $conn;
