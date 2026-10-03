<?php

namespace SistemaComercio\Clientes\Service\Cliente;

use SistemaComercio\Clientes\Entidad\Cliente\Cliente;

interface IClienteService
{
    public function crearCliente(
        string $rut,
        string $nombre,
        string $email
    ): Cliente;

    public function modificarCliente(
        int $id,
        string $rut,
        string $nombre,
        string $email
    ): Cliente;

    public function eliminarCliente(int $id): bool;
    public function obtenerClientePorId(int $id): ?Cliente;
    public function obtenerClientePorRut(string $rut): ?Cliente;
    public function listarClientes(): array;
}
