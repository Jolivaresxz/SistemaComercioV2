<?php

namespace SistemaComercio\Clientes\DTO\Cliente;

use InvalidArgumentException;

class ClienteRequestDto
{
    public function __construct(
        private string $rut,
        private string $nombre,
        private string $email
    ) {}

    public static function desdeArray(array $datos): self
    {
        return new self(
            self::obtenerTextoObligatorio($datos, 'rut'),
            self::obtenerTextoObligatorio($datos, 'nombre'),
            self::obtenerTextoObligatorio($datos, 'email')
        );
    }

    public function getRut(): string
    {
        return $this->rut;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    private static function obtenerTextoObligatorio(
        array $datos,
        string $campo
    ): string {
        if (!isset($datos[$campo]) || !is_string($datos[$campo])) {
            throw new InvalidArgumentException(
                "El campo {$campo} es obligatorio."
            );
        }

        return trim($datos[$campo]);
    }
}
