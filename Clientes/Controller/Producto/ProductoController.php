<?php

namespace SistemaComercio\Clientes\Controller\Producto;

use SistemaComercio\Clientes\DTO\Producto\ProductoRequestDto;
use SistemaComercio\Clientes\DTO\Producto\ProductoResponseDto;
use SistemaComercio\Clientes\Service\Producto\IProductoService;

class ProductoController
{
    public function __construct(
        private IProductoService $productoService
    ) {}

    public function crear(
        ProductoRequestDto $solicitud
    ): ProductoResponseDto {
        $producto = $this->productoService->crearProducto(
            $solicitud->getNombre(),
            $solicitud->getPrecio()
        );

        return ProductoResponseDto::desdeEntidad($producto);
    }

    public function modificar(
        int $id,
        ProductoRequestDto $solicitud
    ): ProductoResponseDto {
        $producto = $this->productoService->modificarProducto(
            $id,
            $solicitud->getNombre(),
            $solicitud->getPrecio()
        );

        return ProductoResponseDto::desdeEntidad($producto);
    }

    public function eliminar(int $id): bool
    {
        return $this->productoService->eliminarProducto($id);
    }

    public function obtenerPorId(int $id): ?ProductoResponseDto
    {
        $producto = $this->productoService->obtenerProductoPorId($id);

        return $producto === null
            ? null
            : ProductoResponseDto::desdeEntidad($producto);
    }

    public function obtenerTodos(): array
    {
        return array_map(
            static fn ($producto): ProductoResponseDto =>
                ProductoResponseDto::desdeEntidad($producto),
            $this->productoService->listarProductos()
        );
    }
}
