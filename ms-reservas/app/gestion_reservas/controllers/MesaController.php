<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Mesa;
use Exception;

class MesaController extends AbstractController
{
    private const ESTADOS = ['disponible', 'reservada', 'ocupada', 'fuera_servicio'];

    public function listar(): array
    {
        return Mesa::orderBy('id')->get()->toArray();
    }

    public function obtener(int $id): array
    {
        $mesa = Mesa::find($id);

        if (!$mesa) {
            throw new Exception('Mesa no encontrada.', 404);
        }

        return $mesa->toArray();
    }

    public function crear(array $data): array
    {
        $this->validarDatos($data);

        $numero = trim((string) $data['numero']);

        if (Mesa::where('numero', $numero)->exists()) {
            throw new Exception('Ya existe una mesa con ese número.', 400);
        }

        // Estado inicial: el que llegue o, si no llega, "disponible"
        $estado = isset($data['estado']) && trim((string) $data['estado']) !== ''
            ? trim((string) $data['estado'])
            : 'disponible';
        $this->validarEstado($estado);

        $mesa = Mesa::create([
            'numero'    => $numero,
            'capacidad' => (int) $data['capacidad'],
            'estado'    => $estado,
        ]);

        return $mesa->toArray();
    }

    public function actualizar(int $id, array $data): array
    {
        $mesa = Mesa::find($id);

        if (!$mesa) {
            throw new Exception('Mesa no encontrada.', 404);
        }

        // Solo se editan capacidad y estado (el número identifica a la mesa)
        if (isset($data['capacidad'])) {
            $this->validarCapacidad($data['capacidad']);
            $mesa->capacidad = (int) $data['capacidad'];
        }

        if (isset($data['estado'])) {
            $this->validarEstado((string) $data['estado']);
            $mesa->estado = (string) $data['estado'];
        }

        $mesa->save();

        return $mesa->toArray();
    }

    public function cambiarEstado(int $id, string $estado): array
    {
        $this->validarEstado($estado);

        $mesa = Mesa::find($id);

        if (!$mesa) {
            throw new Exception('Mesa no encontrada.', 404);
        }

        $mesa->estado = $estado;
        $mesa->save();

        return $mesa->toArray();
    }

    protected function validarDatos(array $data, bool $isUpdate = false): void
    {
        $this->validarRequerido($data, 'numero', 'El número de mesa es obligatorio.');
        $this->validarRequerido($data, 'capacidad', 'La capacidad es obligatoria.');
        $this->validarCapacidad($data['capacidad']);
    }

    private function validarCapacidad($capacidad): void
    {
        if (!is_numeric($capacidad) || (int) $capacidad <= 0) {
            throw new Exception('La capacidad debe ser mayor a cero.', 400);
        }
    }

    private function validarEstado(string $estado): void
    {
        if (!in_array($estado, self::ESTADOS, true)) {
            throw new Exception(
                'Estado de mesa inválido. Valores permitidos: ' . implode(', ', self::ESTADOS) . '.',
                400
            );
        }
    }
}
