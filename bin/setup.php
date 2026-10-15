<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function pregunta(string $texto, ?string $predeterminado = null): string
{
    $ayuda = $predeterminado === null ? '' : " [{$predeterminado}]";
    echo $texto . $ayuda . ': ';
    $respuesta = fgets(STDIN);

    if ($respuesta === false) {
        throw new RuntimeException('Se cerró la entrada antes de terminar.');
    }

    $respuesta = trim($respuesta);
    return $respuesta === '' && $predeterminado !== null
        ? $predeterminado
        : $respuesta;
}

function ipValida(string $texto): string
{
    do {
        $ip = pregunta($texto);

        if (
            filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            && !str_starts_with($ip, '127.')
            && $ip !== '0.0.0.0'
        ) {
            return $ip;
        }

        echo "Escribe una IPv4 válida de la red local, por ejemplo 192.168.1.11.\n";
    } while (true);
}

function tokenCompartido(string $nombre): string
{
    do {
        $valor = pregunta($nombre . ' que entregó el Gateway');

        if ($valor !== '') {
            return $valor;
        }

        echo "Este token no puede quedar vacío.\n";
    } while (true);
}

$raizProyecto = dirname(__DIR__);
$archivo = $raizProyecto . '/config.local.php';
$anterior = [];

echo "=== Configuración sencilla de SistemaComercio ===\n";
echo "Inicia Apache y MySQL desde XAMPP y ten a mano la IPv4 de cada PC (ipconfig).\n\n";

if (is_file($archivo)) {
    $anterior = require $archivo;

    if (!is_array($anterior)) {
        throw new RuntimeException('La configuración anterior no es válida.');
    }

    echo "Los tokens anteriores se conservarán si vuelves a configurar este PC.\n";
    $reemplazar = strtolower(pregunta('Ya existe una configuración. ¿Reemplazarla? Escribe SI', 'NO'));

    if ($reemplazar !== 'si') {
        echo "No se cambió la configuración.\n";
        exit(0);
    }
}

try {
    do {
        $tipo = pregunta('¿Este PC será el Gateway (1) o un compañero/backend (2)?', '2');
    } while (!in_array($tipo, ['1', '2'], true));

    $esGateway = $tipo === '1';

    do {
        $id = pregunta('Nombre único de este PC (sin espacios)', 'pc-' . strtolower((string) gethostname()));

        if (preg_match('/^[a-zA-Z0-9._-]{1,80}$/', $id)) {
            break;
        }

        echo "Usa solo letras, números, puntos, guiones o guion bajo.\n";
    } while (true);

    $ipPropia = ipValida('IPv4 de ESTE PC');
    $ipGateway = $esGateway ? $ipPropia : ipValida('IPv4 del PC Gateway');
    $rutaWeb = pregunta('Ruta web del proyecto en Apache', '/sistemaComercio/sistema_comercio');
    $rutaWeb = '/' . trim($rutaWeb, '/');

    if (!preg_match('~^/[a-zA-Z0-9/_-]+$~', $rutaWeb)) {
        throw new RuntimeException('La ruta web contiene caracteres no admitidos.');
    }

    $nombreBase = pregunta('Nombre de la base MySQL', 'sistemacomercio');
    $usuarioBase = pregunta('Usuario MySQL local', 'root');
    $claveBase = pregunta('Clave MySQL local (Enter si está vacía)', '');

    if ($nombreBase === '' || $usuarioBase === '') {
        throw new RuntimeException('La base y el usuario MySQL son obligatorios.');
    }

    if ($esGateway) {
        $tokenRegistry = (string) ($anterior['REGISTRY_TOKEN'] ?? bin2hex(random_bytes(24)));
        $tokenBackend = (string) ($anterior['BACKEND_GATEWAY_TOKEN'] ?? bin2hex(random_bytes(24)));
        $carpetaDatos = dirname(PHP_BINARY, 2) . '/gateway-data';

        if (!is_dir($carpetaDatos) && !mkdir($carpetaDatos, 0770, true)) {
            throw new RuntimeException('No se pudo crear la carpeta de datos del Gateway.');
        }

        $rutaRegistryDb = $carpetaDatos . '/registry.sqlite';
    } else {
        if (
            !empty($anterior['REGISTRY_TOKEN'])
            && !empty($anterior['BACKEND_GATEWAY_TOKEN'])
            && strtolower(pregunta('¿Conservar los tokens anteriores? SI/no', 'SI')) === 'si'
        ) {
            $tokenRegistry = (string) $anterior['REGISTRY_TOKEN'];
            $tokenBackend = (string) $anterior['BACKEND_GATEWAY_TOKEN'];
        } else {
            $tokenRegistry = tokenCompartido('Token del Registry');
            $tokenBackend = tokenCompartido('Token del Backend');
        }
        $rutaRegistryDb = '';
    }

    $basePropia = 'http://' . $ipPropia . $rutaWeb;
    $baseGateway = 'http://' . $ipGateway . $rutaWeb;
    $configuracion = [
        'GATEWAY_ENABLED' => $esGateway ? '1' : '0',
        'INSTANCE_ID' => $id,
        'INSTANCE_BASE_URL' => $basePropia,
        'REGISTRY_URL' => $baseGateway . '/registry',
        'REGISTRY_TOKEN' => $tokenRegistry,
        'BACKEND_GATEWAY_TOKEN' => $tokenBackend,
        'REGISTRY_DB_PATH' => $rutaRegistryDb,
        'LOAD_BALANCER' => 'random',
        'DB_HOST' => 'localhost',
        'DB_PORT' => '3306',
        'DB_NAME' => $nombreBase,
        'DB_USER' => $usuarioBase,
        'DB_PASSWORD' => $claveBase,
    ];
    $contenido = "<?php\n\n// Configuración solo de este PC. No subir a Git.\nreturn "
        . var_export($configuracion, true) . ";\n";

    if (file_put_contents($archivo, $contenido, LOCK_EX) === false) {
        throw new RuntimeException('No se pudo guardar config.local.php.');
    }

    echo "\nListo: " . $archivo . "\n";

    if ($esGateway) {
        echo "\nComparte estos DOS tokens solo con tus compañeros:\n";
        echo "Token Registry: {$tokenRegistry}\n";
        echo "Token Backend: {$tokenBackend}\n";
    }

    echo "\n1. Comprueba: {$basePropia}/health.php\n";
    echo "2. Abre 2-registrar-servidor.bat y deja esa ventana abierta.\n";
    echo "3. Mira los equipos: {$baseGateway}/registry/instances\n";
    echo "4. Consulta por: {$baseGateway}/api/clientes\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Error: {$error->getMessage()}\n");
    exit(1);
}
