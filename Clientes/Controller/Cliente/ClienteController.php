<?php

namespace SistemaComercio\Clientes\Controller\Cliente;

use SistemaComercio\Clientes\DTO\Cliente\ClienteRequestDto;
use SistemaComercio\Clientes\DTO\Cliente\ClienteResponseDto;
use SistemaComercio\Clientes\Service\Cliente\IClienteService;

class ClienteController
{
    public function __construct(private IClienteService $clienteService) {}

    public function crear(ClienteRequestDto $solicitud): ClienteResponseDto
    {
        $cliente = $this->clienteService->crearCliente(
            $solicitud->getRut(),
            $solicitud->getNombre(),
            $solicitud->getEmail()
        );

        return ClienteResponseDto::desdeEntidad($cliente);
    }

    public function modificar(
        int $id,
        ClienteRequestDto $solicitud
    ): ClienteResponseDto {
        $cliente = $this->clienteService->modificarCliente(
            $id,
            $solicitud->getRut(),
            $solicitud->getNombre(),
            $solicitud->getEmail()
        );

        return ClienteResponseDto::desdeEntidad($cliente);
    }

    public function eliminar(int $id): bool
    {
        return $this->clienteService->eliminarCliente($id);
    }

    public function obtenerPorId(int $id): ?ClienteResponseDto
    {
        $cliente = $this->clienteService->obtenerClientePorId($id);

        return $cliente === null
            ? null
            : ClienteResponseDto::desdeEntidad($cliente);
    }

    public function obtenerPorRut(string $rut): ?ClienteResponseDto
    {
        $cliente = $this->clienteService->obtenerClientePorRut($rut);

        return $cliente === null
            ? null
            : ClienteResponseDto::desdeEntidad($cliente);
    }

    public function obtenerTodos(): array
    {
        return array_map(
            static fn ($cliente): ClienteResponseDto =>
                ClienteResponseDto::desdeEntidad($cliente),
            $this->clienteService->listarClientes()
        );
    }
}
