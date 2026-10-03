<?php

$raizProyecto = dirname(__DIR__, 2);
$autoloadComposer = $raizProyecto . '/vendor/autoload.php';

if (is_file($autoloadComposer)) {
    require_once $autoloadComposer;
} else {
    spl_autoload_register(
        static function (string $clase) use ($raizProyecto): void {
            $prefijo = 'SistemaComercio\\';

            if (!str_starts_with($clase, $prefijo)) {
                return;
            }

            $nombreRelativo = substr($clase, strlen($prefijo));
            $archivo = $raizProyecto
                . '/SistemaComercio/'
                . str_replace('\\', '/', $nombreRelativo)
                . '.php';

            if (is_file($archivo)) {
                require_once $archivo;
            }
        }
    );
}

function responderJson(array $contenido, int $estado = 200): void
{
    http_response_code($estado);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        $contenido,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function validarMetodo(array $metodosPermitidos): void
{
    $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if (!in_array($metodo, $metodosPermitidos, true)) {
        header('Allow: ' . implode(', ', $metodosPermitidos));
        responderJson(
            ['exito' => false, 'mensaje' => 'Método HTTP no permitido.'],
            405
        );
    }
}

function obtenerDatosPeticion(): array
{
    if ($_POST !== []) {
        return $_POST;
    }

    $contenido = file_get_contents('php://input');

    if ($contenido === false || trim($contenido) === '') {
        return [];
    }

    $tipoContenido = $_SERVER['CONTENT_TYPE'] ?? '';

    if (str_contains(strtolower($tipoContenido), 'application/json')) {
        $datos = json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($datos)) {
            throw new InvalidArgumentException(
                'El contenido JSON debe ser un objeto.'
            );
        }

        return $datos;
    }

    parse_str($contenido, $datos);

    return is_array($datos) ? $datos : [];
}

function obtenerIdPeticion(array $datos = [], string $campo = 'id'): int
{
    $valor = $_GET[$campo] ?? $datos[$campo] ?? null;
    $id = filter_var(
        $valor,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        throw new InvalidArgumentException(
            "El campo {$campo} debe ser un entero mayor a cero."
        );
    }

    return $id;
}

function obtenerTextoPeticion(array $datos, string $campo): string
{
    $valor = $datos[$campo] ?? null;

    if (!is_string($valor) || trim($valor) === '') {
        throw new InvalidArgumentException(
            "El campo {$campo} es obligatorio."
        );
    }

    return trim($valor);
}

function listaDtoAArray(array $elementos): array
{
    return array_map(
        static fn ($elemento): array => $elemento->toArray(),
        $elementos
    );
}

function manejarError(Throwable $error): void
{
    if ($error instanceof JsonException) {
        responderJson(
            ['exito' => false, 'mensaje' => 'El JSON recibido no es válido.'],
            400
        );
    }

    if ($error instanceof InvalidArgumentException) {
        responderJson(
            ['exito' => false, 'mensaje' => $error->getMessage()],
            400
        );
    }

    if ($error instanceof mysqli_sql_exception) {
        if ($error->getCode() === 1062) {
            responderJson(
                ['exito' => false, 'mensaje' => 'El registro ya existe.'],
                409
            );
        }

        if (in_array($error->getCode(), [1451, 1452], true)) {
            responderJson(
                [
                    'exito' => false,
                    'mensaje' => 'La operación entra en conflicto con registros relacionados.'
                ],
                409
            );
        }
    }

    if ($error instanceof RuntimeException) {
        $mensaje = $error->getMessage();

        if (
            stripos($mensaje, 'Ya existe') !== false
            || stripos($mensaje, 'Solo se pueden') !== false
        ) {
            responderJson(
                ['exito' => false, 'mensaje' => $mensaje],
                409
            );
        }

        if (
            stripos($mensaje, 'No existe') !== false
            || stripos($mensaje, 'inexistente') !== false
        ) {
            responderJson(
                ['exito' => false, 'mensaje' => $mensaje],
                404
            );
        }
    }

    error_log((string) $error);
    responderJson(
        [
            'exito' => false,
            'mensaje' => 'Ocurrió un error interno al procesar la solicitud.'
        ],
        500
    );
}

set_exception_handler('manejarError');

$conexion = require $raizProyecto
    . '/SistemaComercio/Configuracion/db_connect.php';
