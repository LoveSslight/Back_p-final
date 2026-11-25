<?php
declare(strict_types=1);

namespace App\Presentation\Repositories;

use App\Controllers\CategoriaController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CategoriaRepository extends AbstractRepository
{
    private CategoriaController $controller;

    public function __construct()
    {
        $this->controller = new CategoriaController();
    }

    public function listar(Request $request, Response $response): Response
    {
        return $this->responder($response, fn() => $this->controller->listar(), 'Categorías obtenidas correctamente.');
    }
}
