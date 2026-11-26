<?php
declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Middlewares\ValidarTokenMiddleware;
use App\Presentation\Repositories\MesaRepository;
use App\Presentation\Repositories\ReservaRepository;

return function (App $app): void {
    $mesas    = new MesaRepository();
    $reservas = new ReservaRepository();

    $app->group('/mesas', function (RouteCollectorProxy $group) use ($mesas) {
        $group->get('', [$mesas, 'listar']);
        $group->post('', [$mesas, 'crear']);
        $group->get('/{id}', [$mesas, 'obtener']);
        $group->put('/{id}', [$mesas, 'actualizar']);
        $group->patch('/{id}/estado', [$mesas, 'cambiarEstado']);
    })->add(new ValidarTokenMiddleware());

    $app->group('/reservas', function (RouteCollectorProxy $group) use ($reservas) {
        $group->get('', [$reservas, 'listar']);
        $group->post('', [$reservas, 'crear']);
        // Las rutas fijas van antes que /{id}
        $group->get('/fecha/{fecha}', [$reservas, 'listarPorFecha']);
        $group->get('/cliente/{nombre}', [$reservas, 'listarPorCliente']);
        $group->get('/estado/{estado}', [$reservas, 'listarPorEstado']);
        $group->get('/{id}', [$reservas, 'obtener']);
        $group->put('/{id}', [$reservas, 'actualizar']);
        // Cancelar acepta PATCH y POST para ser compatible con cualquier frontend
        $group->map(['PATCH', 'POST'], '/{id}/cancelar', [$reservas, 'cancelar']);
        $group->patch('/{id}/estado', [$reservas, 'cambiarEstado']);
    })->add(new ValidarTokenMiddleware());
};
