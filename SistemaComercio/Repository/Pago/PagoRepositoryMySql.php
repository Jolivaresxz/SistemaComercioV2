<?php

namespace SistemaComercio\Repository\Pago;

use DateTimeImmutable;
use mysqli;
use RuntimeException;
use SistemaComercio\Entidad\Pago\Pago;

class PagoRepositoryMySql implements IPagoRepository
{
    public function __construct(private mysqli $conexion) {}

    public function guardar(Pago $pago): Pago
    {
        $sentencia = $this->conexion->prepare(
            'INSERT INTO pago (PedidoId, Monto, Metodo, Estado, Fecha) '
            . 'VALUES (?, ?, ?, ?, ?)'
        );
        $pedidoId = $pago->getPedidoId();
        $monto = $pago->getMonto();
        $metodo = $pago->getMetodo();
        $estado = $pago->getEstado();
        $fecha = $pago->getFecha()->format('Y-m-d H:i:s');
        $sentencia->bind_param(
            'iisss',
            $pedidoId,
            $monto,
            $metodo,
            $estado,
            $fecha
        );
        $sentencia->execute();
        $pago->asignarId((int) $this->conexion->insert_id);
        $sentencia->close();

        return $pago;
    }

    public function actualizar(Pago $pago): void
    {
        $id = $pago->getId();

        if ($id === null) {
            throw new RuntimeException(
                'No se puede actualizar un pago sin ID.'
            );
        }

        $sentencia = $this->conexion->prepare(
            'UPDATE pago SET PedidoId = ?, Monto = ?, Metodo = ?, '
            . 'Estado = ? WHERE Id = ?'
        );
        $pedidoId = $pago->getPedidoId();
        $monto = $pago->getMonto();
        $metodo = $pago->getMetodo();
        $estado = $pago->getEstado();
        $sentencia->bind_param(
            'iissi',
            $pedidoId,
            $monto,
            $metodo,
            $estado,
            $id
        );
        $sentencia->execute();
        $filasAfectadas = $sentencia->affected_rows;
        $sentencia->close();

        if ($filasAfectadas === 0 && $this->obtenerPorId($id) === null) {
            throw new RuntimeException(
                'No se puede actualizar un pago inexistente.'
            );
        }
    }

    public function obtenerPorId(int $id): ?Pago
    {
        $sentencia = $this->conexion->prepare(
            'SELECT Id, PedidoId, Monto, Metodo, Estado, Fecha '
            . 'FROM pago WHERE Id = ?'
        );
        $sentencia->bind_param('i', $id);
        $sentencia->execute();
        $fila = $sentencia->get_result()->fetch_assoc();
        $sentencia->close();

        return $fila === null ? null : $this->crearDesdeFila($fila);
    }

    public function obtenerPorPedidoId(int $pedidoId): ?Pago
    {
        $sentencia = $this->conexion->prepare(
            'SELECT Id, PedidoId, Monto, Metodo, Estado, Fecha '
            . 'FROM pago WHERE PedidoId = ?'
        );
        $sentencia->bind_param('i', $pedidoId);
        $sentencia->execute();
        $fila = $sentencia->get_result()->fetch_assoc();
        $sentencia->close();

        return $fila === null ? null : $this->crearDesdeFila($fila);
    }

    public function obtenerTodos(): array
    {
        $resultado = $this->conexion->query(
            'SELECT Id, PedidoId, Monto, Metodo, Estado, Fecha '
            . 'FROM pago ORDER BY Id'
        );
        $pagos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $pagos[] = $this->crearDesdeFila($fila);
        }

        $resultado->free();

        return $pagos;
    }

    private function crearDesdeFila(array $fila): Pago
    {
        return new Pago(
            (int) $fila['Id'],
            (int) $fila['PedidoId'],
            (int) $fila['Monto'],
            $fila['Metodo'],
            $fila['Estado'],
            new DateTimeImmutable($fila['Fecha'])
        );
    }
}
