<?php

namespace SistemaComercio\Clientes\DTO\Cliente;

use SistemaComercio\Clientes\Entidad\Cliente\Cliente;

class ClienteResponseDto
{
    public function __construct(
        private ?int $id,
        private string $rut,
        private string $nombre,
        private string $email
    ) {}

    public static function desdeEntidad(Cliente $cliente): self
    {
        return new self(
            $cliente->getId(),
            $cliente->getRut(),
            $cliente->getNombre(),
            $cliente->getEmail()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'rut' => $this->rut,
            'nombre' => $this->nombre,
            'email' => $this->email
        ];
    }
}
