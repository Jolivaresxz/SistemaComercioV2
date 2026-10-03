<?php

namespace SistemaComercio\Clientes\Repository\Pago;

use SistemaComercio\Clientes\Entidad\Pago\Pago;

interface IPagoRepository
{
    public function guardar(Pago $pago): Pago;
    public function actualizar(Pago $pago): void;
    public function obtenerPorId(int $id): ?Pago;
    public function obtenerPorPedidoId(int $pedidoId): ?Pago;
    public function obtenerTodos(): array;
}
