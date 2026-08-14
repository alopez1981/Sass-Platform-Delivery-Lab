# SaaS Platform Delivery Lab

Proyecto de portfolio que demuestra criterio de arquitectura y gestión de entrega en una plataforma SaaS multi-tenant, sin sobredimensionar la solución.

## Resumen ejecutivo

Una autoescuela u organización similar necesita coordinar solicitudes operativas internas (incidencias, peticiones de mantenimiento, tareas entre equipos) por organización, con trazabilidad de quién hizo qué y cuándo, y sin que una organización pueda ver los datos de otra. Este laboratorio construye una versión pequeña pero real de esa plataforma para demostrar decisiones de arquitectura, seguridad y entrega — no para venderse como producto.

## El problema

Cualquier SaaS B2B con múltiples organizaciones (tenants) enfrenta los mismos retos, independientemente de su dominio: aislar datos entre clientes, autorizar por rol dentro de cada organización, procesar trabajo en segundo plano sin bloquear la petición del usuario, y desplegar cambios con confianza sin degradar el servicio para todos los tenants a la vez. Este proyecto aborda esos retos con un caso de uso concreto: gestión de solicitudes operativas con estados, comentarios, historial y notificaciones asíncronas.

## Rol simulado

Se actúa como **Software Architect / Platform Engineer / Technical Project Manager** de un equipo pequeño: se toman y documentan decisiones de arquitectura (ADR), se define un plan de entrega por sesiones/hitos, y se gestionan riesgos y criterios de aceptación como en un proyecto real, aunque el "cliente" y los stakeholders sean simulados (ver `docs/stakeholder-map.md`, Sesión 4).

## Arquitectura

Backend (Laravel 12 API) y frontend (Vue 3 + TypeScript SPA) desacoplados, con MySQL como base de datos compartida (aislamiento multi-tenant por fila) y RabbitMQ para notificaciones asíncronas. Detalle completo, diagrama y decisiones evaluadas en:

- [`docs/architecture.md`](docs/architecture.md)
- [`docs/adr/`](docs/adr/) — registro de decisiones de arquitectura

## Cómo ejecutar el proyecto

Requisitos: Docker y Docker Compose.

```bash
cp .env.example .env
cp backend/.env.example backend/.env
cd backend && php artisan key:generate && cd ..
docker compose up -d
```

Puertos expuestos en el host (remapeados para no chocar con otros stacks Docker que puedan estar corriendo en la misma máquina — ajusta si tu entorno está libre en los puertos "por defecto"):

| Servicio | URL local |
|---|---|
| Backend API | http://localhost:8000 (health check en `/up`) |
| Frontend (Vite dev) | http://localhost:5174 |
| MySQL | localhost:3307 |
| RabbitMQ (AMQP) | localhost:5673 |
| RabbitMQ (panel de administración) | http://localhost:15673 |

## Cómo ejecutar los tests

Backend (Pest, contra SQLite en memoria — no requiere Docker):

```bash
cd backend
composer install
./vendor/bin/pest
./vendor/bin/pint --test   # estilo de código
```

Frontend (Vitest):

```bash
cd frontend
npm install
npm run test:unit
npm run lint
npm run build              # incluye type-check con vue-tsc
```

## Principales decisiones

- Backend y frontend desacoplados (API + SPA), autenticados con Laravel Sanctum en modo SPA-stateful — [ADR 0002](docs/adr/0002-decoupled-api-and-spa.md)
- Multi-tenancy por base de datos compartida con aislamiento por fila (`organization_id` + Global Scope), no por base de datos separada — [ADR 0003](docs/adr/0003-multi-tenancy-strategy.md)
- Versiones de stack elegidas según el entorno disponible, no por ser las más recientes — [ADR 0001](docs/adr/0001-stack-selection.md)

## Estado del proyecto

**Sesión 1 de 5 completada: arquitectura y estructura.**

Funciona realmente, verificado en esta máquina:
- `docker compose up -d` levanta MySQL, RabbitMQ, backend, worker de colas y frontend, todos sanos (`healthy`/`Up`).
- Backend responde en `/up` (health check de Laravel) y ejecuta sus migraciones base automáticamente al arrancar.
- Worker de colas conectado a RabbitMQ (aún sin jobs de negocio que consumir — eso es la Sesión 2).
- Frontend sirve la SPA base de Vue 3 + TypeScript vía Vite.
- Suites de test base pasan: `./vendor/bin/pest` (backend, Pest) y `npm run test:unit` (frontend, Vitest).
- Lint y build limpios en ambos proyectos (`pint --test`, `eslint`/`oxlint`, `vue-tsc --build`, `vite build`).

Pendiente / todavía simulado:
- No existen aún modelos de dominio (`Organization`, `Request`, roles, etc.) — Sesión 2.
- No hay flujo de autenticación real ni pantallas más allá del scaffold por defecto de Vue — Sesión 2.
- Tests de aislamiento multi-tenant, autorización por rol y manejo de errores — Sesión 3.
- Documentación de gestión de proyecto (charter, RACI, riesgos, SLO/SLA, runbooks) — Sesión 4, algunos ADR ya adelantados.
- Feature flags, notificaciones con reintentos/dead-letter, dashboard operativo — Sesiones 2–3.

## Limitaciones conocidas

- El contenedor del backend usa `php artisan serve` (adecuado para desarrollo, no para producción — ver `docs/architecture.md`).
- Node local es 22.12.0; el scaffold de Vue pide `^22.18.0`. Funciona con avisos no bloqueantes; se recomienda actualizar Node antes de un uso prolongado.
- Los puertos de host en `docker-compose.yml` están remapeados (3307, 5673, 15673, 5174) porque el entorno de desarrollo ya tenía otro stack Docker ocupando los puertos por defecto. Ajusta `docker-compose.yml` si tu máquina los tiene libres.

## Próximos pasos

Ver tabla de sesiones y entregables en la introducción de este proyecto (conversación de planificación) y, a partir de la Sesión 4, en `docs/milestones.md` y `docs/delivery-plan.md`.

## Documentación

- [`docs/architecture.md`](docs/architecture.md)
- [`docs/adr/`](docs/adr/)
- El resto de documentos de gestión (`docs/project-charter.md`, `docs/scope.md`, `docs/risk-register.md`, `docs/slo.md`, etc.) se añaden en la Sesión 4.
