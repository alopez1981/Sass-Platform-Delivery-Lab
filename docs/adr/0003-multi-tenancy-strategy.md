# ADR 0003: Estrategia de aislamiento multi-tenant

## Estado
Aceptado — 2026-08-14

## Contexto
El enunciado exige que los datos de una organización nunca sean accesibles desde otra, y que el aislamiento se aplique en backend, no solo en la interfaz. Existen dos enfoques habituales:

1. **Silo (base de datos por tenant):** aislamiento fuerte a nivel de infraestructura, pero añade complejidad operativa (migraciones N veces, conexión dinámica, backups por tenant) desproporcionada para un laboratorio de portfolio con pocos tenants de demostración.
2. **Pool (base de datos compartida, aislamiento por fila):** una única base de datos MySQL; cada tabla relevante tiene una columna `organization_id`; todas las consultas se filtran automáticamente por el tenant del usuario autenticado.

## Decisión
Se adopta el modelo **pool (base de datos compartida con aislamiento por fila)**:

- Toda tabla propiedad de un tenant (`users`, `requests`, `comments`, `request_status_histories`, etc.) tiene una columna `organization_id` no anulable con clave foránea.
- Un **Global Scope** de Eloquent (`BelongsToOrganizationScope`) se aplica automáticamente a esos modelos y filtra por `organization_id = auth()->user()->organization_id` en cada consulta, incluyendo `find()`, sin que el desarrollador tenga que recordarlo en cada controlador.
- Los modelos tenant-scoped usan un trait (`BelongsToOrganization`) que registra el scope y rellena `organization_id` automáticamente al crear registros.
- La autorización por rol (Administrator / Manager / Member) es una capa **adicional e independiente** del aislamiento por tenant: el tenant determina *qué* datos existen para el usuario; el rol determina *qué puede hacer* con esos datos.
- Se añaden tests de regresión (Sesión 3) que crean dos organizaciones y verifican explícitamente que un usuario de la organización A no puede leer, actualizar, ni ver en listados recursos de la organización B, ni siquiera conociendo su ID (evita IDOR).

## Consecuencias
- Riesgo principal: si un desarrollador crea un modelo nuevo y olvida el trait `BelongsToOrganization`, ese modelo queda sin aislamiento silenciosamente. Mitigación: los modelos tenant-scoped heredan de una clase base abstracta (`TenantScopedModel`) en lugar de aplicarse por convención suelta, y un test de arquitectura (Sesión 3/5) verifica que los modelos con columna `organization_id` extienden esa base.
- El modelo pool es más barato de operar (una sola base de datos, un solo pool de conexiones) y es coherente con el principio de "no crear una arquitectura artificialmente grande".
- No cubre aislamiento a nivel de infraestructura (ej. un fallo de la aplicación que ignore el scope podría, en teoría, filtrar datos entre tenants dentro de la misma base de datos). Esta limitación se documenta explícitamente en `docs/architecture.md` y en el README como decisión consciente, no como descuido.
