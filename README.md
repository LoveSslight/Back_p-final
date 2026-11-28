# Backend – Sistema de Reservas y Pedidos (Restaurante XYZ)

Cuatro microservicios independientes con **PHP 8.2+, Slim 4, Eloquent ORM y Composer**.
Cada uno tiene su propia base de datos MySQL (las bases se entregan ya creadas: **este proyecto no crea ni modifica tablas**).

| Microservicio | Puerto | Base de datos | Responsabilidad |
|---|---|---|---|
| ms-auth | 8001 | ms_auth | Login, logout y validación de sesión |
| ms-reservas | 8002 | ms_reservas | Mesas y reservas |
| ms-productos | 8003 | ms_productos | Menú y categorías |
| ms-pedidos | 8004 | ms_pedidos | Pedidos y sus detalles |

## Puesta en marcha

Requisitos: XAMPP con MySQL encendido, PHP 8.2+ (`php -v`) y Composer.

```bash
# En cada carpeta ms-*/
cd ms-auth
composer install
copy .env.example .env        # (Linux/Mac: cp .env.example .env) y ajusta DB_PASS si tu root tiene contraseña
php -S localhost:8001 -t app/public
```

Repite con `ms-reservas` (8002), `ms-productos` (8003) y `ms-pedidos` (8004).
Probar con los archivos `api.http` (extensión *REST Client* de VS Code).

## Convenciones

- Todas las respuestas son JSON: `{ "success": true|false, "message": "...", "data": ... }`.
- Códigos: `200` ok, `201` creado, `400` validación, `401` sin sesión, `403` usuario inactivo, `404` no existe, `500` error interno.
- Todas las rutas (excepto `/login`) exigen `Authorization: Bearer <token>`. El token se obtiene en ms-auth.
- Los microservicios **no se llaman entre sí**. Para validar el token, ms-reservas/productos/pedidos leen (solo `SELECT`) la tabla `usuarios` de `ms_auth`; ms-pedidos lee la tabla `mesas` de `ms_reservas`.

## ms-auth (8001)

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/login` | Body: `identificador` (usuario o correo), `contrasena`. Devuelve `token` y datos del usuario |
| POST | `/logout` | Invalida el token (requiere Bearer) |
| GET | `/validar` | Verifica que el token y la sesión estén activos |

## ms-reservas (8002)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/mesas` | Lista de mesas con capacidad y estado |
| GET | `/mesas/{id}` | Detalle de una mesa |
| POST | `/mesas` | Crea mesa. Body: `numero`, `capacidad`, `estado` (opcional, por defecto `disponible`) |
| PUT | `/mesas/{id}` | Edita `capacidad` y/o `estado` |
| PATCH | `/mesas/{id}/estado` | Body: `estado` = `disponible` \| `reservada` \| `ocupada` \| `fuera_servicio` |
| GET | `/reservas` | Lista todas las reservas |
| GET | `/reservas/fecha/{AAAA-MM-DD}` | Busca por fecha |
| GET | `/reservas/cliente/{nombre}` | Busca por nombre de cliente (coincidencia parcial) |
| GET | `/reservas/estado/{estado}` | `pendiente` \| `confirmada` \| `cancelada` \| `finalizada` |
| GET | `/reservas/{id}` | Detalle de una reserva |
| POST | `/reservas` | Body: `nombre_cliente`, `telefono_cliente`, `cantidad_personas`, `fecha`, `hora`, `mesa_id`, `observaciones` (opcional) |
| PUT | `/reservas/{id}` | Edita `fecha`, `hora`, `mesa_id`, `cantidad_personas`, `observaciones` |
| PATCH | `/reservas/{id}/cancelar` | Cancela la reserva (también acepta POST) |
| PATCH | `/reservas/{id}/estado` | Body: `estado` (confirmar, finalizar o cancelar) |

Validaciones: mesa duplicada, capacidad > 0, fecha no pasada, mesa fuera de servicio, capacidad máxima de la mesa y doble reserva (misma mesa, fecha y hora).

## ms-productos (8003)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/categorias` | Lista de categorías |
| GET | `/productos` | Lista de productos |
| GET | `/productos/disponibles` | Solo productos disponibles |
| GET | `/productos/categoria/{id}` | Productos de una categoría |
| GET | `/productos/{id}` | Detalle de un producto |
| POST | `/productos` | Body: `nombre`, `categoria_id`, `precio`, `disponible` (opcional), `descripcion` (opcional) |
| PUT | `/productos/{id}` | Edita nombre, categoría, precio y/o disponibilidad |
| DELETE | `/productos/{id}` | Elimina el producto |

Validaciones: precio > 0, nombre obligatorio, nombre no duplicado, categoría existente.

## ms-pedidos (8004)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/pedidos` | Lista pedidos con sus detalles |
| GET | `/pedidos/estado/{estado}` | `pendiente` \| `en_preparacion` \| `entregado` \| `pagado` \| `cancelado` |
| GET | `/pedidos/{id}` | Detalle: incluye `subtotal`, `total` y `cantidad_total` |
| POST | `/pedidos` | Body: `mesa_id`, `detalles[]` (`producto_id`, `nombre_producto`, `cantidad`, `precio_unitario`); `fecha` y `hora` opcionales |
| PATCH | `/pedidos/{id}/estado` | Body: `estado` |
| DELETE | `/pedidos/{id}` | Elimina el pedido y sus detalles |
| POST | `/pedidos/{id}/detalles` | Agrega un producto (si ya existe, suma la cantidad) |
| PUT | `/pedidos/{id}/detalles/{detalle_id}` | Modifica cantidad (y opcionalmente precio) |
| DELETE | `/pedidos/{id}/detalles/{detalle_id}` | Quita un producto (el pedido no puede quedar vacío) |

Validaciones: pedido no vacío, cantidad ≥ 1, mesa existente y **no disponible** (ni fuera de servicio). Subtotal, total y cantidad total se calculan automáticamente en el servidor. Los pedidos pagados o cancelados no se pueden modificar.
