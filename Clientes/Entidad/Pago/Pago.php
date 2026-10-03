<?php

namespace SistemaComercio\Clientes\Entidad\Pago;

use DateTimeImmutable;
use InvalidArgumentException;

class Pago
{
    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_APROBADO = 'APROBADO';
    public const ESTADO_RECHAZADO = 'RECHAZADO';

    private const ESTADOS_VALIDOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_APROBADO,
        self::ESTADO_RECHAZADO
    ];

    private ?int $id;
    private int $pedidoId;
    private int $monto;
    private string $metodo;
    private string $estado;
    private DateTimeImmutable $fecha;

    public function __construct(
        ?int $id,
        int $pedidoId,
        int $monto,
        string $metodo,
        string $estado = self::ESTADO_PENDIENTE,
        ?DateTimeImmutable $fecha = null
    ) {
        $this->id = $id;
        $this->validarYAsignarDatos($pedidoId, $monto, $metodo);
        $this->cambiarEstado($estado);
        $this->fecha = $fecha ?? new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPedidoId(): int
    {
        return $this->pedidoId;
    }

    public function getMonto(): int
    {
        return $this->monto;
    }

    public function getMetodo(): string
    {
        return $this->metodo;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getFecha(): DateTimeImmutable
    {
        return $this->fecha;
    }

    public function actualizarDatos(
        int $pedidoId,
        int $monto,
        string $metodo
    ): void {
        $this->validarYAsignarDatos($pedidoId, $monto, $metodo);
    }

    public function cambiarEstado(string $estado): void
    {
        $estado = strtoupper(trim($estado));

        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            throw new InvalidArgumentException(
                'El estado del pago no es válido.'
            );
        }

        $this->estado = $estado;
    }

    public function asignarId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'El ID debe ser mayor a cero.'
            );
        }

        $this->id = $id;
    }

    private function validarYAsignarDatos(
        int $pedidoId,
        int $monto,
        string $metodo
    ): void {
        $metodo = trim($metodo);

        if ($pedidoId <= 0) {
            throw new InvalidArgumentException(
                'El ID del pedido debe ser mayor a cero.'
            );
        }

        if ($monto <= 0) {
            throw new InvalidArgumentException(
                'El monto debe ser mayor a cero.'
            );
        }

        if ($metodo === '') {
            throw new InvalidArgumentException(
                'El método de pago es obligatorio.'
            );
        }

        $this->pedidoId = $pedidoId;
        $this->monto = $monto;
        $this->metodo = $metodo;
    }
}
