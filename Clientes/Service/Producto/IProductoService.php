<?php

namespace SistemaComercio\Clientes\Service\Producto;

use SistemaComercio\Clientes\Entidad\Producto\Producto;

interface IProductoService
{
    public function crearProducto(string $nombre, int $precio): Producto;
    public function modificarProducto(
        int $id,
        string $nombre,
        int $precio
    ): Producto;
    public function eliminarProducto(int $id): bool;
    public function obtenerProductoPorId(int $id): ?Producto;
    public function listarProductos(): array;
}
