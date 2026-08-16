# Stakeholders y matriz RACI

> Stakeholders simulados — no representan personas reales. Todos los roles los ejerce el mismo autor en la práctica; se mantienen separados porque cada uno representa una preocupación distinta que en un equipo real tendría un dueño distinto y a veces entraría en conflicto con las demás (por ejemplo: Product Owner quiere velocidad, Security Advisor quiere más pruebas antes de cada release).

## Stakeholders

| Rol | Nombre (simulado) | Preocupación principal | Interés | Influencia |
|---|---|---|---|---|
| Product Owner | Elena Ross | Alcance, prioridades, criterios de éxito | Alto | Alta |
| Engineering Lead / Architect | (el autor) | Arquitectura, calidad técnica, decisiones documentadas (ADR) | Alto | Alta |
| Security & Compliance Advisor | Marcus Ibe | Aislamiento multi-tenant, autorización, manejo de errores sin fugas de datos | Alto | Media |
| Pilot Customer Rep | Sofía Andrade (representa una organización piloto que adoptaría la plataforma) | Que el flujo de solicitudes sea usable y las notificaciones lleguen | Medio | Baja |
| Support / Customer Success Lead | Dana Okafor | Observabilidad, dashboard operativo, tiempo de resolución de incidencias | Medio | Media |

## Matriz RACI

R = Responsable (ejecuta) · A = Aprueba (rinde cuentas) · C = Consultado · I = Informado

| Actividad / entregable | Product Owner | Engineering Lead | Security Advisor | Pilot Customer | Support Lead |
|---|:---:|:---:|:---:|:---:|:---:|
| Definir alcance (`scope.md`) | A | R | C | C | I |
| Decisiones de arquitectura (ADR) | I | R/A | C | I | I |
| Diseño e implementación del aislamiento multi-tenant | I | R | A | I | I |
| Tests adversariales de seguridad | I | R | A | I | I |
| Implementación de la API y la SPA | C | R/A | I | C | I |
| Estrategia de despliegue y rollback | C | R/A | C | I | C |
| Definición de SLI/SLO/SLA (`slo.md`) | A | R | C | I | C |
| Dashboard operativo | I | R | I | I | A |
| Registro y priorización de riesgos | A | R | C | I | I |
| Comunicación de hitos (`delivery-plan.md`) | R/A | C | I | I | I |

Notas sobre decisiones no obvias:
- **Security Advisor es "A" (no solo "C") en el aislamiento multi-tenant y los tests de seguridad**: es la única fila donde alguien distinto del Engineering Lead tiene la responsabilidad última — refleja que en un equipo real, quien construye no debería ser también quien da el visto bueno final a su propia superficie de seguridad.
- **Support Lead es "A" en el dashboard operativo**: el dashboard existe para que soporte pueda operar el sistema día a día: si no le sirve a quien lo va a usar, no importa que esté bien construido.
- **Pilot Customer nunca es R ni A**: no implementa ni aprueba nada — solo se le consulta o informa, como cualquier cliente real.
