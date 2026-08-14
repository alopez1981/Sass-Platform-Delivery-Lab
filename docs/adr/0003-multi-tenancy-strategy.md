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

## Enmienda — 2026-08-14: `User` queda excluido del Global Scope

Durante la verificación end-to-end de la Sesión 2 apareció un error real: aplicar `BelongsToOrganization` (y por tanto `OrganizationScope`) al modelo `User` provocaba un **error 500 por agotamiento de memoria** en cualquier petición autenticada.

**Causa raíz:** autenticar una petición requiere que Sanctum resuelva el usuario a partir del ID guardado en sesión (`$provider->retrieveById($id)`), lo que ejecuta `User::query()->find($id)`. Si esa consulta está filtrada por `OrganizationScope` (que a su vez llama a `auth()->user()->organization_id` para saber por qué organización filtrar), se produce una paradoja circular: para saber quién es el usuario autenticado hace falta que el usuario autenticado ya esté resuelto. El resultado es una recursión infinita entre `Auth::user()` y la consulta que lo resuelve, hasta agotar el límite de memoria de PHP.

Esto **no se detectó con `php artisan tinker`** (donde `auth()->loginUsingId()` inyecta el usuario directamente en el guard sin pasar por esa consulta) ni con los tests de Pest existentes hasta ese momento — solo se manifestó al probar el flujo real de extremo a extremo vía HTTP, que es precisamente el motivo por el que esta sesión incluye una verificación manual además de los tests automatizados.

**Decisión revisada:** `User` **no** usa `BelongsToOrganization` ni queda cubierto por el Global Scope. Mantiene la columna `organization_id`, la relación `organization()` y su aislamiento se aplica **manualmente y explícitamente** en cada consulta (tal como ya se hacía en `RequestPolicy` y en `NotifyRequestCreated`). Cualquier futura pantalla de "gestionar usuarios de mi organización" debe filtrar por `organization_id` a mano — no puede depender del scope automático.

**Consecuencia para el test de arquitectura mencionado arriba:** ese test (Sesión 3/5) debe tratar `User` como una excepción documentada, no como un fallo a corregir.

**Limitación honesta sobre la cobertura de este bug:** se añadió un test (`tests/Feature/AuthTest.php`, *"stays authenticated across requests after a real session login"*) que hace login real y luego una segunda petición autenticada, precisamente para no depender de `actingAs()` (que evita la consulta real y por eso nunca detectó el problema). Sin embargo, se comprobó deliberadamente que **ese test sigue pasando incluso si se reintroduce el bug**: el límite de memoria de PHP-FPM en Docker es 128 MB, mientras que el proceso CLI que ejecuta Pest normalmente no tiene límite de memoria, así que la misma recursión infinita no llega a agotar memoria en el entorno de test — simplemente es más lenta pero no explota. Es decir: **el test automatizado no habría atrapado esta regresión por sí solo; la verificación real fue manual, contra el stack de Docker, con curl reproduciendo exactamente las cabeceras/cookies de la SPA.** Se documenta esto explícitamente en vez de aparentar una cobertura que no existe.
