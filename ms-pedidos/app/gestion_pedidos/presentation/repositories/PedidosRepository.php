<?php
declare(strict_types=1);

namespace App\Presentation\Repositories;

use App\Controllers\PedidoController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PedidosRepository extends AbstractRepository
{
    private PedidoController $controller;

    public function __construct()
    {
        $this->controller = new PedidoController();
    }

    public function listar(Request $request, Response $response): Response
    {
        return $this->responder($response, fn() => $this->controller->listar(), 'Pedidos obtenidos correctamente.');
    }

    public function listarPorEstado(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->listarPorEstado($args['estado']),
            'Pedidos obtenidos correctamente.'
        );
    }

    public function obtener(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->obtener((int) $args['id']),
            'Pedido obtenido correctamente.'
        );
    }

    public function crear(Request $request, Response $response): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->crear($this->obtenerDatos($request)),
            'Pedido creado correctamente.',
            201
        );
    }

    public function actualizarEstado(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->actualizarEstado((int) $args['id'], $this->obtenerDatos($request)),
            'Estado del pedido actualizado correctamente.'
        );
    }

    public function eliminar(Request $request, Response $response, array $args): Response
    {
        return $this->responder($response, function () use ($args) {
            $this->controller->eliminar((int) $args['id']);
            return null;
        }, 'Pedido eliminado correctamente.');
    }

    public function agregarDetalle(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->agregarDetalle((int) $args['id'], $this->obtenerDatos($request)),
            'Producto agregado al pedido correctamente.',
            201
        );
    }

    public function actualizarDetalle(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->actualizarDetalle(
                (int) $args['id'],
                (int) $args['detalle_id'],
                $this->obtenerDatos($request)
            ),
            'Detalle actualizado correctamente.'
        );
    }

    public function eliminarDetalle(Request $request, Response $response, array $args): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->eliminarDetalle((int) $args['id'], (int) $args['detalle_id']),
            'Producto eliminado del pedido correctamente.'
        );
    }
}
