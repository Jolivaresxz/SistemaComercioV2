<?php

namespace SistemaComercio\Clientes\Repository\Producto;

use mysqli;
use RuntimeException;
use SistemaComercio\Clientes\Entidad\Producto\Producto;

class ProductoRepositoryMySql implements IProductoRepository
{
    public function __construct(private mysqli $conexion) {}

    public function guardar(Producto $producto): Producto
    {
        $sentencia = $this->conexion->prepare(
            'INSERT INTO producto (Nombre, Precio) VALUES (?, ?)'
        );
        $nombre = $producto->getNombre();
        $precio = $producto->getPrecio();
        $sentencia->bind_param('si', $nombre, $precio);
        $sentencia->execute();
        $producto->asignarId((int) $this->conexion->insert_id);
        $sentencia->close();

        return $producto;
    }

    public function actualizar(Producto $producto): void
    {
        $id = $producto->getId();

        if ($id === null) {
            throw new RuntimeException(
                'No se puede actualizar un producto sin ID.'
            );
        }

        $sentencia = $this->conexion->prepare(
            'UPDATE producto SET Nombre = ?, Precio = ? WHERE Id = ?'
        );
        $nombre = $producto->getNombre();
        $precio = $producto->getPrecio();
        $sentencia->bind_param('sii', $nombre, $precio, $id);
        $sentencia->execute();
        $filasAfectadas = $sentencia->affected_rows;
        $sentencia->close();

        if ($filasAfectadas === 0 && $this->obtenerPorId($id) === null) {
            throw new RuntimeException(
                'No se puede actualizar un producto inexistente.'
            );
        }
    }

    public function obtenerPorId(int $id): ?Producto
    {
        $sentencia = $this->conexion->prepare(
            'SELECT Id, Nombre, Precio FROM producto WHERE Id = ?'
        );
        $sentencia->bind_param('i', $id);
        $sentencia->execute();
        $fila = $sentencia->get_result()->fetch_assoc();
        $sentencia->close();

        return $fila === null ? null : $this->crearDesdeFila($fila);
    }

    public function obtenerTodos(): array
    {
        $resultado = $this->conexion->query(
            'SELECT Id, Nombre, Precio FROM producto ORDER BY Id'
        );
        $productos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $this->crearDesdeFila($fila);
        }

        $resultado->free();

        return $productos;
    }

    public function eliminar(int $id): bool
    {
        $sentencia = $this->conexion->prepare(
            'DELETE FROM producto WHERE Id = ?'
        );
        $sentencia->bind_param('i', $id);
        $sentencia->execute();
        $eliminado = $sentencia->affected_rows === 1;
        $sentencia->close();

        return $eliminado;
    }

    private function crearDesdeFila(array $fila): Producto
    {
        return new Producto(
            (int) $fila['Id'],
            $fila['Nombre'],
            (int) $fila['Precio']
        );
    }
}
