<?php

namespace SistemaComercio\Clientes\Entidad\Pedido;

use DateTimeImmutable;
use InvalidArgumentException;

class Pedido
{
    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_CONFIRMADO = 'CONFIRMADO';
    public const ESTADO_CANCELADO = 'CANCELADO';

    private const ESTADOS_VALIDOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_CONFIRMADO,
        self::ESTADO_CANCELADO
    ];

    private ?int $id;
    private int $clienteId;
    private int $productoId;
    private int $cantidad;
    private int $total;
    private string $estado;
    private DateTimeImmutable $fecha;

    public function __construct(
        ?int $id,
        int $clienteId,
        int $productoId,
        int $cantidad,
        int $total,
        string $estado = self::ESTADO_PENDIENTE,
        ?DateTimeImmutable $fecha = null
    ) {
        $this->id = $id;
        $this->validarYAsignarDatos(
            $clienteId,
            $productoId,
            $cantidad,
            $total
        );
        $this->cambiarEstado($estado);
        $this->fecha = $fecha ?? new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTotal(): int
    {
        return $this->total;
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
        int $clienteId,
        int $productoId,
        int $cantidad,
        int $total
    ): void {
        $this->validarYAsignarDatos(
            $clienteId,
            $productoId,
            $cantidad,
            $total
        );
    }

    public function cambiarEstado(string $estado): void
    {
        $estado = strtoupper(trim($estado));

        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            throw new InvalidArgumentException(
                'El estado del pedido no es válido.'
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
        int $clienteId,
        int $productoId,
        int $cantidad,
        int $total
    ): void {
        if ($clienteId <= 0) {
            throw new InvalidArgumentException(
                'El ID del cliente debe ser mayor a cero.'
            );
        }

        if ($productoId <= 0) {
            throw new InvalidArgumentException(
                'El ID del producto debe ser mayor a cero.'
            );
        }

        if ($cantidad <= 0) {
            throw new InvalidArgumentException(
                'La cantidad debe ser mayor a cero.'
            );
        }

        if ($total <= 0) {
            throw new InvalidArgumentException(
                'El total debe ser mayor a cero.'
            );
        }

        $this->clienteId = $clienteId;
        $this->productoId = $productoId;
        $this->cantidad = $cantidad;
        $this->total = $total;
    }
}
