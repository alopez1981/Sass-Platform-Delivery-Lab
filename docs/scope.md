# Alcance

## Dentro de alcance (implementado, Sesiones 1–3)

- Multi-tenancy con aislamiento por fila (`organization_id` + Global Scope) — ver [ADR 0003](adr/0003-multi-tenancy-strategy.md).
- Organizaciones, usuarios con rol (Administrator / Manager / Member).
- Solicitudes operativas con máquina de estados (Draft → Open → In progress → Resolved/reabierta → Closed), comentarios e historial de cambios.
- Autenticación real vía Laravel Sanctum (SPA-stateful) y autorización por rol vía Policies.
- Notificaciones asíncronas reales sobre RabbitMQ, con reintentos y `failed_jobs` como dead-letter simplificado.
- Feature flag de ejemplo escopado por organización (Laravel Pennant).
- Health checks (liveness/readiness/application health), logs estructurados con correlation ID, manejo de errores consistente, rate limiting en login.
- Dashboard operativo (solo Administrator).
- Datos de demostración reproducibles (seeder con 2 organizaciones).
- Tests automatizados del camino feliz y adversariales de aislamiento (44 backend + 6 frontend).

## Dentro de alcance (pendiente, Sesión 5)

- Suite de tests ampliada y revisión final de cobertura.
- Pulido de README/documentación para presentación.

## Explícitamente fuera de alcance

- **Kubernetes** o cualquier orquestador más allá de Docker Compose.
- **Multi-tenancy por base de datos separada** (modelo *silo*) — alternativa evaluada y descartada, ver ADR 0003.
- **Despliegue real a un proveedor cloud** o uso de servicios de pago — la estrategia se documenta (`deployment-runbook.md`) pero no se ejecuta; ver la decisión en la conversación de la Sesión 4.
- **Autenticación de terceros (API tokens machine-to-machine)** — solo se cubre el flujo de sesión de la SPA.
- **Gestión de usuarios desde la UI** (crear/editar usuarios de tu organización desde la interfaz) — los usuarios existen vía seeder; el backend sí impone que un Administrator sería quien lo haga, pero no hay pantalla para ello.
- **Dead-letter explícito** (exchange/cola dedicados en RabbitMQ para mensajes fallidos) — `failed_jobs` cumple ese propósito de forma más simple, decisión consciente.
- **Alertas reales conectadas a un sistema de monitorización** — se documentan en `slo.md` como "qué existiría en un entorno real", no se implementan (no hay production de verdad a la que alertar).
- **Internacionalización, temas visuales configurables, accesibilidad WCAG formal** — la interfaz es funcional, no un producto pulido de cara al cliente final.

## Definition of Ready

Una funcionalidad está lista para empezar a implementarse cuando:

1. Está descrita en este documento o en un ADR (si implica una decisión de arquitectura con alternativas).
2. Se sabe qué modelos/tablas toca y si alguno necesita `organization_id` + `TenantScopedModel` (ver ADR 0003).
3. Se sabe qué rol(es) pueden usarla (entrada para la Policy correspondiente).
4. Si implica un caso de error o de seguridad relevante, se sabe qué test adversarial lo probará antes de darla por cerrada.

## Definition of Done

Una funcionalidad está terminada cuando:

1. Tiene tests automatizados que pasan (`./vendor/bin/pest` / `npm run test:unit`), incluyendo al menos un caso de error si aplica.
2. Pasa lint y build sin errores (`pint`, `eslint`/`oxlint`, `vue-tsc`, `vite build`).
3. Se ha verificado manualmente contra el stack de Docker real (no solo contra el entorno de test) — la Sesión 2 demostró que algunos bugs solo aparecen así.
4. La documentación relevante (README, `architecture.md`, ADR si aplica) refleja el estado real, no el planeado.
5. No introduce una regresión conocida en el aislamiento multi-tenant (el test de arquitectura de `TenantScopingArchitectureTest.php` debe seguir en verde).

## Criterios de aceptación (ejemplos representativos)

**Aislamiento multi-tenant**: dado un usuario autenticado de la organización A, al intentar leer, comentar o cambiar el estado de un recurso de la organización B por ID directo, la API responde 404 (nunca 403) y el recurso no aparece en ningún listado de A.

**Notificación asíncrona**: dado que se crea una solicitud sin asignar, cuando el `queue-worker` procesa el job correspondiente, entonces existe una `Notification` para cada Administrator y Manager de esa organización, y ninguna para organizaciones distintas.

**Feature flag**: dado que un Administrator activa `members-can-close-own-requests` para su organización, cuando un Member de esa organización intenta cerrar una solicitud que él mismo creó, entonces se permite; para un Member de otra organización (flag inactivo allí), la misma acción se rechaza.
