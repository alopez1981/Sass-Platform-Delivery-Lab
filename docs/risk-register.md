# Registro de riesgos

Escala de probabilidad e impacto: Baja / Media / Alta / Crítica (impacto crítico = compromete el objetivo de negocio del proyecto, ver `project-charter.md`).

Algunos de estos riesgos **ya se materializaron durante el desarrollo** — se dejan registrados como tal en vez de retirarlos, porque un registro de riesgos que solo muestra riesgos hipotéticos no resueltos es menos útil que uno que demuestra cómo se gestionó un riesgo real.

| ID | Riesgo | Probabilidad | Impacto | Mitigación | Propietario | Estado |
|---|---|---|---|---|---|---|
| R1 | Fuga de datos entre organizaciones (IDOR) | Media | Crítico | Global Scope automático (`TenantScopedModel`) + tests adversariales (`TenantIsolationTest.php`) + test de arquitectura que impide crear un modelo nuevo sin el scope | Security Advisor | Mitigado |
| R2 | Recursión infinita al autenticar por aplicar el scope multi-tenant al modelo `User` | — (ya ocurrió) | Crítico | Excluir `User` del Global Scope automático; aislamiento manual donde haga falta. Ver enmienda del [ADR 0003](adr/0003-multi-tenancy-strategy.md) | Engineering Lead | **Materializado y resuelto** (Sesión 2) |
| R3 | Herramienta de desarrollo bloquea la verificación visual en navegador (peticiones entre puertos de `localhost`) | — (ya ocurrió) | Medio | Proxy de desarrollo en Vite (`vite.config.ts`) — SPA y API como mismo origen ante el navegador | Engineering Lead | **Materializado y resuelto** (Sesión 3) |
| R4 | Mensajes de RabbitMQ publicados pero nunca consumidos por configuración incorrecta del exchange | — (ya ocurrió, detectado en verificación manual) | Alto | Simplificar la configuración (sin exchange personalizado); verificar la profundidad real de la cola tras cada cambio, no solo que el dispatch no lance excepción | Engineering Lead | **Materializado y resuelto** (Sesión 2) |
| R5 | Un job de notificación falla repetidamente y se pierde silenciosamente | Media | Medio | Reintentos (`--tries`/`--backoff`) + `failed_jobs` como dead-letter simplificado, visible en el dashboard operativo | Engineering Lead | Mitigado (parcialmente — ver limitación en `architecture.md` sobre dead-letter explícito) |
| R6 | Bus factor: un único desarrollador conoce todo el sistema | Alta | Alto | ADRs y documentación exhaustiva de cada decisión no obvia, para que el conocimiento no dependa de la memoria de una persona | Product Owner | Aceptado (riesgo inherente a un side-project; mitigación es la documentación, no eliminarlo) |
| R7 | Coste inesperado si se despliega a un proveedor cloud sin control de gasto | Baja | Medio | Decisión explícita de no desplegar; estrategia de despliegue documentada pero no ejecutada (`deployment-runbook.md`) | Product Owner | Mitigado (evitado por decisión) |
| R8 | Deriva de versiones entre el entorno de desarrollo y Docker (ej. Node local 22.12 vs 22.18 requerido) | Media | Bajo | Documentado en README como limitación conocida; Docker Compose es la fuente de verdad para versiones, no el entorno local | Engineering Lead | Aceptado |
| R9 | Un feature flag activado para una organización se filtra incorrectamente a otra | Baja | Alto | Pennant escopa por `Organization`; test (`FeatureFlagTest.php`) verifica explícitamente que activar el flag en una organización no lo activa en otra | Security Advisor | Mitigado |
| R10 | El dashboard operativo expone datos de negocio de otro tenant a través de `failed_jobs` (payload de un job fallido) | Baja | Alto | El dashboard nunca serializa el payload del job, solo la excepción y la fecha (`DashboardController::recentErrors()`); test explícito que lo comprueba | Security Advisor | Mitigado |

## Cómo se usa este registro

Un riesgo nuevo entra a este documento en cuanto se identifica (antes de mitigarlo), con estado inicial acorde a si ya se materializó o no. Se actualiza el estado, nunca se borra la fila — el histórico de qué se identificó y cuándo es parte del valor del documento.
