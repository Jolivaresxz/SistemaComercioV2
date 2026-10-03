<?php

namespace SistemaComercio\Service\Pago;

use SistemaComercio\Entidad\Pago\Pago;

interface IPagoService
{
    public function crearPago(int $pedidoId, string $metodo): Pago;
    public function cambiarEstado(int $id, string $estado): Pago;
    public function obtenerPagoPorId(int $id): ?Pago;
    public function obtenerPagoPorPedido(int $pedidoId): ?Pago;
    public function listarPagos(): array;
}
