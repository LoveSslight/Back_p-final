<?php
declare(strict_types=1);

namespace App\Presentation\Repositories;

use App\Controllers\MesaController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MesaRepository extends AbstractRepository
{
    private MesaController $controller;

    public function __construct()
    {
        $this->controller = new MesaController();
    }

    public function listar(Request $request, Response $response): Response
    {
        return $this->responder($response, fn() => $this->controller->listar(), 'Mesas obtenidas correctamente.');
    }

    public function obtener(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->obtener((int) $args['id']),
            'Mesa obtenida correctamente.'
        );
    }

    public function crear(Request $request, Response $response): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->crear($this->obtenerDatos($request)),
            'Mesa creada correctamente.',
            201
        );
    }

    public function actualizar(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->actualizar((int) $args['id'], $this->obtenerDatos($request)),
            'Mesa actualizada correctamente.'
        );
    }

    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->cambiarEstado(
                (int) $args['id'],
                (string) ($this->obtenerDatos($request)['estado'] ?? '')
            ),
            'Estado de mesa actualizado correctamente.'
        );
    }
}
