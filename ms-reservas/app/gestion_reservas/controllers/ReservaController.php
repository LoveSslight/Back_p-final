<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Mesa;
use App\Models\Reserva;
use Exception;

class ReservaController extends AbstractController
{
    private const ESTADOS         = ['pendiente', 'confirmada', 'cancelada', 'finalizada'];
    private const ESTADOS_ACTIVOS = ['pendiente', 'confirmada'];

    // ---------- Consultas ----------

    public function listar(): array
    {
        return $this->consulta()->get()->toArray();
    }

    public function listarPorFecha(string $fecha): array
    {
        $this->validarFecha($fecha);

        return $this->consulta()->where('fecha', $fecha)->get()->toArray();
    }

    public function listarPorCliente(string $nombre): array
    {
        return $this->consulta()
            ->where('nombre_cliente', 'like', '%' . $nombre . '%')
            ->get()
            ->toArray();
    }

    public function listarPorEstado(string $estado): array
    {
        $this->validarEstado($estado);

        return $this->consulta()->where('estado', $estado)->get()->toArray();
    }

    public function obtener(int $id): array
    {
        $reserva = Reserva::with('mesa')->find($id);

        if (!$reserva) {
            throw new Exception('Reserva no encontrada.', 404);
        }

        return $reserva->toArray();
    }

    // ---------- Escritura ----------

    public function crear(array $data): array
    {
        $this->validarDatos($data);

        $fecha    = trim((string) $data['fecha']);
        $hora     = $this->normalizarHora(trim((string) $data['hora']));
        $personas = (int) $data['cantidad_personas'];

        $this->validarFechaNoPasada($fecha);
        $mesa = $this->validarMesaParaReserva((int) $data['mesa_id'], $personas, $fecha, $hora, null);

        $reserva = Reserva::create([
            'nombre_cliente'    => trim((string) $data['nombre_cliente']),
            'telefono_cliente'  => trim((string) $data['telefono_cliente']),
            'cantidad_personas' => $personas,
            'fecha'             => $fecha,
            'hora'              => $hora,
            'observaciones'     => $data['observaciones'] ?? null,
            'estado'            => 'pendiente',
            'mesa_id'           => $mesa->id,
        ]);

        $this->marcarMesaReservada($mesa);

        return Reserva::with('mesa')->find($reserva->id)->toArray();
    }

    public function actualizar(int $id, array $data): array
    {
        $reserva = Reserva::find($id);

        if (!$reserva) {
            throw new Exception('Reserva no encontrada.', 404);
        }

        if (!in_array($reserva->estado, self::ESTADOS_ACTIVOS, true)) {
            throw new Exception('No se puede editar una reserva cancelada o finalizada.', 400);
        }

        // Solo estos campos son editables; lo que no llegue conserva su valor actual
        $editables = [
            'nombre_cliente', 'telefono_cliente', 'fecha', 'hora',
            'mesa_id', 'cantidad_personas', 'observaciones',
        ];
        $datos = array_merge(
            $reserva->only($editables),
            array_intersect_key($data, array_flip($editables))
        );

        $this->validarDatos($datos, true);

        // El formulario de edición también puede cambiar el estado; se valida antes de guardar nada
        $nuevoEstado = isset($data['estado']) && trim((string) $data['estado']) !== ''
            ? trim((string) $data['estado'])
            : $reserva->estado;
        $this->validarEstado($nuevoEstado);

        $fecha    = trim((string) $datos['fecha']);
        $hora     = $this->normalizarHora(trim((string) $datos['hora']));
        $personas = (int) $datos['cantidad_personas'];
        $mesaId   = (int) $datos['mesa_id'];

        if ($fecha !== $reserva->fecha) {
            $this->validarFechaNoPasada($fecha);
        }

        $mesa         = $this->validarMesaParaReserva($mesaId, $personas, $fecha, $hora, $id);
        $mesaAnterior = (int) $reserva->mesa_id;

        $reserva->nombre_cliente    = trim((string) $datos['nombre_cliente']);
        $reserva->telefono_cliente  = trim((string) $datos['telefono_cliente']);
        $reserva->fecha             = $fecha;
        $reserva->hora              = $hora;
        $reserva->mesa_id           = $mesaId;
        $reserva->cantidad_personas = $personas;
        $reserva->observaciones     = $datos['observaciones'] ?? null;
        $reserva->save();

        if ($mesaAnterior !== $mesaId) {
            $this->liberarMesaSiCorresponde($mesaAnterior);
        }
        $this->marcarMesaReservada($mesa);

        // Si cambió el estado (confirmar, finalizar, cancelar), se aplica y se sincroniza la mesa
        if ($nuevoEstado !== $reserva->estado) {
            return $this->aplicarEstado($reserva, $nuevoEstado);
        }

        return Reserva::with('mesa')->find($id)->toArray();
    }

