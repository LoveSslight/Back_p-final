<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\DetallePedido;
use App\Models\Pedido;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;

class PedidoController extends AbstractController
{
    private const ESTADOS         = ['pendiente', 'en_preparacion', 'entregado', 'pagado', 'cancelado'];
    private const ESTADOS_CERRADOS = ['pagado', 'cancelado'];

    // - Consultas -

    public function listar(): array
    {
        return Pedido::with('detalles')->orderByDesc('id')->get()->toArray();
    }

    public function listarPorEstado(string $estado): array
    {
        $this->validarEstado($estado);

        return Pedido::with('detalles')->where('estado', $estado)->orderByDesc('id')->get()->toArray();
    }

    public function obtener(int $id): array
    {
        return $this->buscar($id)->toArray();
    }

    // - Pedido -

    public function crear(array $data): array
    {
        $this->validarDatos($data);

        $fecha = isset($data['fecha']) && trim((string) $data['fecha']) !== ''
            ? trim((string) $data['fecha'])
            : date('Y-m-d');
        $hora = isset($data['hora']) && trim((string) $data['hora']) !== ''
            ? $this->normalizarHora(trim((string) $data['hora']))
            : date('H:i:s');
        $this->validarFecha($fecha);

        $this->validarMesaParaPedido((int) $data['mesa_id']);
        $detalles = $this->validarDetalles($data['detalles'] ?? null);

        // Transacción: o se guarda el pedido completo o no se guarda nada
        $id = Capsule::connection()->transaction(function () use ($data, $fecha, $hora, $detalles) {
            $pedido = Pedido::create([
                'mesa_id'  => (int) $data['mesa_id'],
                'fecha'    => $fecha,
                'hora'     => $hora,
                'subtotal' => 0,
                'total'    => 0,
                'estado'   => 'pendiente',
            ]);

            foreach ($detalles as $detalle) {
                DetallePedido::create($detalle + ['pedido_id' => $pedido->id]);
            }

            $this->recalcularTotales($pedido);

            return $pedido->id;
        });

        return $this->buscar((int) $id)->toArray();
    }

    public function actualizarEstado(int $id, array $data): array
    {
        $pedido = Pedido::find($id);

        if (!$pedido) {
            throw new Exception('Pedido no encontrado.', 404);
        }

        $this->validarRequerido($data, 'estado', 'El estado es obligatorio.');
        $estado = trim((string) $data['estado']);
        $this->validarEstado($estado);

        if (in_array($pedido->estado, self::ESTADOS_CERRADOS, true) && $estado !== $pedido->estado) {
            throw new Exception('Un pedido pagado o cancelado ya no puede cambiar de estado.', 400);
        }

        $pedido->estado = $estado;
        $pedido->save();

        return $this->buscar($id)->toArray();
    }

    public function eliminar(int $id): void
    {
        $pedido = Pedido::find($id);

        if (!$pedido) {
            throw new Exception('Pedido no encontrado.', 404);
        }

        Capsule::connection()->transaction(function () use ($pedido) {
            DetallePedido::where('pedido_id', $pedido->id)->delete();
            $pedido->delete();
        });
    }

    // - Detalles (de productos del pedido) -

    public function agregarDetalle(int $pedidoId, array $data): array
    {
        $pedido = $this->buscar($pedidoId);
        $this->asegurarModificable($pedido);

        $nuevo = $this->validarDetalle($data);

        // Si el producto ya está en el pedido, se suma la cantidad en vez de duplicar la línea
        $existente = DetallePedido::where('pedido_id', $pedidoId)
            ->where('producto_id', $nuevo['producto_id'])
            ->first();

        if ($existente) {
            $existente->cantidad        += $nuevo['cantidad'];
            $existente->precio_unitario  = $nuevo['precio_unitario'];
            $existente->subtotal         = round($existente->cantidad * $existente->precio_unitario, 2);
            $existente->save();
        } else {
            DetallePedido::create($nuevo + ['pedido_id' => $pedidoId]);
        }

        $this->recalcularTotales($pedido);

        return $this->buscar($pedidoId)->toArray();
    }

    public function actualizarDetalle(int $pedidoId, int $detalleId, array $data): array
    {
        $pedido = $this->buscar($pedidoId);
        $this->asegurarModificable($pedido);

        $detalle = $this->buscarDetalle($pedidoId, $detalleId);

        $this->validarCantidad($data['cantidad'] ?? null);

        $precio = $data['precio_unitario'] ?? $detalle->precio_unitario;
        $this->validarPrecio($precio);

        $detalle->cantidad        = (int) $data['cantidad'];
        $detalle->precio_unitario = (float) $precio;
        $detalle->subtotal        = round((float) $precio * (int) $data['cantidad'], 2);
        $detalle->save();

        $this->recalcularTotales($pedido);

        return $this->buscar($pedidoId)->toArray();
    }

