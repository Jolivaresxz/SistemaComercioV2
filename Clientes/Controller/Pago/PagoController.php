<?php

namespace SistemaComercio\Clientes\Controller\Pago;

use SistemaComercio\Clientes\DTO\Pago\PagoRequestDto;
use SistemaComercio\Clientes\DTO\Pago\PagoResponseDto;
use SistemaComercio\Clientes\Service\Pago\IPagoService;

class PagoController
{
    public function __construct(private IPagoService $pagoService) {}

    public function crear(PagoRequestDto $solicitud): PagoResponseDto
    {
        $pago = $this->pagoService->crearPago(
            $solicitud->getPedidoId(),
            $solicitud->getMetodo()
        );

        return PagoResponseDto::desdeEntidad($pago);
    }

    public function cambiarEstado(
        int $id,
        string $estado
    ): PagoResponseDto {
        return PagoResponseDto::desdeEntidad(
            $this->pagoService->cambiarEstado($id, $estado)
        );
    }

    public function obtenerPorId(int $id): ?PagoResponseDto
    {
        $pago = $this->pagoService->obtenerPagoPorId($id);

        return $pago === null
            ? null
            : PagoResponseDto::desdeEntidad($pago);
    }

    public function obtenerPorPedido(int $pedidoId): ?PagoResponseDto
    {
        $pago = $this->pagoService->obtenerPagoPorPedido($pedidoId);

        return $pago === null
            ? null
            : PagoResponseDto::desdeEntidad($pago);
    }

    public function obtenerTodos(): array
    {
        return array_map(
            static fn ($pago): PagoResponseDto =>
                PagoResponseDto::desdeEntidad($pago),
            $this->pagoService->listarPagos()
        );
    }
}
