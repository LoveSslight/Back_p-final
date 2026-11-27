<?php
declare(strict_types=1);

function cargarEnv(string $ruta): void
{
    if (!is_file($ruta)) {
        return;
    }

    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);

        if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
            continue;
        }

        [$clave, $valor] = explode('=', $linea, 2);
        $_ENV[trim($clave)] = trim($valor, " \t\"'");
    }
}

function leerEnv(string $clave, string $porDefecto = ''): string
{
    return $_ENV[$clave] ?? $porDefecto;
}
