<?php

namespace SistemaComercio\Service\Pedido;

use RuntimeException;
use SistemaComercio\Entidad\Pedido\Pedido;
use SistemaComercio\Repository\Cliente\IClienteRepository;
use SistemaComercio\Repository\Pedido\IPedidoRepository;
use SistemaComercio\Repository\Producto\IProductoRepository;

class PedidoService implements IPedidoService
{
    public function __construct(
        private IPedidoRepository $pedidoRepository,
        private IClienteRepository $clienteRepository,
        private IProductoRepository $productoRepository
    ) {}

    public function crearPedido(
        int $clienteId,
        int $productoId,
        int $cantidad
    ): Pedido {
        $total = $this->calcularTotal(
            $clienteId,
            $productoId,
            $cantidad
        );
        $pedido = new Pedido(
            null,
            $clienteId,
            $productoId,
            $cantidad,
            $total
        );

        return $this->pedidoRepository->guardar($pedido);
    }

    public function modificarPedido(
        int $id,
        int $clienteId,
        int $productoId,
        int $cantidad
    ): Pedido {
        $pedido = $this->obtenerPedidoExistente($id);

        if ($pedido->getEstado() !== Pedido::ESTADO_PENDIENTE) {
            throw new RuntimeException(
                'Solo se pueden modificar pedidos pendientes.'
            );
        }

        $total = $this->calcularTotal(
            $clienteId,
            $productoId,
            $cantidad
        );
        $pedido->actualizarDatos(
            $clienteId,
            $productoId,
            $cantidad,
            $total
        );
        $this->pedidoRepository->actualizar($pedido);

        return $pedido;
    }

    public function cambiarEstado(int $id, string $estado): Pedido
    {
        $pedido = $this->obtenerPedidoExistente($id);
        $pedido->cambiarEstado($estado);
        $this->pedidoRepository->actualizar($pedido);

        return $pedido;
    }

    public function obtenerPedidoPorId(int $id): ?Pedido
    {
        return $this->pedidoRepository->obtenerPorId($id);
    }

    public function obtenerPedidosPorCliente(int $clienteId): array
    {
        return $this->pedidoRepository->obtenerPorClienteId($clienteId);
    }

    public function listarPedidos(): array
    {
        return $this->pedidoRepository->obtenerTodos();
    }

    private function calcularTotal(
        int $clienteId,
        int $productoId,
        int $cantidad
    ): int {
        if ($this->clienteRepository->obtenerPorId($clienteId) === null) {
            throw new RuntimeException(
                'No existe un cliente con ese ID.'
            );
        }

        $producto = $this->productoRepository->obtenerPorId($productoId);

        if ($producto === null) {
            throw new RuntimeException(
                'No existe un producto con ese ID.'
            );
        }

        if ($cantidad <= 0) {
            throw new RuntimeException(
                'La cantidad debe ser mayor a cero.'
            );
        }

        return $producto->getPrecio() * $cantidad;
    }

    private function obtenerPedidoExistente(int $id): Pedido
    {
        $pedido = $this->pedidoRepository->obtenerPorId($id);

        if ($pedido === null) {
            throw new RuntimeException(
                'No existe un pedido con ese ID.'
            );
        }

        return $pedido;
    }
}
