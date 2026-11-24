<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Usuario;
use Exception;

class UsuarioController extends AbstractController
{
    public function iniciarSesion(array $data): array
    {
        $this->validarDatos($data);

        $identificador = trim((string) $data['identificador']);
        $contrasena    = trim((string) $data['contrasena']);

        // Se puede entrar con el nombre de usuario o con el correo
        $usuario = Usuario::where('usuario', $identificador)
            ->orWhere('correo', $identificador)
            ->first();

        if (!$usuario || !$this->contrasenaCorrecta($contrasena, (string) $usuario->contrasena)) {
            throw new Exception('Credenciales incorrectas.', 401);
        }

        if (!$usuario->estaActivo()) {
            throw new Exception('Usuario inactivo. Contacte al administrador.', 403);
        }

        // Token simple: con 64 caracteres hexadecimales aleatorios
        $usuario->token         = bin2hex(random_bytes(32));
        $usuario->sesion_activa = true;
        $usuario->save();

        return [
            'token'   => $usuario->token,
            'usuario' => [
                'id'      => $usuario->id,
                'nombre'  => $usuario->nombre,
                'correo'  => $usuario->correo,
                'usuario' => $usuario->usuario,
                'rol'     => $usuario->rol,
            ],
        ];
    }

    public function cerrarSesion(string $token): void
    {
        $usuario = $this->buscarPorTokenActivo($token);

        if (!$usuario) {
            throw new Exception('Token inválido o sesión ya cerrada.', 401);
        }

        $usuario->token         = null;
        $usuario->sesion_activa = false;
        $usuario->save();
    }

    public function validarSesion(string $token): array
    {
        $usuario = $this->buscarPorTokenActivo($token);

        if (!$usuario) {
            throw new Exception('Sesión inválida o expirada.', 401);
        }

        return [
            'id'     => $usuario->id,
            'nombre' => $usuario->nombre,
            'rol'    => $usuario->rol,
        ];
    }

    protected function validarDatos(array $data, bool $isUpdate = false): void
    {
        $this->validarRequerido($data, 'identificador', 'El usuario o correo es obligatorio.');
        $this->validarRequerido($data, 'contrasena', 'La contraseña es obligatoria.');
    }

    private function buscarPorTokenActivo(string $token): ?Usuario
    {
        return Usuario::where('token', $token)
            ->where('sesion_activa', true)
            ->where('estado', 'activo')
            ->first();
    }

    /**La base de datos entregada guarda las contraseñas en texto plano, por eso se compara directo.*/
    private function contrasenaCorrecta(string $ingresada, string $guardada): bool
    {
        return hash_equals($guardada, $ingresada) || password_verify($ingresada, $guardada);
    }
}
