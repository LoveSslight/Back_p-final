<?php
declare(strict_types=1);

namespace App\Middlewares;

use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Protege las rutas: exige un token válido y una sesión activa.
 * No llama a ms-auth por HTTP (el enunciado prohíbe la comunicación entre
 * microservicios); consulta en modo lectura la tabla usuarios de ms_auth.
 */
class ValidarTokenMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        $token = trim((string) preg_replace('/^Bearer\s+/i', '', $request->getHeaderLine('Authorization')));

        if ($token === '') {
            return $this->rechazar('Token no proporcionado.');
        }

        $usuario = Capsule::connection('auth')
            ->table('usuarios')
            ->where('token', $token)
            ->where('sesion_activa', 1)
            ->where('estado', 'activo')
            ->first();

        if (!$usuario) {
            return $this->rechazar('Sesión inválida o expirada.');
        }

        return $handler->handle($request->withAttribute('usuario', $usuario));
    }

    private function rechazar(string $mensaje): Response
    {
        $respuesta = new SlimResponse(401);
        $respuesta->getBody()->write(json_encode(
            ['success' => false, 'message' => $mensaje],
            JSON_UNESCAPED_UNICODE
        ));

        return $respuesta->withHeader('Content-Type', 'application/json');
    }
}