    public function eliminarDetalle(int $pedidoId, int $detalleId): array
    {
        $pedido = $this->buscar($pedidoId);
        $this->asegurarModificable($pedido);

        $detalle = $this->buscarDetalle($pedidoId, $detalleId);

        // No se deja que se permitan pedidos vacíos
        if ($pedido->detalles->count() <= 1) {
            throw new Exception('No se puede eliminar el único producto del pedido.', 400);
        }

        $detalle->delete();
        $this->recalcularTotales($pedido);

        return $this->buscar($pedidoId)->toArray();
    }

    protected function validarDatos(array $data, bool $isUpdate = false): void
    {
        $this->validarRequerido($data, 'mesa_id', 'La mesa es obligatoria.');
    }

    // - Validaciones -

    /**Lee (solo lee) la tabla mesas de ms_reservas, para que los microservicios se llamen entre sí. No se permiten pedidos para mesas disponibles.*/
    private function validarMesaParaPedido(int $mesaId): void
    {
        $mesa = Capsule::connection('reservas')->table('mesas')->where('id', $mesaId)->first();

        if (!$mesa) {
            throw new Exception('La mesa indicada no existe.', 404);
        }

        if ($mesa->estado === 'disponible') {
            throw new Exception('No se pueden registrar pedidos para una mesa disponible.', 400);
        }

        if ($mesa->estado === 'fuera_servicio') {
            throw new Exception('No se pueden registrar pedidos para una mesa fuera de servicio.', 400);
        }
    }

    private function validarDetalles($detalles): array
    {
        if (!is_array($detalles) || count($detalles) === 0) {
            throw new Exception('El pedido debe tener al menos un producto.', 400);
        }

        $limpios = [];
        foreach ($detalles as $detalle) {
            $limpios[] = $this->validarDetalle(is_array($detalle) ? $detalle : []);
        }

        return $limpios;
    }

    /** Valida un producto del pedido y lo devuelve listo para guardar (con su subtotal). */
    private function validarDetalle(array $detalle): array
    {
        $this->validarRequerido($detalle, 'producto_id', 'El producto es obligatorio.');
        $this->validarRequerido($detalle, 'nombre_producto', 'El nombre del producto es obligatorio.');
        $this->validarCantidad($detalle['cantidad'] ?? null);
        $this->validarPrecio($detalle['precio_unitario'] ?? null);

        $cantidad = (int) $detalle['cantidad'];
        $precio   = (float) $detalle['precio_unitario'];

        return [
            'producto_id'     => (int) $detalle['producto_id'],
            'nombre_producto' => trim((string) $detalle['nombre_producto']),
            'cantidad'        => $cantidad,
            'precio_unitario' => $precio,
            'subtotal'        => round($precio * $cantidad, 2),
        ];
    }

    private function validarCantidad($cantidad): void
    {
        if (!is_numeric($cantidad) || (int) $cantidad < 1) {
            throw new Exception('La cantidad debe ser mayor o igual a uno.', 400);
        }
    }

    private function validarPrecio($precio): void
    {
        if (!is_numeric($precio) || (float) $precio <= 0) {
            throw new Exception('El precio unitario debe ser mayor a cero.', 400);
        }
    }

    private function validarEstado(string $estado): void
    {
        if (!in_array($estado, self::ESTADOS, true)) {
            throw new Exception(
                'Estado de pedido inválido. Valores permitidos: ' . implode(', ', self::ESTADOS) . '.',
                400
            );
        }
    }

    private function asegurarModificable(Pedido $pedido): void
    {
        if (in_array($pedido->estado, self::ESTADOS_CERRADOS, true)) {
            throw new Exception('No se puede modificar un pedido pagado o cancelado.', 400);
        }
    }

    // - Utilidades -

    private function buscar(int $id): Pedido
    {
        $pedido = Pedido::with('detalles')->find($id);

        if (!$pedido) {
            throw new Exception('Pedido no encontrado.', 404);
        }

        return $pedido;
    }

    private function buscarDetalle(int $pedidoId, int $detalleId): DetallePedido
    {
        $detalle = DetallePedido::where('id', $detalleId)->where('pedido_id', $pedidoId)->first();

        if (!$detalle) {
            throw new Exception('Detalle no encontrado.', 404);
        }

        return $detalle;
    }

    /** Subtotal y total salen de sumar los detalles; nunca se confía en un total enviado por el cliente. */
    private function recalcularTotales(Pedido $pedido): void
    {
        $subtotal = (float) DetallePedido::where('pedido_id', $pedido->id)->sum('subtotal');

        $pedido->subtotal = $subtotal;
        $pedido->total    = $subtotal;
        $pedido->save();
    }
}
