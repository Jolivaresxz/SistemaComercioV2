<?php

namespace SistemaComercio\DTO\Pedido;

use SistemaComercio\Entidad\Pedido\Pedido;

class PedidoResponseDto
{
    public function __construct(
        private ?int $id,
        private int $clienteId,
        private int $productoId,
        private int $cantidad,
        private int $total,
        private string $estado,
        private string $fecha
    ) {}

    public static function desdeEntidad(Pedido $pedido): self
    {
        return new self(
            $pedido->getId(),
            $pedido->getClienteId(),
            $pedido->getProductoId(),
            $pedido->getCantidad(),
            $pedido->getTotal(),
            $pedido->getEstado(),
            $pedido->getFecha()->format('Y-m-d H:i:s')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'clienteId' => $this->clienteId,
            'productoId' => $this->productoId,
            'cantidad' => $this->cantidad,
            'total' => $this->total,
            'estado' => $this->estado,
            'fecha' => $this->fecha
        ];
    }
}
