<?php

namespace SistemaComercio\Service\Pedido;

use SistemaComercio\Entidad\Pedido\Pedido;

interface IPedidoService
{
    public function crearPedido(
        int $clienteId,
        int $productoId,
        int $cantidad
    ): Pedido;
    public function modificarPedido(
        int $id,
        int $clienteId,
        int $productoId,
        int $cantidad
    ): Pedido;
    public function cambiarEstado(int $id, string $estado): Pedido;
    public function obtenerPedidoPorId(int $id): ?Pedido;
    public function obtenerPedidosPorCliente(int $clienteId): array;
    public function listarPedidos(): array;
}
