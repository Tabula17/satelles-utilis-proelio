# XVII: 🛰️ satelles-utilis-proelio
![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue)
![License](https://img.shields.io/github/license/Tabula17/satelles-utilis-proelio)
![Last commit](https://img.shields.io/github/last-commit/Tabula17/satelles-utilis-proelio)

Reusable PHP utilities for Tabula17 projects. This library provides small, focused components to speed up application development (queues, cache, configuration helpers, printing, middleware, console logging, and collections).

Note: This repository is a library (no standalone runtime/entry point). It is installed via Composer and used in your own application code.

## Stack

- Language: PHP (>= 8.4 per `composer.json`)
- Package manager: Composer
- Autoload: PSR-4 (`Tabula17\Satelles\Utilis\` => `src/`)
- Key dependencies:
  - Runtime: `psr/log` (PSR-3 interfaces)
  - Dev-only: `php-amqplib/php-amqplib` (suggested for RabbitMQ queue)
- Suggested extensions/packages (optional): `ext-redis`, `ext-openssl`, `ext-swoole`, `ext-simplexml`

## Installation

```bash
composer require xvii/satelles-utilis-proelio
```

## Requirements

- PHP 8.4 or newer
- Extensions depending on what you use:
  - Redis cache/queue: `ext-redis`
  - RabbitMQ queue: `php-amqplib/php-amqplib`
  - TCP mTLS middleware: `ext-openssl`, `ext-swoole`
  - CUPS client (IPP/HTTP): likely `ext-swoole` (see TODO below)

## Usage Overview

Below are minimal examples for the main components currently present in `src/`.

### Cache: RedisStorage

`Tabula17\Satelles\Utilis\Cache\RedisStorage` implements a simple key/value cache on Redis and uses `Tabula17\Satelles\Utilis\Config\RedisConfig` for connection options.

```php
use Tabula17\Satelles\Utilis\Cache\RedisStorage;
use Tabula17\Satelles\Utilis\Config\RedisConfig;

$config = new RedisConfig([
    'host' => '127.0.0.1',
    'port' => 6379,
    // 'auth' => ['user', 'pass'],
    // 'persistent' => true,
]);

$cache = new RedisStorage($config, prefix: 'app-cache:');
$cache->set('user:42:name', 'Ada');
echo $cache->get('user:42:name');
```

There is also a `MemcachedStorage` implementation that requires the `memcached` extension. Example is analogous (`get`, `set`, `has`, `delete`, `clear`).

### Queues: RedisQueue and RabbitMQQueue

`Tabula17\Satelles\Utilis\Queue\RedisQueue` provides a very simple queue over Redis lists/hashes.

```php
use Tabula17\Satelles\Utilis\Queue\RedisQueue;

// Constructor signature: (string $host, int $port, ?string $channel = null, float $timeout = 2.5)
$queue = new RedisQueue('127.0.0.1', 6379, 'my-app');

$taskId = $queue->push(['type' => 'send-email', 'to' => 'user@example.com']);
$task = $queue->pop();
if ($task !== null) {
    // ... process ...
    $queue->ack($taskId);
}
```

`RabbitMQQueue` exists under `src/Queue/` and requires `php-amqplib/php-amqplib`. See class for exact constructor and usage. TODO: Add a concrete example here once interface is finalized.

### TCP mTLS Middleware (Swoole)

`Tabula17\Satelles\Utilis\Middleware\TCPmTLSAuthMiddleware` helps authorize TCP connections using mutual TLS (mTLS). You will need Swoole and OpenSSL enabled in your runtime.

Key features:
- Client certificate validation
- Allowlist of client Common Names
- Enriched connection context

Basic example:

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
### Printing: CUPS Client (IPP)

`Tabula17\Satelles\Utilis\Print\CupsClient` provides access to CUPS (IPP/HTTP). It can retrieve printer lists, submit jobs, and query version.

Important:
- Your CUPS server must permit the required operations from your host. See CUPS docs for `cupsd.conf` examples.
- Authentication may be required by your CUPS setup.

Basic example:

```php
use Tabula17\Satelles\Utilis\Print\CupsClient;

$cups = new CupsClient('localhost', 631);
$printers = $cups->getPrinters();
// $cups->printJob('printer-name', $data, 'Document title', ['copies' => 2]);
$cups->disconnect();
```


### Collections and Console

- `Collection\GenericCollection`: base for iterable collections, with `toArray()`, `count()`, and serialization helpers.
- `Console\Log`: a PSR-3 compatible logger wrapper using an internal `VerboseTrait` (see source). Useful for CLI tools.

## Project Structure

```
.
├── LICENSE
├── README.md
├── composer.json
├── composer.lock
├── src
│   ├── Array/
│   ├── Cache/
│   │   ├── CacheManagerInterface.php
│   │   ├── RedisStorage.php
│   │   └── MemcachedStorage.php
│   ├── Collection/
│   │   └── GenericCollection.php
│   ├── Config/
│   │   ├── AbstractDescriptor.php
│   │   └── RedisConfig.php
│   ├── Console/
│   │   └── Log.php
│   ├── Middleware/
│   │   └── TCPmTLSAuthMiddleware.php
│   ├── Print/
│   │   └── CupsClient.php
│   └── Queue/
│       ├── RedisQueue.php
│       └── RabbitMQQueue.php
└── vendor/
```

## License

MIT License (see `LICENSE`).

## Contributing

Contributions are welcome!

1. Fork the project
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Commit your changes (`git commit -m "feat: add my feature"`)
4. Push the branch (`git push origin feature/my-feature`)
5. Open a Pull Request

## Support

Please open an issue describing bugs or feature requests.

###### 🌌 Ad astra per codicem