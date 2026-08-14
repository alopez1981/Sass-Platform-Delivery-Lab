# ADR 0001: Selección de stack tecnológico

## Estado
Aceptado — 2026-08-14

## Contexto
El proyecto es un portfolio técnico ("SaaS Platform Delivery Lab") que debe demostrar criterio de arquitectura sin sobredimensionar la solución. El enunciado pide PHP moderno, Laravel, Vue 3, TypeScript, MySQL, RabbitMQ, Docker Compose y CI. Antes de fijar versiones se inspeccionó el entorno local disponible:

- PHP 8.4.6 (Homebrew)
- Composer 2.8.8
- Node 22.12.0 / npm 11.4.1
- Docker 28.0.4 / Docker Compose v2.34
- Sin cliente `mysql` local (no relevante, MySQL se ejecuta en contenedor)

## Decisión
- **Backend:** Laravel 12 (última versión estable, soporta PHP 8.2–8.4, coincide con el PHP instalado sin forzar downgrade ni upgrade).
- **Frontend:** Vue 3 + TypeScript + Vite, scaffolded con `create-vue` (Vue Router, Pinia, Vitest, ESLint+Prettier). Se detectó un desajuste de peer-dependencies entre `oxlint@1.74` y `eslint-plugin-oxlint@~1.73` en la plantilla recién generada; se fijó `oxlint` a `~1.73.0` para resolverlo.
- **Node:** el proyecto pide Node `^22.18.0 || >=24.12.0` en `engines`, pero el entorno tiene 22.12.0. `npm install` funciona con avisos `EBADENGINE` no bloqueantes. Se documenta como limitación conocida; se recomienda actualizar Node antes de un uso prolongado, pero no bloquea el desarrollo de este laboratorio.
- **Base de datos:** MySQL 8.4 en contenedor Docker (no se usa el SQLite por defecto del instalador de Laravel salvo en tests, donde SQLite en memoria es intencional por velocidad).
- **Cola de mensajes:** RabbitMQ 3.13 (imagen `management` para incluir la UI de administración en `:15672`), integrado vía `vladimir-yuldashev/laravel-queue-rabbitmq` (usa `php-amqplib`, implementación pura en PHP — no requiere la extensión nativa `ext-amqp`, lo que simplifica la imagen Docker).
- **Testing:** Pest 3 sobre PHPUnit para el backend (estilo funcional, más legible para un portfolio); Vitest para el frontend (ya incluido por `create-vue`).
- **Orquestación local:** Docker Compose, sin Kubernetes (fuera de alcance explícito).

## Consecuencias
- El repo queda como monorepo con dos aplicaciones independientes (`backend/`, `frontend/`), unidas solo por HTTP/API y por `docker-compose.yml` en la raíz.
- Al no forzar versiones "más nuevas a toda costa", el entorno es reproducible en la máquina donde se desarrolló sin necesidad de gestores de versión adicionales (asdf, nvm) para poder arrancar, aunque se recomienda Node ≥22.18 a futuro.
- Usar `php-amqplib` en vez de `ext-amqp` evita compilar extensiones nativas en el Dockerfile del backend, a costa de un rendimiento ligeramente menor (aceptable para el volumen de este laboratorio).
