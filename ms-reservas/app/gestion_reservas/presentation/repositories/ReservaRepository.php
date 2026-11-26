<?php
declare(strict_types=1);

namespace App\Presentation\Repositories;

use App\Controllers\ReservaController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReservaRepository extends AbstractRepository
{
    private ReservaController $controller;

    public function __construct()
    {
        $this->controller = new ReservaController();
    }

    public function listar(Request $request, Response $response): Response
    {
        return $this->responder($response, fn() => $this->controller->listar(), 'Reservas obtenidas correctamente.');
    }

    public function listarPorFecha(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->listarPorFecha($args['fecha']),
            'Reservas obtenidas correctamente.'
        );
    }

    public function listarPorCliente(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->listarPorCliente($args['nombre']),
            'Reservas obtenidas correctamente.'
        );
    }

    public function listarPorEstado(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->listarPorEstado($args['estado']),
            'Reservas obtenidas correctamente.'
        );
    }

    public function obtener(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->obtener((int) $args['id']),
            'Reserva obtenida correctamente.'
        );
    }

    public function crear(Request $request, Response $response): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->crear($this->obtenerDatos($request)),
            'Reserva creada correctamente.',
            201
        );
    }

    public function actualizar(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->actualizar((int) $args['id'], $this->obtenerDatos($request)),
            'Reserva actualizada correctamente.'
        );
    }

    public function cancelar(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->cancelar((int) $args['id']),
            'Reserva cancelada correctamente.'
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
            'Estado de la reserva actualizado correctamente.'
        );
    }
}
