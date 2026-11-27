<?php
declare(strict_types=1);

namespace App\Presentation\Repositories;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class AbstractRepository
{
    private const CODIGOS_HTTP = [400, 401, 403, 404];

    protected function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * Ejecuta la acción del controlador y arma la respuesta estándar
     * { success, message, data }. Centraliza el try/catch para no repetirlo.
     */
    protected function responder(Response $response, callable $accion, string $mensaje, int $status = 200): Response
    {
        try {
            $resultado = $accion();

            $cuerpo = ['success' => true, 'message' => $mensaje];
            if ($resultado !== null) {
                $cuerpo['data'] = $resultado;
            }

            return $this->json($response, $cuerpo, $status);
        } catch (Exception $e) {
            return $this->jsonError($response, $e);
        }
    }

    protected function jsonError(Response $response, Exception $exception): Response
    {
        $codigo = (int) $exception->getCode();

        if (in_array($codigo, self::CODIGOS_HTTP, true)) {
            return $this->json($response, [
                'success' => false,
                'message' => $exception->getMessage(),
            ], $codigo);
        }

        // Error inesperado (como de la BD): se registra y no se expone al cliente
        error_log($exception->getMessage());

        return $this->json($response, [
            'success' => false,
            'message' => 'Error interno del servidor.',
        ], 500);
    }

    protected function obtenerDatos(Request $request): array
    {
        $data = $request->getParsedBody();

        if (is_array($data)) {
            return $data;
        }

        $decoded = json_decode((string) $request->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }
}
