<?php

namespace SistemaComercio\Repository\Producto;

use SistemaComercio\Entidad\Producto\Producto;

interface IProductoRepository
{
    public function guardar(Producto $producto): Producto;
    public function actualizar(Producto $producto): void;
    public function obtenerPorId(int $id): ?Producto;
    public function obtenerTodos(): array;
    public function eliminar(int $id): bool;
}
