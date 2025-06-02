# XVII: 🛰️ satelles-utilis-proelio
![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue)
![License](https://img.shields.io/github/license/Tabula17/satelles-utilis-proelio)
![Last commit](https://img.shields.io/github/last-commit/Tabula17/satelles-utilis-proelio)

Biblioteca de utilidades PHP para proyectos Tabula17. Proporciona componentes reutilizables y herramientas comunes para facilitar el desarrollo de aplicaciones.

## Instalación

```bash
composer require xvii/satelles-utilis-proelio
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
// Configuración del servidor
$server = new Swoole\Server('0.0.0.0', 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);

$server->set([
    'ssl_cert_file' => '/path/to/server.crt',
    'ssl_key_file' => '/path/to/server.key',
    'ssl_ca_file' => '/path/to/ca.crt',
    'ssl_verify_peer' => true,
    'ssl_allow_self_signed' => false,
    'ssl_client_cert_file' => true,
]);

// Configuración del middleware
$logger = new Monolog\Logger('mtls');
$middleware = new TCPmTLSAuthMiddleware($logger);

// Autorizar clientes
$middleware->allowClients([
    'client1.example.com',
    'client2.example.com',
    '*.trusted-domain.com'
]);

// Manejar conexiones
$server->on('receive', function (Server $server, int $fd, int $reactorId, string $data) use ($middleware) {
    $middleware->handle($server, $fd, $data, function ($server, $context) {
        // Lógica de negocio para conexiones autorizadas
        $response = sprintf(
            "Hola %s! Tu mensaje: %s\n",
            $context['client_cn'],
            trim($context['data'])
        );
        $server->send($context['fd'], $response);
    });
});

$server->start();
```

Características del middleware mTLS:
- Validación de certificados de cliente
- Gestión de lista blanca de Common Names
- Rechazo automático de conexiones no autorizadas
- Contexto enriquecido con información del certificado


### Cliente CUPS (IPP)

Implementa un cliente para el protocolo IPP (Internet Printing Protocol) que permite interactuar con servidores CUPS. 
Permite enviar trabajos de impresión y gestionar impresoras de red.

#### Nota importante:
1. El servidor CUPS debe estar configurado para aceptar conexiones remotas si no estás trabajando en localhost.
2. El protocolo IPP (Internet Printing Protocol) es complejo y esta clase solo implementa una parte.
3. Swoole es necesario para manejar las conexiones de red de manera eficiente.

Ejemplo de uso:

```php
// Crear cliente CUPS
$client = new CupsClient('localhost', 631);

// Enviar trabajo de impresión
$response = $client->printJob(
    'mi-impresora',
    $datos,
    'Documento importante',
    ['copies' => 2]
);

// Listar impresoras disponibles
$printers = $client->getPrinters();

// Cerrar conexión cuando termine
$client->disconnect();
```
Características del cliente CUPS:
- Soporte completo del protocolo IPP 1.1
- Gestión automática de conexiones
- Envío de trabajos de impresión
- Consulta de impresoras disponibles
- Manejo de atributos y opciones de impresión
- Parsing completo de respuestas IPP

## Por Implementar

- Sistema de Caché
- Utilidades de Logging
- Helpers para manipulación de arrays y strings
- Validadores comunes
- Utilidades de fecha y hora
- Interfaces HTTP comunes
- Y más...

## Requisitos

- PHP 8.2 o superior
- Extensiones según el componente:
  - Redis: `ext-redis`
  - RabbitMQ: `php-amqplib/php-amqplib`
  - mTLS: `ext-openssl`, `ext-swoole`
  - CUPS: `ext-swoole`

## Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Haz fork del proyecto
2. Crea una rama para tu funcionalidad (`git checkout -b feature/nueva-funcionalidad`)
3. Commitea tus cambios (`git commit -am 'Agrega nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crea un Pull Request

## Licencia

MIT License

## Soporte

Para reportar problemas o solicitar nuevas características:
1. Revisa los issues existentes
2. Abre un nuevo issue con los detalles del problema o sugerencia

###### 🌌 Ad astra per codicem