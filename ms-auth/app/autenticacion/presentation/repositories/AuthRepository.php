<?php
declare(strict_types=1);

namespace App\Presentation\Repositories;

use App\Controllers\UsuarioController;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthRepository extends AbstractRepository
{
    private UsuarioController $controller;

    public function __construct()
    {
        $this->controller = new UsuarioController();
    }

    public function login(Request $request, Response $response): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->iniciarSesion($this->obtenerDatos($request)),
            'Inicio de sesión exitoso.'
        );
    }

    public function logout(Request $request, Response $response): Response
    {
        return $this->responder($response, function () use ($request) {
            $this->controller->cerrarSesion($this->extraerToken($request));
            return null;
        }, 'Sesión cerrada correctamente.');
    }

    public function validarSesion(Request $request, Response $response): Response
    {
        return $this->responder(
            $response,
            fn() => $this->controller->validarSesion($this->extraerToken($request)),
            'Sesión válida.'
        );
    }

    /** Lee el token del encabezado  Authorization: Bearer <token> */
    private function extraerToken(Request $request): string
    {
        $token = trim((string) preg_replace('/^Bearer\s+/i', '', $request->getHeaderLine('Authorization')));

        if ($token === '') {
            throw new Exception('Token no proporcionado.', 401);
        }

        return $token;
    }
}
