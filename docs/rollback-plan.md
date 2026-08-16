# Plan de rollback

> Al igual que `deployment-runbook.md`, este plan describe una estrategia; no se ha ejecutado nunca contra un entorno real porque el proyecto no está desplegado (ver decisión en `project-charter.md`).

## Cuándo se dispara un rollback

Cualquiera de estas condiciones, tras un despliegue:

1. `GET /api/health/ready` no responde `200` de forma consistente en los primeros 5 minutos tras el corte de tráfico.
2. El smoke test post-despliegue (login + listar solicitudes) falla.
3. La tasa de errores 5xx supera el umbral que agotaría el error budget mensual en menos de 24h si continuara al mismo ritmo (ver cálculo en [`slo.md`](slo.md)).
4. Se detecta que un cambio reciente afecta al aislamiento multi-tenant de forma no prevista (máxima prioridad — ver `risk-register.md`, R1).

## Cómo se haría el rollback

1. **Revertir el tráfico primero, el código después**: el balanceador/DNS vuelve a apuntar a la versión anterior (que no se ha retirado todavía — ver paso 8 de `deployment-runbook.md`). Esto reduce el impacto en segundos, no en el tiempo que tarde un nuevo despliegue.
2. **Migraciones de base de datos**: por diseño, las migraciones de este proyecto son aditivas (añaden columnas/tablas, no las eliminan ni renombran en el mismo paso — ver cómo se hizo en la práctica en `2026_08_14_120041_add_organization_and_role_to_users_table.php`, que añade columnas sin tocar las existentes). Esto significa que la versión anterior del código sigue funcionando contra el esquema nuevo sin necesidad de revertir la migración. **No se ejecuta `migrate:rollback` en caliente** salvo que la migración en cuestión sea la causa confirmada del incidente — revertir un esquema con datos ya escritos es más arriesgado que convivir con una columna de más.
3. **Cola de mensajes**: si el incidente implica jobs ya encolados con el formato nuevo, se dejan en `failed_jobs` tras agotar reintentos en vez de forzar su procesamiento con código antiguo incompatible — se reprocesan manualmente una vez resuelta la causa raíz.
4. **Comunicación**: Engineering Lead notifica a Product Owner y Security Advisor en cuanto se dispara el rollback (no después de completarlo) — según el RACI, ambos son "I" (informados) como mínimo en cualquier incidente de este tipo.

## MTTR objetivo

Ver `slo.md` — 30 minutos para incidentes que afecten a la disponibilidad general, 15 minutos si el incidente implica una posible fuga de datos entre organizaciones (se trata como el escenario más grave posible, no como uno más).

## Después del rollback

Todo rollback genera una entrada en `risk-register.md` si la causa es un patrón repetible (no una casualidad de infraestructura), y un ADR si la causa implica revisar una decisión de arquitectura ya tomada.
