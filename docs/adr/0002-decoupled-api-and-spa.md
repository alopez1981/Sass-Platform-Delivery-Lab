# ADR 0002: Backend API y frontend SPA desacoplados

## Estado
Aceptado — 2026-08-14

## Contexto
Había dos opciones razonables para servir la interfaz:

1. **Monolito con Inertia.js**: Laravel renderiza páginas Vue sin exponer una API REST versionada ni gestionar tokens de autenticación por separado. Desarrollo más rápido, menos piezas móviles.
2. **API + SPA desacoplada**: Laravel expone únicamente una API (autenticada con Laravel Sanctum en modo SPA), y Vue 3 + TypeScript es una aplicación cliente independiente que la consume vía HTTP.

Esta decisión se consultó explícitamente porque condiciona la autenticación, CORS, la estrategia de despliegue (¿un solo servicio o dos?) y cuánto se demuestra la separación de responsabilidades que pide el enunciado del proyecto.

## Decisión
Se elige **API + SPA desacoplada**, con Laravel Sanctum en modo *stateful SPA* (cookies + CSRF, no tokens Bearer, porque backend y frontend se sirven desde orígenes conocidos en desarrollo).

Cambios concretos ya aplicados en el backend:
- `bootstrap/app.php`: se registra `routes/api.php` y se activa `$middleware->statefulApi()`.
- `config/cors.php`: `supports_credentials = true`, origen permitido = `FRONTEND_URL` (`http://localhost:5173` en desarrollo).
- `.env`: `SANCTUM_STATEFUL_DOMAINS=localhost:5173`.

## Consecuencias
- Se requiere configurar CORS y el flujo de cookie CSRF (`/sanctum/csrf-cookie`) explícitamente — trabajo adicional que un monolito con Inertia no exigiría.
- La API queda naturalmente versionable y reutilizable por un futuro cliente distinto de la SPA (móvil, integraciones), lo cual es representativo de una arquitectura SaaS real.
- Frontend y backend pueden desplegarse, escalarse y probarse de forma independiente (dos pipelines de CI, dos Dockerfiles).
- Fuera de alcance para este laboratorio: tokens de API para clientes de terceros (machine-to-machine); solo se cubre el flujo de sesión de usuario vía SPA.
