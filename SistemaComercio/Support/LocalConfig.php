<?php

declare(strict_types=1);

namespace SistemaComercio\Support;

use RuntimeException;

class LocalConfig
{
    public static function apply(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $settings = require $path;

        if (!is_array($settings)) {
            throw new RuntimeException('config.local.php debe devolver un arreglo.');
        }

        foreach ($settings as $name => $value) {
            if (
                !is_string($name)
                || !preg_match('/^[A-Z][A-Z0-9_]*$/', $name)
                || !is_scalar($value)
            ) {
                throw new RuntimeException('Hay un valor inválido en config.local.php.');
            }

            // Las variables existentes siguen teniendo prioridad para no romper montajes previos.
            if (getenv($name) === false) {
                putenv($name . '=' . (string) $value);
            }
        }
    }
}
