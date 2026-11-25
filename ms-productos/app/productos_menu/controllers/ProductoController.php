<?php
// valida precio mayor a cero, nombre no vacío, nombre no duplicado y que la categoría exista
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Exception;

class ProductoController extends AbstractController
{
    public function listar(): array
    {
        return Producto::with('categoria')->orderBy('id')->get()->toArray();
    }

    public function listarPorCategoria(int $categoriaId): array
    {
        return Producto::with('categoria')
            ->where('categoria_id', $categoriaId)
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    public function listarDisponibles(): array
    {
        return Producto::with('categoria')
            ->where('disponible', true)
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    public function obtener(int $id): array
    {
        $producto = Producto::with('categoria')->find($id);

        if (!$producto) {
            throw new Exception('Producto no encontrado.', 404);
        }

        return $producto->toArray();
    }

    public function crear(array $data): array
    {
        $this->validarDatos($data);
        $this->validarNombreUnico(trim((string) $data['nombre']), null);

        $producto = Producto::create([
            'nombre'       => trim((string) $data['nombre']),
            'descripcion'  => $data['descripcion'] ?? null,
            'precio'       => (float) $data['precio'],
            'disponible'   => filter_var($data['disponible'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'categoria_id' => (int) $data['categoria_id'],
        ]);

        return Producto::with('categoria')->find($producto->id)->toArray();
    }

    public function actualizar(int $id, array $data): array
    {
        $producto = Producto::find($id);

        if (!$producto) {
            throw new Exception('Producto no encontrado.', 404);
        }

        // Lo que no llegue en la petición conserva su valor actual
        $datos = array_merge(
            $producto->only(['nombre', 'descripcion', 'precio', 'disponible', 'categoria_id']),
            $data
        );

        $this->validarDatos($datos, true);
        $this->validarNombreUnico(trim((string) $datos['nombre']), $id);

        $producto->nombre       = trim((string) $datos['nombre']);
        $producto->descripcion  = $datos['descripcion'] ?? null;
        $producto->precio       = (float) $datos['precio'];
        $producto->disponible   = filter_var($datos['disponible'], FILTER_VALIDATE_BOOLEAN);
        $producto->categoria_id = (int) $datos['categoria_id'];
        $producto->save();

        return Producto::with('categoria')->find($id)->toArray();
    }

    public function eliminar(int $id): void
    {
        $producto = Producto::find($id);

        if (!$producto) {
            throw new Exception('Producto no encontrado.', 404);
        }

        $producto->delete();
    }

    protected function validarDatos(array $data, bool $isUpdate = false): void
    {
        $this->validarRequerido($data, 'nombre', 'El nombre es obligatorio.');
        $this->validarRequerido($data, 'precio', 'El precio es obligatorio.');
        $this->validarRequerido($data, 'categoria_id', 'La categoría es obligatoria.');

        if (!is_numeric($data['precio']) || (float) $data['precio'] <= 0) {
            throw new Exception('El precio debe ser un número mayor a cero.', 400);
        }

        // Evita que la llave foránea falle con un error 500
        if (!Categoria::whereKey((int) $data['categoria_id'])->exists()) {
            throw new Exception('La categoría indicada no existe.', 404);
        }
    }

    private function validarNombreUnico(string $nombre, ?int $ignorarId): void
    {
        $consulta = Producto::where('nombre', $nombre);

        if ($ignorarId !== null) {
            $consulta->where('id', '!=', $ignorarId);
        }

        if ($consulta->exists()) {
            throw new Exception('Ya existe un producto con ese nombre.', 400);
        }
    }
}
