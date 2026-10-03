<?php

namespace SistemaComercio\Clientes\Repository\Pedido;

use DateTimeImmutable;
use mysqli;
use RuntimeException;
use SistemaComercio\Clientes\Entidad\Pedido\Pedido;

class PedidoRepositoryMySql implements IPedidoRepository
{
    public function __construct(private mysqli $conexion) {}

    public function guardar(Pedido $pedido): Pedido
    {
        $sentencia = $this->conexion->prepare(
            'INSERT INTO pedido '
            . '(ClienteId, ProductoId, Cantidad, Total, Estado, Fecha) '
            . 'VALUES (?, ?, ?, ?, ?, ?)'
        );
        $clienteId = $pedido->getClienteId();
        $productoId = $pedido->getProductoId();
        $cantidad = $pedido->getCantidad();
        $total = $pedido->getTotal();
        $estado = $pedido->getEstado();
        $fecha = $pedido->getFecha()->format('Y-m-d H:i:s');
        $sentencia->bind_param(
            'iiiiss',
            $clienteId,
            $productoId,
            $cantidad,
            $total,
            $estado,
            $fecha
        );
        $sentencia->execute();
        $pedido->asignarId((int) $this->conexion->insert_id);
        $sentencia->close();

        return $pedido;
    }

    public function actualizar(Pedido $pedido): void
    {
        $id = $pedido->getId();

        if ($id === null) {
            throw new RuntimeException(
                'No se puede actualizar un pedido sin ID.'
            );
        }

        $sentencia = $this->conexion->prepare(
            'UPDATE pedido SET ClienteId = ?, ProductoId = ?, '
            . 'Cantidad = ?, Total = ?, Estado = ? WHERE Id = ?'
        );
        $clienteId = $pedido->getClienteId();
        $productoId = $pedido->getProductoId();
        $cantidad = $pedido->getCantidad();
        $total = $pedido->getTotal();
        $estado = $pedido->getEstado();
        $sentencia->bind_param(
            'iiiisi',
            $clienteId,
            $productoId,
            $cantidad,
            $total,
            $estado,
            $id
        );
        $sentencia->execute();
        $filasAfectadas = $sentencia->affected_rows;
        $sentencia->close();

        if ($filasAfectadas === 0 && $this->obtenerPorId($id) === null) {
            throw new RuntimeException(
                'No se puede actualizar un pedido inexistente.'
            );
        }
    }

    public function obtenerPorId(int $id): ?Pedido
    {
        $sentencia = $this->conexion->prepare(
            'SELECT Id, ClienteId, ProductoId, Cantidad, Total, Estado, Fecha '
            . 'FROM pedido WHERE Id = ?'
        );
        $sentencia->bind_param('i', $id);
        $sentencia->execute();
        $fila = $sentencia->get_result()->fetch_assoc();
        $sentencia->close();

        return $fila === null ? null : $this->crearDesdeFila($fila);
    }

    public function obtenerPorClienteId(int $clienteId): array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT Id, ClienteId, ProductoId, Cantidad, Total, Estado, Fecha '
            . 'FROM pedido WHERE ClienteId = ? ORDER BY Id'
        );
        $sentencia->bind_param('i', $clienteId);
        $sentencia->execute();
        $resultado = $sentencia->get_result();
        $pedidos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $pedidos[] = $this->crearDesdeFila($fila);
        }

        $sentencia->close();

        return $pedidos;
    }

    public function obtenerTodos(): array
    {
        $resultado = $this->conexion->query(
            'SELECT Id, ClienteId, ProductoId, Cantidad, Total, Estado, Fecha '
            . 'FROM pedido ORDER BY Id'
        );
        $pedidos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $pedidos[] = $this->crearDesdeFila($fila);
        }

        $resultado->free();

        return $pedidos;
    }

    private function crearDesdeFila(array $fila): Pedido
    {
        return new Pedido(
            (int) $fila['Id'],
            (int) $fila['ClienteId'],
            (int) $fila['ProductoId'],
            (int) $fila['Cantidad'],
            (int) $fila['Total'],
            $fila['Estado'],
            new DateTimeImmutable($fila['Fecha'])
        );
    }
}
