<?php

namespace SistemaComercio\DTO\Pedido;

use InvalidArgumentException;

class PedidoRequestDto
{
    public function __construct(
        private int $clienteId,
        private int $productoId,
        private int $cantidad
    ) {}

    public static function desdeArray(array $datos): self
    {
        return new self(
            self::obtenerEnteroPositivo($datos, 'clienteId'),
            self::obtenerEnteroPositivo($datos, 'productoId'),
            self::obtenerEnteroPositivo($datos, 'cantidad')
        );
    }

    public function getClienteId(): int
    {
        return $this->clienteId;
    }

    public function getProductoId(): int
    {
        return $this->productoId;
    }

    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    private static function obtenerEnteroPositivo(
        array $datos,
        string $campo
    ): int {
        $valor = filter_var(
            $datos[$campo] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($valor === false) {
            throw new InvalidArgumentException(
                "El campo {$campo} debe ser un entero mayor a cero."
            );
        }

        return $valor;
    }
}
