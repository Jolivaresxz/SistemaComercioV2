<?php

namespace SistemaComercio\Clientes\Controller\Pedido;

use SistemaComercio\Clientes\DTO\Pedido\PedidoRequestDto;
use SistemaComercio\Clientes\DTO\Pedido\PedidoResponseDto;
use SistemaComercio\Clientes\Service\Pedido\IPedidoService;

class PedidoController
{
    public function __construct(private IPedidoService $pedidoService) {}

    public function crear(PedidoRequestDto $solicitud): PedidoResponseDto
    {
        $pedido = $this->pedidoService->crearPedido(
            $solicitud->getClienteId(),
            $solicitud->getProductoId(),
            $solicitud->getCantidad()
        );

        return PedidoResponseDto::desdeEntidad($pedido);
    }

    public function modificar(
        int $id,
        PedidoRequestDto $solicitud
    ): PedidoResponseDto {
        $pedido = $this->pedidoService->modificarPedido(
            $id,
            $solicitud->getClienteId(),
            $solicitud->getProductoId(),
            $solicitud->getCantidad()
        );

        return PedidoResponseDto::desdeEntidad($pedido);
    }

    public function cambiarEstado(
        int $id,
        string $estado
    ): PedidoResponseDto {
        return PedidoResponseDto::desdeEntidad(
            $this->pedidoService->cambiarEstado($id, $estado)
        );
    }

    public function obtenerPorId(int $id): ?PedidoResponseDto
    {
        $pedido = $this->pedidoService->obtenerPedidoPorId($id);

        return $pedido === null
            ? null
            : PedidoResponseDto::desdeEntidad($pedido);
    }

    public function obtenerPorCliente(int $clienteId): array
    {
        return $this->convertirLista(
            $this->pedidoService->obtenerPedidosPorCliente($clienteId)
        );
    }

    public function obtenerTodos(): array
    {
        return $this->convertirLista(
            $this->pedidoService->listarPedidos()
        );
    }

    private function convertirLista(array $pedidos): array
    {
        return array_map(
            static fn ($pedido): PedidoResponseDto =>
                PedidoResponseDto::desdeEntidad($pedido),
            $pedidos
        );
    }
}
