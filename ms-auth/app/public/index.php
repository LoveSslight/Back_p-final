<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config/conexion.php';

date_default_timezone_set(leerEnv('APP_TIMEZONE', 'America/Bogota'));

$cors      = require __DIR__ . '/../middlewares/cors_middleware.php';
$endpoints = require __DIR__ . '/../autenticacion/presentation/routers/endpoints.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$errores = $app->addErrorMiddleware(
    filter_var(leerEnv('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
    true,
    true
);
// Que hasta los errores de Slim (ruta inexistente, etc.) salgan en JSON
$errores->getDefaultErrorHandler()->forceContentType('application/json');

$cors($app);
$endpoints($app);

$app->run();
