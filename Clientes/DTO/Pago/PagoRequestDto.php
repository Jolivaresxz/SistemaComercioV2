<?php

namespace SistemaComercio\Clientes\DTO\Pago;

use InvalidArgumentException;

class PagoRequestDto
{
    public function __construct(
        private int $pedidoId,
        private string $metodo
    ) {}

    public static function desdeArray(array $datos): self
    {
        $pedidoId = filter_var(
            $datos['pedidoId'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $metodo = $datos['metodo'] ?? null;

        if ($pedidoId === false) {
            throw new InvalidArgumentException(
                'El pedidoId debe ser un entero mayor a cero.'
            );
        }

        if (!is_string($metodo) || trim($metodo) === '') {
            throw new InvalidArgumentException(
                'El método de pago es obligatorio.'
            );
        }

        return new self($pedidoId, trim($metodo));
    }

    public function getPedidoId(): int
    {
        return $this->pedidoId;
    }

    public function getMetodo(): string
    {
        return $this->metodo;
    }
}
