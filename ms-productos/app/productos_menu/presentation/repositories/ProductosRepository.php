<?php
declare(strict_types=1);

namespace App\Presentation\Repositories;

use App\Controllers\ProductoController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductosRepository extends AbstractRepository
{
    private ProductoController $controller;

    public function __construct()
    {
        $this->controller = new ProductoController();
    }

    public function listar(Request $request, Response $response): Response
    {
        return $this->responder($response, fn() => $this->controller->listar(), 'Productos obtenidos correctamente.');
    }

    public function listarPorCategoria(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->listarPorCategoria((int) $args['id']),
            'Productos obtenidos correctamente.'
        );
    }

    public function listarDisponibles(Request $request, Response $response): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->listarDisponibles(),
            'Productos disponibles obtenidos correctamente.'
        );
    }

    public function obtener(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->obtener((int) $args['id']),
            'Producto obtenido correctamente.'
        );
    }

    public function crear(Request $request, Response $response): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->crear($this->obtenerDatos($request)),
            'Producto creado correctamente.',
            201
        );
    }

    public function actualizar(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->actualizar((int) $args['id'], $this->obtenerDatos($request)),
            'Producto actualizado correctamente.'
        );
    }

    public function eliminar(Request $request, Response $response, array $args): Response
    {
        return $this->responder($response, function () use ($args) {
            $this->controller->eliminar((int) $args['id']);
            return null;
        }, 'Producto eliminado correctamente.');
    }
}
