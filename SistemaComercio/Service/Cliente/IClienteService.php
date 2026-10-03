<?php

namespace SistemaComercio\Service\Cliente;

use SistemaComercio\Entidad\Cliente\Cliente;

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
