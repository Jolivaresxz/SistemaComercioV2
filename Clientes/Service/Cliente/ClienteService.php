<?php

namespace SistemaComercio\Clientes\Service\Cliente;

use RuntimeException;
use SistemaComercio\Clientes\Entidad\Cliente\Cliente;
use SistemaComercio\Clientes\Repository\Cliente\IClienteRepository;

class ClienteService implements IClienteService
{
    public function __construct(private IClienteRepository $repository) {}

    public function crearCliente(
        string $rut,
        string $nombre,
        string $email
    ): Cliente {
        $rut = trim($rut);

        if ($this->repository->obtenerPorRut($rut) !== null) {
            throw new RuntimeException(
                'Ya existe un cliente con ese RUT.'
            );
        }

        $cliente = new Cliente(null, $rut, $nombre, $email);

        return $this->repository->guardar($cliente);
    }

    public function modificarCliente(
        int $id,
        string $rut,
        string $nombre,
        string $email
    ): Cliente {
        $cliente = $this->repository->obtenerPorId($id);

        if ($cliente === null) {
            throw new RuntimeException(
                'No existe un cliente con ese ID.'
            );
        }

        $rut = trim($rut);
        $clienteConRut = $this->repository->obtenerPorRut($rut);

        if ($clienteConRut !== null && $clienteConRut->getId() !== $id) {
            throw new RuntimeException(
                'Ya existe un cliente con ese RUT.'
            );
        }

        $cliente->actualizarDatos($rut, $nombre, $email);
        $this->repository->actualizar($cliente);

        return $cliente;
    }

    public function eliminarCliente(int $id): bool
    {
        return $this->repository->eliminar($id);
    }

    public function obtenerClientePorId(int $id): ?Cliente
    {
        return $this->repository->obtenerPorId($id);
    }

    public function obtenerClientePorRut(string $rut): ?Cliente
    {
        return $this->repository->obtenerPorRut(trim($rut));
    }

    public function listarClientes(): array
    {
        return $this->repository->obtenerTodos();
    }
}
