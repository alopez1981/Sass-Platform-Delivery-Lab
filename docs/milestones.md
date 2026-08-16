# Hitos

> Las fechas son **simuladas** — representan cómo se habría planificado este trabajo en semanas de calendario si un equipo lo ejecutara así. El desarrollo real ocurrió en sesiones de trabajo concentradas (agente + humano), no repartido en semanas naturales; se documenta con fechas de todos modos porque la planificación temporal es en sí parte de lo que este laboratorio demuestra.

| # | Hito | Fecha objetivo (simulada) | Entregable | Estado |
|---|---|---|---|---|
| 1 | Arquitectura y estructura | 2026-07-20 | Monorepo, Docker Compose, esqueleto ejecutable, ADRs 0001–0002 | ✅ Cerrado |
| 2 | Primer flujo vertical funcional | 2026-07-27 | Dominio completo, auth real, API + SPA, notificación asíncrona real | ✅ Cerrado |
| 3 | Seguridad y casos de error | 2026-08-03 | Tests adversariales, feature flag, health checks, logs, dashboard | ✅ Cerrado |
| 4 | Documentación de gestión | 2026-08-16 | Este documento y el resto de `docs/` de gestión, estrategia de despliegue documentada | ✅ Cerrado |
| 5 | Tests y presentación final | 2026-08-17 | Cobertura ampliada, pulido de README, revisión final | ⏳ Pendiente |

## Criterio de cierre de cada hito

Un hito se considera cerrado cuando cumple la Definition of Done de `scope.md` para todo lo entregado en esa sesión **y** cuando el estado descrito en el README para esa sesión coincide con lo verificado (no con lo planeado). Los hitos 1–4 cumplen ambas condiciones; se puede comprobar el detalle de qué se verificó (y cómo) en la sección "Estado del proyecto" del README en el momento de cierre de cada uno (histórico en `git log`).
