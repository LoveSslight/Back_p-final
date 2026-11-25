<?php
declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Middlewares\ValidarTokenMiddleware;
use App\Presentation\Repositories\CategoriaRepository;
use App\Presentation\Repositories\ProductosRepository;

return function (App $app): void {
    $productos  = new ProductosRepository();
    $categorias = new CategoriaRepository();

    $app->group('/productos', function (RouteCollectorProxy $group) use ($productos) {
        $group->get('', [$productos, 'listar']);
        $group->post('', [$productos, 'crear']);
        // Las rutas fijas (/disponibles, /categoria/{id}) van antes que el /{id} para que no se confundan con un id
        $group->get('/disponibles', [$productos, 'listarDisponibles']);
        $group->get('/categoria/{id}', [$productos, 'listarPorCategoria']);
        $group->get('/{id}', [$productos, 'obtener']);
        $group->put('/{id}', [$productos, 'actualizar']);
        $group->delete('/{id}', [$productos, 'eliminar']);
    })->add(new ValidarTokenMiddleware());

    $app->group('/categorias', function (RouteCollectorProxy $group) use ($categorias) {
        $group->get('', [$categorias, 'listar']);
    })->add(new ValidarTokenMiddleware());
};
