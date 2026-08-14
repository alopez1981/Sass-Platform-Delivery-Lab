# ADR 0004: Nginx + PHP-FPM en vez de `php artisan serve`

## Estado
Aceptado — 2026-08-14

## Contexto
El `Dockerfile` del backend (Sesión 1) usaba `php artisan serve`, el servidor de desarrollo integrado de PHP, por simplicidad: un único proceso, sin piezas adicionales en el `docker-compose.yml`. Se documentó explícitamente como una limitación consciente ("adecuado para desarrollo, no para producción").

Al verificar el primer flujo vertical funcional contra un navegador real (Sesión 2), las peticiones desde la SPA fallaban de forma intermitente con errores de red / CORS aparentemente contradictorios: `curl` contra los mismos endpoints funcionaba siempre, pero el navegador fallaba con `net::ERR_FAILED` en peticiones GET normales, incluso en endpoints públicos. Aumentar `PHP_CLI_SERVER_WORKERS` (paralelismo del servidor integrado) no lo solucionó.

## Decisión
Sustituir `php artisan serve` por **Nginx como proxy inverso + PHP-FPM** como procesador de PHP:

- `backend`: mismo Dockerfile (ahora basado en `php:8.4-fpm` en vez de `php:8.4-cli`), ejecuta `php-fpm` en primer plano.
- `webserver` (nuevo servicio): `nginx:1.27-alpine`, sirve `public/` y reenvía `.php` a `backend:9000` vía FastCGI. Expone el puerto 8000 al host (antes lo exponía `backend` directamente).
- Nginx resuelve el hostname `backend` dinámicamente (`resolver 127.0.0.11` — DNS embebido de Docker — combinado con una variable en `fastcgi_pass`) en vez de cachear la IP una sola vez al arrancar, para no quedar apuntando a una IP muerta si `backend` se reinicia de forma independiente.

## Alternativas consideradas
- **Mantener `php artisan serve` pero investigar más a fondo el error de red:** descartado tras confirmar que el propio navegador (probado con `fetch()` nativo sin la aplicación de por medio) fallaba incluso contra un endpoint público sin CORS ni credenciales — el servidor integrado de PHP es conocido por no manejar bien conexiones keep-alive/concurrentes de navegadores reales, a diferencia de clientes simples como `curl`.
- **FrankenPHP o similar (servidor de aplicación moderno todo-en-uno):** descartado por añadir una dependencia menos estándar/documentada para un laboratorio cuyo objetivo es demostrar el patrón más común en producción real (Nginx + PHP-FPM), no explorar alternativas exóticas.

## Consecuencias
- Una pieza más en `docker-compose.yml` (`webserver`), pero es exactamente el patrón que se usaría en un despliegue real — no es complejidad artificial, es quitar una simplificación que resultó no aguantar tráfico de navegador real.
- Se añadió logging explícito de errores de PHP-FPM (`docker/php-fpm-logging.conf`), porque por defecto PHP-FPM descarta la salida de sus workers y cualquier error fatal se convierte en un 500 sin rastro en los logs — así fue como se diagnosticó el bug de recursión infinita descrito en la enmienda del ADR 0003.
- `docs/architecture.md` y el README quedan actualizados para reflejar el nuevo componente y el puerto que expone.
