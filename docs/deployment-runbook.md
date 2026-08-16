# Runbook de despliegue

> **Este runbook describe una estrategia de despliegue; no se ha ejecutado.** Se decidió deliberadamente no desplegar este proyecto (ni siquiera a un free-tier) porque, como pieza de portfolio, un repositorio que se levanta en local en dos minutos con Docker Compose demuestra el mismo criterio técnico sin el riesgo de que un despliegue gratuito se duerma, tenga cold starts, o deje de estar disponible cuando alguien lo evalúe. El objetivo de este documento es demostrar que se sabe *cómo* se haría, no hacerlo.

## Dónde se desplegaría cada pieza (opciones gratuitas evaluadas)

| Componente | Opción | Por qué esta y no otra |
|---|---|---|
| Frontend (SPA estática) | Cloudflare Pages / Netlify / Vercel | Build estático, sin servidor que mantener vivo — el único componente sin matices en su free tier |
| Backend API + `queue-worker` | Render.com (Web Service + Background Worker) | Es de los pocos con un tipo de servicio "background worker" explícito en el free tier, necesario para que el consumidor de RabbitMQ corra de forma continua |
| MySQL | Clever Cloud (free tier) | PlanetScale, la opción más conocida, retiró su plan gratuito en 2024; Clever Cloud mantiene uno funcional para una base de datos pequeña |
| RabbitMQ | CloudAMQP (plan "Little Lemur") | El único proveedor con un free tier *permanente* (no de prueba) para RabbitMQ gestionado; ~1M mensajes/mes, de sobra para una demo |

**El riesgo principal de esta combinación**: los servicios web gratuitos de Render se "duermen" tras ~15 minutos sin tráfico, y el *background worker* tiene un límite de horas/mes en el plan gratuito. Es aceptable para una demo puntual, no para un uso continuo — se documenta como limitación conocida de la estrategia, no se oculta.

## Pasos de despliegue (si se ejecutara)

1. **CI en verde**: GitHub Actions ejecuta `./vendor/bin/pest`, `pint --test`, `npm run test:unit`, `npm run lint`, `npm run build` sobre la rama a desplegar. Ninguno de los pasos siguientes ocurre si esto falla.
2. **Build de las imágenes**: `docker build` de `backend/Dockerfile` (mismo Dockerfile que en desarrollo — ver [ADR 0004](adr/0004-nginx-php-fpm-over-artisan-serve.md), la paridad dev/producción es intencional).
3. **Migraciones**: `php artisan migrate --force` contra la base de datos del entorno de destino, **antes** de enrutar tráfico a la nueva versión. Las migraciones deben ser aditivas y compatibles hacia atrás (ver `rollback-plan.md` — es la única forma de que un rollback sea seguro).
4. **Arranque**: se levantan `backend` (PHP-FPM + Nginx) y `queue-worker` con la nueva imagen, en paralelo a la versión anterior (no sustituyéndola todavía).
5. **Gate de salud**: no se enruta tráfico real a la nueva versión hasta que `GET /api/health/ready` responde `200` de forma consistente (varias comprobaciones seguidas, no una sola) — ver `docs/observability.md` para qué comprueba exactamente cada endpoint.
6. **Corte de tráfico**: solo entonces se apunta el balanceador/DNS a la nueva versión.
7. **Verificación post-despliegue**: se comprueba `GET /api/health/app` (incluye RabbitMQ) y se ejecuta un smoke test mínimo (login + listar solicitudes) contra el entorno recién desplegado.
8. **Retirada de la versión anterior**: solo después de que el paso 7 pasa y ha transcurrido una ventana de observación (ver `rollback-plan.md` para cuánto y por qué).

## Variables de entorno que cambian por entorno

Ver tabla completa en [`environment-strategy.md`](environment-strategy.md). Las que con más frecuencia se olvidan al desplegar: `APP_DEBUG` (debe ser `false`), `SANCTUM_STATEFUL_DOMAINS` / `FRONTEND_URL` (deben apuntar al dominio real de la SPA, no a `localhost`).

## Quién aprueba un despliegue a producción (simulado)

Según la matriz RACI (`stakeholder-map.md`), el Engineering Lead es responsable de ejecutarlo; el Product Owner debe aprobarlo si el cambio afecta al alcance visible; el Security Advisor debe aprobarlo explícitamente si el cambio toca autenticación, autorización, o el aislamiento multi-tenant.
