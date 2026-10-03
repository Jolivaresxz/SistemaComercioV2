<?php

namespace SistemaComercio\Entidad\Producto;

use InvalidArgumentException;

class Producto
{
    private ?int $id;
    private string $nombre;
    private int $precio;

    public function __construct(
        ?int $id,
        string $nombre,
        int $precio
    ) {
        $this->id = $id;
        $this->validarYAsignarDatos($nombre, $precio);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getPrecio(): int
    {
        return $this->precio;
    }

    public function actualizarDatos(string $nombre, int $precio): void
    {
        $this->validarYAsignarDatos($nombre, $precio);
    }

    public function asignarId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'El ID debe ser mayor a cero.'
            );
        }

        $this->id = $id;
    }

    private function validarYAsignarDatos(
        string $nombre,
        int $precio
    ): void {
        $nombre = trim($nombre);

        if ($nombre === '') {
            throw new InvalidArgumentException(
                'El nombre del producto es obligatorio.'
            );
        }

        if ($precio <= 0) {
            throw new InvalidArgumentException(
                'El precio debe ser mayor a cero.'
            );
        }

        $this->nombre = $nombre;
        $this->precio = $precio;
    }
}
