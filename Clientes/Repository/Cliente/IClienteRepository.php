<?php

namespace SistemaComercio\Clientes\Repository\Cliente;

use SistemaComercio\Clientes\Entidad\Cliente\Cliente;

interface IClienteRepository
{
    public function guardar(Cliente $cliente): Cliente;
    public function actualizar(Cliente $cliente): void;
    public function obtenerPorId(int $id): ?Cliente;
    public function obtenerPorRut(string $rut): ?Cliente;
    public function obtenerTodos(): array;
    public function eliminar(int $id): bool;
}
