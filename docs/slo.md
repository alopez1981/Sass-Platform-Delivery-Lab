# Fiabilidad: SLI, SLO, SLA, error budget

> Este proyecto **no está desplegado** (ver `project-charter.md` y `deployment-runbook.md`). Todo lo que hay aquí distingue explícitamente tres categorías, para no atribuir a esta aplicación una fiabilidad que no se ha medido nunca en producción:
>
> - **Objetivo definido**: la meta que se fijaría, y por qué ese número y no otro.
> - **Simulado/ilustrativo**: un ejemplo numérico para hacer el objetivo concreto — no es una medición.
> - **Observado realmente**: lo único verificado de verdad, con su alcance exacto (spoiler: es poco, y se dice explícitamente qué es y qué no es).

## SLI de disponibilidad (objetivo definido)

**Definición**: porcentaje de peticiones a los endpoints críticos (`/api/login`, `/api/requests`, `/api/health/ready`) que devuelven un código distinto de 5xx dentro de un timeout de 2s, sobre una ventana móvil de 30 días.

Se excluyen deliberadamente `/api/health/live` (nunca debería contar como "petición de negocio") y `/api/dashboard` (uso interno, no crítico para el flujo principal).

## SLI de latencia (objetivo definido)

**Definición**: percentil 95 (p95) del tiempo de respuesta de las peticiones de lectura (`GET /api/requests`, `GET /api/requests/{id}`) y de escritura (`POST /api/requests`, `PATCH .../status`), medido en el servidor (no incluye latencia de red del cliente).

## SLO mensual (simulado/ilustrativo)

| SLI | Objetivo | Qué significa en la práctica |
|---|---|---|
| Disponibilidad | 99.5% mensual | ≈ 3h 39min de indisponibilidad permitida al mes |
| Latencia de lectura (p95) | < 300 ms | Listar o ver una solicitud se siente instantáneo |
| Latencia de escritura (p95) | < 500 ms | Incluye la validación + el dispatch del job a RabbitMQ (no espera a que se consuma) |

Estos números son un punto de partida razonable para un SaaS B2B pequeño, no una medición — se elegirían de verdad tras tener tráfico real que observar durante al menos un ciclo mensual.

## SLA ficticio (ejemplo, nunca prometido a nadie real)

> **Esto es un ejemplo de cómo se vería un SLA comercial, no un compromiso real.** No hay clientes, no hay contrato, no hay créditos de servicio de verdad.

*"El proveedor garantiza una disponibilidad mensual del 99% para el plan estándar de SaaS Platform Delivery Lab. Por debajo del 99%, el cliente recibe un crédito del 10% sobre la cuota mensual; por debajo del 95%, un crédito del 25%."*

El SLA (99%) es deliberadamente más laxo que el SLO interno (99.5%) — es una práctica estándar: el margen entre ambos es el colchón para no incumplir de cara al cliente por variaciones normales de operación.

## Error budget (objetivo definido + cálculo ilustrativo)

Con un SLO de 99.5% sobre 30 días (43.200 minutos):

```
Error budget = 43.200 min × (1 − 0.995) = 216 minutos/mes
```

**Política de uso del error budget** (objetivo definido): si el budget se agota antes de fin de mes, se congelan los despliegues no relacionados con fiabilidad hasta el siguiente ciclo — un despliegue nuevo es, por definición, el momento de mayor riesgo de consumir más budget.

## MTTR objetivo

| Severidad | MTTR objetivo | Ejemplo |
|---|---|---|
| Crítica (sospecha de fuga de datos entre organizaciones) | 15 minutos | Se trata como el peor escenario posible, no como "uno más" — ver `risk-register.md`, R1 |
| Alta (indisponibilidad general) | 30 minutos | Ver `rollback-plan.md` |
| Media (degradación parcial, ej. notificaciones retrasadas) | 2 horas | El flujo síncrono sigue funcionando; solo el asíncrono se degrada |

## Criterios para detener despliegues

1. El error budget del mes ya está agotado.
2. El test de arquitectura de aislamiento multi-tenant (`TenantScopingArchitectureTest.php`) está en rojo — bloqueante absoluto, sin excepciones.
3. El health check de *readiness* no se estabiliza tras 3 reintentos en el entorno de destino (ver `deployment-runbook.md`).

## Alertas que existirían en un entorno real

**Ninguna de estas está implementada** — no hay Prometheus, Grafana, ni ningún sistema de alertas conectado, porque no hay producción a la que alertar. Se documentan como parte del ejercicio de diseño:

| Alerta | Condición | Severidad |
|---|---|---|
| Readiness caído | `/api/health/ready` devuelve 503 en 3 comprobaciones seguidas | Página al on-call (crítica) |
| Cola creciendo sin consumirse | `pending_queue_jobs` (ver dashboard) por encima de un umbral durante > 10 min | Aviso |
| Tasa de jobs fallidos elevada | Incremento sostenido en `failed_jobs` respecto a la línea base | Aviso |
| Latencia degradada | p95 por encima del SLO durante > 15 min | Aviso |
| Tasa de errores 5xx alta | Ritmo que agotaría el error budget mensual en < 24h si continuara | Página al on-call (crítica) |

## Resultados observados realmente

Esto es deliberadamente corto, porque es todo lo que hay:

- Se verificó manualmente (Sesión 3) que los tres health checks (`/api/health/live`, `/ready`, `/app`) responden `200` con sus comprobaciones en `ok` contra el stack de Docker local, en un momento puntual — no es una medición de disponibilidad (no hay ventana de tiempo ni tráfico real).
- La suite de tests automatizados (44 backend + 6 frontend) pasa de forma consistente en cada ejecución local — es una señal de correctitud funcional, no de fiabilidad en producción.
- No existe ninguna medición real de latencia, disponibilidad mensual, ni incidentes en producción, porque el proyecto nunca ha estado desplegado. Cualquier documento o afirmación que sugiera lo contrario sería un error a corregir.
