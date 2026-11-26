<?php
declare(strict_types=1);

namespace App\Controllers;

use Exception;

abstract class AbstractController
{
    abstract protected function validarDatos(array $data, bool $isUpdate = false): void;

    protected function validarRequerido(array $data, string $campo, string $mensaje): void
    {
        if (!isset($data[$campo]) || trim((string) $data[$campo]) === '') {
            throw new Exception($mensaje, 400);
        }
    }

    protected function validarFecha(string $fecha): void
    {
        $objeto = \DateTime::createFromFormat('Y-m-d', $fecha);

        if (!$objeto || $objeto->format('Y-m-d') !== $fecha) {
            throw new Exception('La fecha debe tener el formato AAAA-MM-DD.', 400);
        }
    }

    /** Acepta HH:MM o HH:MM:SS y siempre devuelve HH:MM:SS (formato de la columna TIME). */
    protected function normalizarHora(string $hora): string
    {
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $hora)) {
            throw new Exception('La hora debe tener el formato HH:MM o HH:MM:SS.', 400);
        }

        return strlen($hora) === 5 ? $hora . ':00' : $hora;
    }
}
