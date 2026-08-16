# Observabilidad

> Este documento cubre lo que existe hoy (Sesión 3): health checks y logs estructurados. Los objetivos de fiabilidad (SLI/SLO/SLA, error budget, alertas) se documentan aparte en `docs/slo.md` (Sesión 4) — mezclar "qué medimos" con "qué deberíamos prometer" en el mismo documento invita a confundir una cosa con la otra.

## Health checks

Tres endpoints, deliberadamente distintos — no es redundancia, cada uno responde a una pregunta distinta y lo usaría un consumidor distinto:

| Endpoint | Pregunta que responde | Quién lo llama | Qué comprueba |
|---|---|---|---|
| `GET /api/health/live` | ¿Sigue vivo el proceso de PHP? | Un orquestador, para decidir si **reiniciar** el contenedor | Nada más que responder — a propósito no toca base de datos ni cola |
| `GET /api/health/ready` | ¿Puede esta instancia atender tráfico *ahora mismo*? | Un orquestador / balanceador, para decidir si **enruta** tráfico aquí | MySQL (`select 1`) y la cache (escritura+lectura real) |
| `GET /api/health/app` | ¿Está todo el sistema sano? | Un humano / un dashboard de monitorización | Lo mismo que `ready`, más RabbitMQ (profundidad real de la cola `default`) y versión de PHP/Laravel |

Por qué separarlos: un contenedor puede estar **vivo** pero **no listo** (por ejemplo, MySQL tarda 2 segundos en aceptar conexiones tras un despliegue) — si liveness y readiness fueran el mismo endpoint, un orquestador reiniciaría el contenedor innecesariamente en vez de simplemente esperar y no enviarle tráfico todavía. `app` es deliberadamente más lento y más "chismoso" (incluye RabbitMQ) — nunca debe conectarse a un probe de liveness/readiness real, solo a un dashboard.

Los tres son públicos (sin autenticación) porque quien los llama —un balanceador, un `curl` de monitorización— no tiene sesión. Ninguno expone información sensible (nunca credenciales, ni siquiera en el mensaje de error de una comprobación fallida).

## Logs estructurados con correlation ID

Cada petición HTTP pasa por `App\Http\Middleware\LogRequests`, que:

1. Reutiliza el header `X-Correlation-Id` si el cliente ya lo manda (por ejemplo, un proxy/balanceador delante de la app), o genera un UUID si no.
2. Lo añade al `Context` de Laravel — no a un log concreto, sino al contexto de la petición completa.
3. Al terminar la petición, escribe una línea `http.request` con método, ruta, código de estado y duración.
4. Devuelve el mismo ID en la respuesta (header `X-Correlation-Id`), para que quien hizo la petición pueda correlacionar su propio log con el nuestro.

El canal de log (`config/logging.php`, canal `single`) usa `Monolog\Formatter\JsonFormatter`: cada línea es JSON, no texto libre. Como el `correlation_id` vive en el `Context` de Laravel (no hay que pasarlo a mano en cada `Log::info()`), **aparece automáticamente en cualquier log que se escriba durante esa petición** — incluidos los que escriba un controlador o una excepción no capturada.

Un detalle no evidente: el `Context` de Laravel se propaga también a los **jobs despachados durante la petición** (por ejemplo, `NotifyRequestCreated`). Eso significa que, en principio, el mismo `correlation_id` de la petición HTTP que creó una `Request` podría aparecer también en los logs del `queue-worker` que procesa su notificación — permitiendo trazar un flujo de principio a fin, incluida su parte asíncrona. No se ha explotado esto más allá de que la infraestructura ya lo soporta (no se ha añadido, por ejemplo, un log explícito dentro de `NotifyRequestCreated::handle()`).

## Manejo de errores

- Toda ruta bajo `/api/*` responde siempre en JSON, incluso si el cliente no manda `Accept: application/json` (`shouldRenderJsonWhen` en `bootstrap/app.php`) — evita que un cliente mal configurado reciba una página de error HTML en vez de algo parseable.
- Forma consistente: los errores de validación (422) siempre incluyen `message` + `errors`; el resto (401/403/404/500) siempre incluyen al menos `message`.
- `/api/login` está limitado a 5 intentos por minuto por IP (`throttle:5,1`) — mitigación básica de fuerza bruta.
- Los jobs fallidos (por ejemplo, si RabbitMQ no puede procesar una notificación tras los reintentos) terminan en la tabla `failed_jobs` de Laravel — el equivalente sencillo a una dead-letter queue, visible (solo la excepción y la fecha, nunca el payload) en `GET /api/dashboard`.
- `APP_DEBUG` debe ser `false` en cualquier entorno real. Verificado en esta sesión contra el stack de Docker: con `APP_DEBUG=true` (el valor local/demo), un 404 de aislamiento multi-tenant devuelve el stack trace completo en el cuerpo de la respuesta — aceptable para depurar en local, inaceptable en producción.
