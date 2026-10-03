<?php

namespace SistemaComercio\Clientes\Repository\Cliente;

use RuntimeException;
use SistemaComercio\Clientes\Entidad\Cliente\Cliente;

class ClienteRepositoryMemoria implements IClienteRepository
{
    private array $clientes = [];
    private int $siguienteId = 1;

    public function guardar(Cliente $cliente): Cliente
    {
        if ($cliente->getId() === null) {
            $cliente->asignarId($this->siguienteId);
            $this->siguienteId++;
        }

        $this->clientes[$cliente->getId()] = $cliente;

        return $cliente;
    }

    public function actualizar(Cliente $cliente): void
    {
        $id = $cliente->getId();

        if ($id === null || !isset($this->clientes[$id])) {
            throw new RuntimeException(
                'No se puede actualizar un cliente inexistente.'
            );
        }

        $this->clientes[$id] = $cliente;
    }

    public function obtenerPorId(int $id): ?Cliente
    {
        return $this->clientes[$id] ?? null;
    }

    public function obtenerPorRut(string $rut): ?Cliente
    {
        foreach ($this->clientes as $cliente) {
            if ($cliente->getRut() === $rut) {
                return $cliente;
            }
        }

        return null;
    }

    public function obtenerTodos(): array
    {
        return array_values($this->clientes);
    }

    public function eliminar(int $id): bool
    {
        if (!isset($this->clientes[$id])) {
            return false;
        }

        unset($this->clientes[$id]);

        return true;
    }
}
