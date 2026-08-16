# Diagrama: secuencia de notificación asíncrona

De extremo a extremo, desde que un usuario crea una solicitud hasta que la notificación existe en base de datos — la pieza que demuestra que el trabajo en segundo plano es real, no una simulación síncrona disfrazada.

```mermaid
sequenceDiagram
    actor U as Usuario (SPA)
    participant API as Laravel API
    participant DB as MySQL
    participant MQ as RabbitMQ
    participant W as queue-worker

    U->>API: POST /api/requests
    API->>DB: INSERT requests (status=draft)
    API->>MQ: dispatch NotifyRequestCreated
    API-->>U: 201 Created (ya, sin esperar al job)

    Note over API,U: La petición HTTP termina aquí.<br/>Todo lo siguiente ocurre en otro proceso.

    MQ->>W: entrega el mensaje
    W->>DB: SELECT usuarios a notificar<br/>(Administrator/Manager de la organización,<br/>o el asignado si lo hay)
    W->>DB: INSERT notifications (una por destinatario)

    alt el job falla tras agotar reintentos
        W->>DB: INSERT failed_jobs (excepción + fecha, nunca el payload)
    end
```

El `correlation_id` de la petición HTTP original se propaga automáticamente al contexto del job (vía `Illuminate\Support\Facades\Context` — ver `docs/observability.md`), aunque hoy no se registra explícitamente ningún log dentro de `NotifyRequestCreated::handle()` que lo aproveche.
