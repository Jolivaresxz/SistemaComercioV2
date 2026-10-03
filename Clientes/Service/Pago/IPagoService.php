<?php

namespace SistemaComercio\Clientes\Service\Pago;

use SistemaComercio\Clientes\Entidad\Pago\Pago;

interface IPagoService
{
    public function crearPago(int $pedidoId, string $metodo): Pago;
    public function cambiarEstado(int $id, string $estado): Pago;
    public function obtenerPagoPorId(int $id): ?Pago;
    public function obtenerPagoPorPedido(int $pedidoId): ?Pago;
    public function listarPagos(): array;
}
