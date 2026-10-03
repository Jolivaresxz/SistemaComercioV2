<?php

namespace SistemaComercio\Clientes\Entidad\Cliente;

use InvalidArgumentException;

class Cliente
{
    private ?int $id;
    private string $rut;
    private string $nombre;
    private string $email;

    public function __construct(
        ?int $id,
        string $rut,
        string $nombre,
        string $email
    ) {
        $this->id = $id;
        $this->validarYAsignarDatos($rut, $nombre, $email);
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function actualizarDatos(
        string $rut,
        string $nombre,
        string $email
    ): void {
        $this->validarYAsignarDatos($rut, $nombre, $email);
    }

    public function asignarId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'El ID debe ser mayor a cero.'
            );
        }

        $this->id = $id;
    }

    private function validarYAsignarDatos(
        string $rut,
        string $nombre,
        string $email
    ): void {
        $rut = trim($rut);
        $nombre = trim($nombre);
        $email = trim($email);

        if ($rut === '') {
            throw new InvalidArgumentException('El RUT es obligatorio.');
        }

        if ($nombre === '') {
            throw new InvalidArgumentException('El nombre es obligatorio.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                'El email no tiene un formato válido.'
            );
        }

        $this->rut = $rut;
        $this->nombre = $nombre;
        $this->email = $email;
    }
}
