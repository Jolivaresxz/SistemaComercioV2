<?php

declare(strict_types=1);

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
