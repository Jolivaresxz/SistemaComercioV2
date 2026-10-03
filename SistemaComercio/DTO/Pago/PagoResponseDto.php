<?php

namespace SistemaComercio\DTO\Pago;

use SistemaComercio\Entidad\Pago\Pago;

class PagoResponseDto
{
    public function __construct(
        private ?int $id,
        private int $pedidoId,
        private int $monto,
        private string $metodo,
        private string $estado,
        private string $fecha
    ) {}

    public static function desdeEntidad(Pago $pago): self
    {
        return new self(
            $pago->getId(),
            $pago->getPedidoId(),
            $pago->getMonto(),
            $pago->getMetodo(),
            $pago->getEstado(),
            $pago->getFecha()->format('Y-m-d H:i:s')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'pedidoId' => $this->pedidoId,
            'monto' => $this->monto,
            'metodo' => $this->metodo,
            'estado' => $this->estado,
            'fecha' => $this->fecha
        ];
    }
}
