<?php

namespace SistemaComercio\DTO\Producto;

use InvalidArgumentException;

class ProductoRequestDto
{
    public function __construct(
        private string $nombre,
        private int $precio
    ) {}

    public static function desdeArray(array $datos): self
    {
        $nombre = $datos['nombre'] ?? null;
        $precio = filter_var(
            $datos['precio'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if (!is_string($nombre)) {
            throw new InvalidArgumentException(
                'El campo nombre es obligatorio.'
            );
        }

        if ($precio === false) {
            throw new InvalidArgumentException(
                'El precio debe ser un número entero mayor a cero.'
            );
        }

        return new self(trim($nombre), $precio);
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getPrecio(): int
    {
        return $this->precio;
    }
}