    public function cancelar(int $id): array
    {
        $reserva = Reserva::find($id);

        if (!$reserva) {
            throw new Exception('Reserva no encontrada.', 404);
        }

        if ($reserva->estaCancelada()) {
            throw new Exception('La reserva ya está cancelada.', 400);
        }

        if ($reserva->estado === 'finalizada') {
            throw new Exception('No se puede cancelar una reserva finalizada.', 400);
        }

        return $this->aplicarEstado($reserva, 'cancelada');
    }

    /** Permite pasar la reserva a confirmada o finalizada (o cancelarla). */
    public function cambiarEstado(int $id, string $estado): array
    {
        $this->validarEstado($estado);

        $reserva = Reserva::find($id);

        if (!$reserva) {
            throw new Exception('Reserva no encontrada.', 404);
        }

        if (!in_array($reserva->estado, self::ESTADOS_ACTIVOS, true)) {
            throw new Exception('No se puede cambiar el estado de una reserva cancelada o finalizada.', 400);
        }

        return $this->aplicarEstado($reserva, $estado);
    }

    protected function validarDatos(array $data, bool $isUpdate = false): void
    {
        $this->validarRequerido($data, 'nombre_cliente', 'El nombre del cliente es obligatorio.');
        $this->validarRequerido($data, 'telefono_cliente', 'El teléfono es obligatorio.');

        $this->validarRequerido($data, 'cantidad_personas', 'La cantidad de personas es obligatoria.');
        $this->validarRequerido($data, 'fecha', 'La fecha es obligatoria.');
        $this->validarRequerido($data, 'hora', 'La hora es obligatoria.');
        $this->validarRequerido($data, 'mesa_id', 'La mesa es obligatoria.');

        if (!ctype_digit((string) $data['cantidad_personas']) || (int) $data['cantidad_personas'] < 1) {
            throw new Exception('La cantidad de personas debe ser un número entero mayor a cero.', 400);
        }

        $this->validarFecha(trim((string) $data['fecha']));
    }

    // ---------- Reglas de negocio ----------

    private function validarFechaNoPasada(string $fecha): void
    {
        if ($fecha < date('Y-m-d')) {
            throw new Exception('No se permiten reservas en fechas pasadas.', 400);
        }
    }

    /**
     * Valida que la mesa exista, esté en servicio, tenga capacidad suficiente
     * y no tenga ya otra reserva activa en la misma fecha y hora.
     */
    private function validarMesaParaReserva(int $mesaId, int $personas, string $fecha, string $hora, ?int $ignorarReservaId): Mesa
    {
        $mesa = Mesa::find($mesaId);

        if (!$mesa) {
            throw new Exception('Mesa no encontrada.', 404);
        }

        if ($mesa->estaFueraDeServicio()) {
            throw new Exception('No se puede reservar una mesa fuera de servicio.', 400);
        }

        if ($personas > $mesa->capacidad) {
            throw new Exception('La cantidad de personas supera la capacidad de la mesa.', 400);
        }

        $dobleReserva = Reserva::where('mesa_id', $mesaId)
            ->where('fecha', $fecha)
            ->where('hora', $hora)
            ->whereIn('estado', self::ESTADOS_ACTIVOS);

        if ($ignorarReservaId !== null) {
            $dobleReserva->where('id', '!=', $ignorarReservaId);
        }

        if ($dobleReserva->exists()) {
            throw new Exception('Ya existe una reserva para esa mesa en esa fecha y hora.', 400);
        }

        return $mesa;
    }

    private function validarEstado(string $estado): void
    {
        if (!in_array($estado, self::ESTADOS, true)) {
            throw new Exception(
                'Estado de reserva inválido. Valores permitidos: ' . implode(', ', self::ESTADOS) . '.',
                400
            );
        }
    }

    private function aplicarEstado(Reserva $reserva, string $estado): array
    {
        $reserva->estado = $estado;
        $reserva->save();

        if (!in_array($estado, self::ESTADOS_ACTIVOS, true)) {
            $this->liberarMesaSiCorresponde((int) $reserva->mesa_id);
        }

        return Reserva::with('mesa')->find($reserva->id)->toArray();
    }

    // - Sincronización del estado de la mesa -

    private function marcarMesaReservada(Mesa $mesa): void
    {
        if ($mesa->estado === 'disponible') {
            $mesa->estado = 'reservada';
            $mesa->save();
        }
    }

    /** La mesa vuelve a "disponible" solo si estaba "reservada" y ya no tiene reservas activas. */
    private function liberarMesaSiCorresponde(int $mesaId): void
    {
        $mesa = Mesa::find($mesaId);

        if (!$mesa || $mesa->estado !== 'reservada') {
            return;
        }

        $hayActivas = Reserva::where('mesa_id', $mesaId)
            ->whereIn('estado', self::ESTADOS_ACTIVOS)
            ->exists();

        if (!$hayActivas) {
            $mesa->estado = 'disponible';
            $mesa->save();
        }
    }

    private function consulta()
    {
        return Reserva::with('mesa')->orderBy('fecha')->orderBy('hora');
    }
}
