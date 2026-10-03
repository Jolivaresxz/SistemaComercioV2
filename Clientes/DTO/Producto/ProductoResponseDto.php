<?php

namespace SistemaComercio\Clientes\DTO\Producto;

use SistemaComercio\Clientes\Entidad\Producto\Producto;

class ProductoResponseDto
{
    public function __construct(
        private ?int $id,
        private string $nombre,
        private int $precio
    ) {}

    public static function desdeEntidad(Producto $producto): self
    {
        return new self(
            $producto->getId(),
            $producto->getNombre(),
            $producto->getPrecio()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'precio' => $this->precio
        ];
    }
}
