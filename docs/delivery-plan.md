# Plan de entrega

## Método de trabajo

Entrega por **sesiones**, cada una un incremento vertical completo (no una capa horizontal): cada sesión deja el sistema arrancable y verificado de extremo a extremo, nunca a medias. Esto es deliberado — ver `scope.md` (Definition of Done): no se cierra una sesión con tests en rojo, lint fallando, o una funcionalidad verificada "solo sobre el papel".

Dentro de cada sesión:
1. Planificar en voz alta qué se va a construir y por qué, antes de escribir código.
2. Implementar de dentro hacia fuera: modelo de datos → reglas de negocio/autorización → API → interfaz.
3. Escribir tests según se construye, no al final.
4. Verificar contra el stack de Docker real, no solo contra el entorno de test (la Sesión 2 demostró que no son intercambiables — un bug de recursión infinita solo apareció en Docker).
5. Documentar la decisión (ADR) si hubo alternativas reales evaluadas; documentar el bug si algo se rompió y por qué.
6. Cerrar con lint + build + tests en verde, commit, y actualización del README reflejando el estado real.

## Estrategia de comunicación (simulada)

| Qué | Cuándo | Con quién | Formato |
|---|---|---|---|
| Demo de fin de sesión | Al cerrar cada hito (`milestones.md`) | Product Owner, Pilot Customer Rep | Sesión corta enseñando el flujo en navegador |
| Decisión de arquitectura | Cuando surge una alternativa real que evaluar | Engineering Lead, Security Advisor | ADR (`docs/adr/`) |
| Incidente o bug encontrado en verificación | Inmediatamente al detectarlo | Todos los stakeholders relevantes según el RACI | Entrada en `risk-register.md` si es recurrente, o nota en `architecture.md`/el ADR relevante si es puntual |
| Estado del proyecto | Continuo | Cualquiera que abra el repositorio | README, sección "Estado del proyecto" — se actualiza en cada cierre de sesión, nunca se deja desactualizado |

La regla de fondo: **la documentación de estado vive en el repositorio, no en una herramienta externa de gestión** — para un proyecto de este tamaño, tener el estado en un Jira/Notion aparte solo crea una fuente de verdad más que mantener sincronizada.

## Cadencia

Sin cadencia de calendario fija (ver nota de fechas simuladas en `milestones.md`). La cadencia real es **una sesión = un hito**, y una sesión no empieza hasta que la anterior cierra con su Definition of Done cumplida.
