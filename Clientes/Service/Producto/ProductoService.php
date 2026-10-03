<?php

namespace SistemaComercio\Clientes\Service\Producto;

use RuntimeException;
use SistemaComercio\Clientes\Entidad\Producto\Producto;
use SistemaComercio\Clientes\Repository\Producto\IProductoRepository;

class ProductoService implements IProductoService
{
    public function __construct(
        private IProductoRepository $productoRepository
    ) {}

    public function crearProducto(string $nombre, int $precio): Producto
    {
        return $this->productoRepository->guardar(
            new Producto(null, $nombre, $precio)
        );
    }

    public function modificarProducto(
        int $id,
        string $nombre,
        int $precio
    ): Producto {
        $producto = $this->productoRepository->obtenerPorId($id);

        if ($producto === null) {
            throw new RuntimeException(
                'No existe un producto con ese ID.'
            );
        }

        $producto->actualizarDatos($nombre, $precio);
        $this->productoRepository->actualizar($producto);

        return $producto;
    }

    public function eliminarProducto(int $id): bool
    {
        return $this->productoRepository->eliminar($id);
    }

    public function obtenerProductoPorId(int $id): ?Producto
    {
        return $this->productoRepository->obtenerPorId($id);
    }

    public function listarProductos(): array
    {
        return $this->productoRepository->obtenerTodos();
    }
}
