# Project Charter — SaaS Platform Delivery Lab

> Todo lo que hay en este documento (patrocinador, presupuesto, fechas) es **simulado** para poder demostrar el proceso de gestión de un proyecto técnico. Se marca explícitamente para no confundirlo con datos reales. Lo que sí es real es el código, los tests y las decisiones de arquitectura que documenta.

## Objetivo empresarial

Una organización que coordina trabajo interno entre equipos (incidencias, mantenimiento, peticiones entre departamentos) no tiene visibilidad de quién está haciendo qué, ni una forma fiable de asegurar que los datos de una unidad de negocio no se mezclan con los de otra si en el futuro la plataforma sirve a varias organizaciones independientes (clientes, franquicias, filiales). El objetivo de negocio simulado es: **ofrecer una plataforma de gestión de solicitudes operativas que cualquier organización pueda adoptar de forma aislada, con confianza en que sus datos nunca son visibles para otra organización que use la misma plataforma.**

Esto es el objetivo de negocio de un producto SaaS B2B genérico — deliberadamente no se ata a un sector concreto, porque el valor que demuestra este laboratorio es la arquitectura multi-tenant en sí, no el dominio de negocio.

## Patrocinador (simulado)

**Elena Ross**, Product Owner. Aprueba el alcance (`scope.md`) y prioriza qué se construye en cada sesión/iteración. En este laboratorio, todas las decisiones de producto y de arquitectura las toma la misma persona (el autor) — el documento mantiene los roles separados porque en un equipo real representarían intereses distintos que sí compiten entre sí (ver `stakeholder-map.md`).

## Alcance de alto nivel

Ver detalle completo, incluido lo explícitamente fuera de alcance, en [`scope.md`](scope.md). Resumen:

- Multi-tenancy con aislamiento estricto entre organizaciones.
- Gestión de solicitudes operativas con estados, comentarios, historial y asignación.
- Roles por organización (Administrator, Manager, Member).
- Notificaciones asíncronas.
- Observabilidad básica (health checks, logs, dashboard operativo).
- Feature flags para activación progresiva.

## Restricciones del proyecto

- **Sin coste**: no se usan servicios de pago ni se despliega a un proveedor cloud real sin autorización explícita (ver [`deployment-runbook.md`](deployment-runbook.md) — el despliegue está documentado como plan, no ejecutado).
- **Sin Kubernetes** ni orquestadores más allá de Docker Compose.
- **Un solo desarrollador**: las prácticas de equipo (RACI, comunicación) se documentan igualmente porque son parte de lo que este laboratorio demuestra, no porque haga falta coordinar a varias personas de verdad.
- **Presupuesto**: 0 € reales. Cualquier cifra de presupuesto en otros documentos de gestión es ilustrativa.

## Criterios de éxito

El proyecto se considera exitoso si, al final de la Sesión 5:

1. El sistema completo se levanta con un comando (`docker compose up -d` + seed) y funciona de extremo a extremo, verificado con clics reales en navegador.
2. El aislamiento multi-tenant está probado con tests adversariales, no solo documentado como intención.
3. Existe un registro de decisiones de arquitectura (ADR) que explica las alternativas descartadas, no solo la decisión final.
4. La documentación de gestión (este documento y los que enlaza) es coherente con lo que el código realmente hace — sin afirmaciones no verificadas.

Ver hitos concretos por sesión en [`milestones.md`](milestones.md).

## Fecha de aprobación (simulada)

2026-07-18 — antes del inicio de la Sesión 1.
