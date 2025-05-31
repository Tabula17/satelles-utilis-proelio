# XVII: satelles-utilis-proelio

Biblioteca de utilidades PHP para proyectos Tabula17. Proporciona componentes reutilizables y herramientas comunes para facilitar el desarrollo de aplicaciones.

## Instalación

```bash
composer require tabula17/satelles-utilis-proelio
```

## Componentes

### Sistema de Colas (Queue)

Implementa un sistema de colas de tareas con múltiples backends:

- **RedisQueue**: Implementación basada en Redis
- **RabbitMQQueue**: Implementación basada en RabbitMQ

Ejemplo de uso con Redis:

```php
$config = [
    'host' => 'localhost',
    'port' => 6379,
    'channel' => 'mi-aplicacion'
];

$queue = new RedisQueue($config);

// Agregar una tarea
$taskId = $queue->push([
    'tipo' => 'enviar-email',
    'datos' => [
        'destinatario' => 'usuario@ejemplo.com',
        'asunto' => 'Prueba'
    ]
]);

// Procesar una tarea
$task = $queue->pop();
if ($task) {
    // Procesar la tarea...
    $queue->ack($task['_task_id']);
}
```

Ejemplo con RabbitMQ:

```php
$config = [
    'host' => 'localhost',
    'port' => 5672,
    'user' => 'guest',
    'password' => 'guest',
    'queue' => 'mi-cola'
];

$queue = new RabbitMQQueue($config);

// El uso es similar al de RedisQueue
```

### Middleware mTLS (TCP)

Implementa autenticación mutual TLS (mTLS) para servidores TCP Swoole. Permite gestionar conexiones seguras basadas en certificados X.509.

Ejemplo de uso:

```php
$middleware = new TCPmTLSAuthMiddleware();

// Agregar clientes autorizados por su Common Name
$middleware->allowClient('client1.example.com');
$middleware->allowClient('client2.example.com');

// Configurar el servidor Swoole con TLS
$server->set([
    'ssl_cert_file' => '/path/to/server.crt',
    'ssl_key_file' => '/path/to/server.key',
    'ssl_ca_file' => '/path/to/ca.crt',
    'ssl_verify_peer' => true,
    'ssl_client_cert_file' => true,
]);

// Manejar conexiones entrantes
$server->on('receive', function (Server $server, int $fd, int $reactorId, string $data) use ($middleware) {
    $middleware->handle($server, $fd, $data, function ($server, $context) {
        // El cliente está autenticado, procesar la petición
        $server->send($context['fd'], "Bienvenido " . $context['client_cn']);
    });
});
```

Características del middleware mTLS:
- Validación de certificados de cliente
- Gestión de lista blanca de Common Names
- Rechazo automático de conexiones no autorizadas
- Contexto enriquecido con información del certificado

## Por Implementar

- Sistema de Caché
- Utilidades de Logging
- Helpers para manipulación de arrays y strings
- Validadores comunes
- Utilidades de fecha y hora
- Interfaces HTTP comunes
- Y más...

## Requisitos

- PHP 8.0 o superior
- Extensiones según el componente:
  - Redis: `ext-redis`
  - RabbitMQ: `php-amqplib/php-amqplib`
  - mTLS: `ext-openssl`, `ext-swoole`

## Licencia

Este proyecto está licenciado bajo los términos de la licencia MIT.

## Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Haz fork del proyecto
2. Crea una rama para tu funcionalidad (`git checkout -b feature/nueva-funcionalidad`)
3. Commitea tus cambios (`git commit -am 'Agrega nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crea un Pull Request
