<?php
//solo lista categorías. el frontend lo necesita para llenar el selector del formulario.
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Categoria;

class CategoriaController extends AbstractController
{
    public function listar(): array
    {
        return Categoria::orderBy('id')->get()->toArray();
    }

    protected function validarDatos(array $data, bool $isUpdate = false): void
    {
        // Las categorías son solo de consulta: no hay algo que validar
    }
}
