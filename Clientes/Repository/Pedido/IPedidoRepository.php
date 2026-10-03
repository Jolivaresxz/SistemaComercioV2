<?php

namespace SistemaComercio\Clientes\Repository\Pedido;

use SistemaComercio\Clientes\Entidad\Pedido\Pedido;

interface IPedidoRepository
{
    public function guardar(Pedido $pedido): Pedido;
    public function actualizar(Pedido $pedido): void;
    public function obtenerPorId(int $id): ?Pedido;
    public function obtenerPorClienteId(int $clienteId): array;
    public function obtenerTodos(): array;
}
