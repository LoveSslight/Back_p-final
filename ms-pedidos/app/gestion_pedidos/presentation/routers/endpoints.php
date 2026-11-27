<?php
declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Middlewares\ValidarTokenMiddleware;
use App\Presentation\Repositories\PedidosRepository;

return function (App $app): void {
    $pedidos = new PedidosRepository();

    $app->group('/pedidos', function (RouteCollectorProxy $group) use ($pedidos) {
        $group->get('', [$pedidos, 'listar']);
        $group->post('', [$pedidos, 'crear']);
        // Las rutas fijas van antes que /{id] (para que no se confunda id)
        $group->get('/estado/{estado}', [$pedidos, 'listarPorEstado']);
        $group->get('/{id}', [$pedidos, 'obtener']);
        $group->patch('/{id}/estado', [$pedidos, 'actualizarEstado']);
        $group->delete('/{id}', [$pedidos, 'eliminar']);

        $group->post('/{id}/detalles', [$pedidos, 'agregarDetalle']);
        $group->put('/{id}/detalles/{detalle_id}', [$pedidos, 'actualizarDetalle']);
        $group->delete('/{id}/detalles/{detalle_id}', [$pedidos, 'eliminarDetalle']);
    })->add(new ValidarTokenMiddleware());
};
