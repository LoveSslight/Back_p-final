<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/env.php';
cargarEnv(__DIR__ . '/../../.env');

$base = [
    'driver'    => 'mysql',
    'host'      => leerEnv('DB_HOST', '127.0.0.1'),
    'port'      => (int) leerEnv('DB_PORT', '3306'),
    'username'  => leerEnv('DB_USER', 'root'),
    'password'  => leerEnv('DB_PASS', ''),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
];

$capsule = new Capsule();

// Conexión principal: la base de datos PROPIA de este microservicio
$capsule->addConnection($base + ['database' => leerEnv('DB_NAME', 'ms_pedidos')]);
// Conexión de SOLO LECTURA a ms_auth: la usa el middleware para validar el token
$capsule->addConnection($base + ['database' => leerEnv('AUTH_DB_NAME', 'ms_auth')], 'auth');
// Conexión de SOLO LECTURA a ms_reservas: valida el estado de la mesa al crear un pedido
$capsule->addConnection($base + ['database' => leerEnv('RESERVAS_DB_NAME', 'ms_reservas')], 'reservas');

$capsule->setAsGlobal();
$capsule->bootEloquent();
