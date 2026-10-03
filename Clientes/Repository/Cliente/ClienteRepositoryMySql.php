<?php

namespace SistemaComercio\Clientes\Repository\Cliente;

use mysqli;
use RuntimeException;
use SistemaComercio\Clientes\Entidad\Cliente\Cliente;

class ClienteRepositoryMySql implements IClienteRepository
{
    public function __construct(private mysqli $conexion) {}

    public function guardar(Cliente $cliente): Cliente
    {
        if ($cliente->getId() === null) {
            $sentencia = $this->conexion->prepare(
                'INSERT INTO cliente (Rut, Nombre, Email) VALUES (?, ?, ?)'
            );
            $rut = $cliente->getRut();
            $nombre = $cliente->getNombre();
            $email = $cliente->getEmail();
            $sentencia->bind_param('sss', $rut, $nombre, $email);
            $sentencia->execute();
            $cliente->asignarId((int) $this->conexion->insert_id);
            $sentencia->close();

            return $cliente;
        }

        $sentencia = $this->conexion->prepare(
            'INSERT INTO cliente (Id, Rut, Nombre, Email) VALUES (?, ?, ?, ?)'
        );
        $id = $cliente->getId();
        $rut = $cliente->getRut();
        $nombre = $cliente->getNombre();
        $email = $cliente->getEmail();
        $sentencia->bind_param('isss', $id, $rut, $nombre, $email);
        $sentencia->execute();
        $sentencia->close();

        return $cliente;
    }

    public function actualizar(Cliente $cliente): void
    {
        $id = $cliente->getId();

        if ($id === null) {
            throw new RuntimeException(
                'No se puede actualizar un cliente sin ID.'
            );
        }

        $sentencia = $this->conexion->prepare(
            'UPDATE cliente SET Rut = ?, Nombre = ?, Email = ? WHERE Id = ?'
        );
        $rut = $cliente->getRut();
        $nombre = $cliente->getNombre();
        $email = $cliente->getEmail();
        $sentencia->bind_param('sssi', $rut, $nombre, $email, $id);
        $sentencia->execute();
        $filasAfectadas = $sentencia->affected_rows;
        $sentencia->close();

        if ($filasAfectadas === 0 && $this->obtenerPorId($id) === null) {
            throw new RuntimeException(
                'No se puede actualizar un cliente inexistente.'
            );
        }
    }

    public function obtenerPorId(int $id): ?Cliente
    {
        $sentencia = $this->conexion->prepare(
            'SELECT Id, Rut, Nombre, Email FROM cliente WHERE Id = ?'
        );
        $sentencia->bind_param('i', $id);
        $sentencia->execute();
        $fila = $sentencia->get_result()->fetch_assoc();
        $sentencia->close();

        return $fila === null ? null : $this->crearClienteDesdeFila($fila);
    }

    public function obtenerPorRut(string $rut): ?Cliente
    {
        $sentencia = $this->conexion->prepare(
            'SELECT Id, Rut, Nombre, Email FROM cliente WHERE Rut = ?'
        );
        $sentencia->bind_param('s', $rut);
        $sentencia->execute();
        $fila = $sentencia->get_result()->fetch_assoc();
        $sentencia->close();

        return $fila === null ? null : $this->crearClienteDesdeFila($fila);
    }

    public function obtenerTodos(): array
    {
        $resultado = $this->conexion->query(
            'SELECT Id, Rut, Nombre, Email FROM cliente ORDER BY Id'
        );
        $clientes = [];

        while ($fila = $resultado->fetch_assoc()) {
            $clientes[] = $this->crearClienteDesdeFila($fila);
        }

        $resultado->free();

        return $clientes;
    }

    public function eliminar(int $id): bool
    {
        $sentencia = $this->conexion->prepare(
            'DELETE FROM cliente WHERE Id = ?'
        );
        $sentencia->bind_param('i', $id);
        $sentencia->execute();
        $eliminado = $sentencia->affected_rows === 1;
        $sentencia->close();

        return $eliminado;
    }

    private function crearClienteDesdeFila(array $fila): Cliente
    {
        return new Cliente(
            (int) $fila['Id'],
            $fila['Rut'],
            $fila['Nombre'],
            $fila['Email']
        );
    }
}
