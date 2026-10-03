<?php

namespace SistemaComercio\Service\Pago;

use RuntimeException;
use SistemaComercio\Entidad\Pago\Pago;
use SistemaComercio\Repository\Pago\IPagoRepository;
use SistemaComercio\Repository\Pedido\IPedidoRepository;

class PagoService implements IPagoService
{
    public function __construct(
        private IPagoRepository $pagoRepository,
        private IPedidoRepository $pedidoRepository
    ) {}

    public function crearPago(int $pedidoId, string $metodo): Pago
    {
        $pedido = $this->pedidoRepository->obtenerPorId($pedidoId);

        if ($pedido === null) {
            throw new RuntimeException(
                'No existe un pedido con ese ID.'
            );
        }

        if ($this->pagoRepository->obtenerPorPedidoId($pedidoId) !== null) {
            throw new RuntimeException(
                'Ya existe un pago para ese pedido.'
            );
        }

        $pago = new Pago(
            null,
            $pedidoId,
            $pedido->getTotal(),
            $metodo
        );

        return $this->pagoRepository->guardar($pago);
    }

    public function cambiarEstado(int $id, string $estado): Pago
    {
        $pago = $this->obtenerPagoExistente($id);
        $pago->cambiarEstado($estado);
        $this->pagoRepository->actualizar($pago);

        return $pago;
    }

    public function obtenerPagoPorId(int $id): ?Pago
    {
        return $this->pagoRepository->obtenerPorId($id);
    }

    public function obtenerPagoPorPedido(int $pedidoId): ?Pago
    {
        return $this->pagoRepository->obtenerPorPedidoId($pedidoId);
    }

    public function listarPagos(): array
    {
        return $this->pagoRepository->obtenerTodos();
    }

    private function obtenerPagoExistente(int $id): Pago
    {
        $pago = $this->pagoRepository->obtenerPorId($id);

        if ($pago === null) {
            throw new RuntimeException(
                'No existe un pago con ese ID.'
            );
        }

        return $pago;
    }
}
