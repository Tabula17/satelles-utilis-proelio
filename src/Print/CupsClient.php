<?php

namespace Tabula17\Satelles\Utilis\Print;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Swoole\Coroutine\Http\Client;
use Tabula17\Satelles\Utilis\Exception\InvalidArgumentException;
use Tabula17\Satelles\Utilis\Exception\RuntimeException;

/**
 * @property string|null $username
 * @property float $timeout
 */
class CupsClient
{
    private string $host;
    private int $port;
    private Client $client;
    private ?string $password;
    private ?string $username;
    private float $timeout;
    private const array IPP_VERSION = [0x01, 0x01]; // IPP 1.1
    private const int MAX_RETRIES = 3;
    private const int RETRY_DELAY = 100; // ms

    // Añadir validación en el constructor
    public function __construct(
        string $host = 'localhost',
        int $port = 631,
        float $timeout = 5.0,
        ?string $username = null,
        ?string $password = null
    ) {
        // Validación mejorada
        $this->validateHost($host);
        $this->validatePort($port);
        $this->validateTimeout($timeout);

        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->username = $username;
        $this->password = $password;

        $this->initializeClient();
    }

    private function validateHost(string $host): void
    {
        if (empty($host) || strlen($host) > 255) {
            throw new InvalidArgumentException("Host inválido: debe tener entre 1 y 255 caracteres");
        }

        // Prevenir inyección de comandos
        if (preg_match('/[;&|`$]/', $host)) {
            throw new InvalidArgumentException("Host contiene caracteres no permitidos");
        }
    }

    private function validatePort(int $port): void
    {
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException("Puerto inválido: debe estar entre 1 y 65535");
        }
    }

    private function validateTimeout(float $timeout): void
    {
        if ($timeout < 0.1 || $timeout > 300) {
            throw new InvalidArgumentException("Timeout inválido: debe estar entre 0.1 y 300 segundos");
        }
    }

    private function initializeClient(): void
    {
        $this->client = new Client($this->host, $this->port);
        $this->client->set([
            'timeout' => $this->timeout,
            'headers' => [
                'Content-Type' => 'application/ipp',
                'Accept' => 'application/ipp',
                'User-Agent' => 'Satelles-CUPS-Client/1.0'
            ],
            'keep_alive' => true,
            'websocket_mask' => false
        ]);

        if ($this->username && $this->password) {
            $this->setCredentials($this->username, $this->password);
        }
    }
    /**
     * Ejecuta una solicitud con reintentos automáticos
     */
    private function executeWithRetry(callable $operation, int $maxRetries = self::MAX_RETRIES): mixed
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Recrear cliente si la conexión se perdió
                if (!$this->client->connected) {
                    $this->initializeClient();
                }

                return $operation();
            } catch (Exception $e) {
                $lastException = $e;

                if ($attempt < $maxRetries) {
                    usleep(self::RETRY_DELAY * 1000 * $attempt);
                    continue;
                }
            }
        }

        throw new RuntimeException(
            "Error después de {$maxRetries} intentos: " . $lastException?->getMessage(),
            0,
            $lastException
        );
    }

    /**
     * Versión mejorada de printJob con reintentos
     */
    public function printJob(
        string $printer,
        string $data,
        string $jobName = 'PHP Job',
        array $options = []
    ): array {
        return $this->executeWithRetry(function() use ($printer, $data, $jobName, $options) {
            $this->validatePrinterName($printer);
            $this->validatePrintData($data);

            $ippRequest = $this->buildIppRequest(
                'print-job',
                $printer,
                $jobName,
                $data,
                $options
            );

            $path = '/printers/' . rawurlencode($printer);
            $this->client->post($path, $ippRequest);

            $this->validateResponse($this->client);

            return $this->parseIppResponse($this->client->body);
        });
    }

    private function validatePrinterName(string $printer): void
    {
        if (empty($printer) || strlen($printer) > 127) {
            throw new InvalidArgumentException("Nombre de impresora inválido");
        }

        // Prevenir path traversal
        if (preg_match('/[\/\\\\\.]{2,}/', $printer)) {
            throw new InvalidArgumentException("Nombre de impresora contiene caracteres no permitidos");
        }
    }

    private function validatePrintData(string $data): void
    {
        if (empty($data)) {
            throw new InvalidArgumentException("Los datos de impresión no pueden estar vacíos");
        }

        // Opcional: límite de tamaño
        $maxSize = 100 * 1024 * 1024; // 100 MB
        if (strlen($data) > $maxSize) {
            throw new InvalidArgumentException("Los datos de impresión exceden el tamaño máximo permitido");
        }
    }

    private function validateResponse(Client $client): void
    {
        $statusCode = $client->statusCode;

        if ($statusCode === 401) {
            throw new RuntimeException("Autenticación requerida o credenciales inválidas");
        }

        if ($statusCode === 403) {
            throw new RuntimeException("Acceso denegado a la impresora especificada");
        }

        if ($statusCode === 404) {
            throw new RuntimeException("Impresora no encontrada");
        }

        if ($statusCode !== 200) {
            throw new RuntimeException(
                sprintf("Error HTTP %d: %s", $statusCode, $client->body ?: 'Error desconocido')
            );
        }
    }

    /**
     * Construcción IPP mejorada con soporte para más tipos de datos
     */
    private function buildIppRequest(
        string $operation,
        ?string $printer = null,
        ?string $jobName = null,
        ?string $data = null,
        array $options = []
    ): string {
        $request = pack('CC', ...self::IPP_VERSION);

        $operationCode = $this->getOperationCode($operation);
        $request .= pack('n', $operationCode);

        // Usar ID más robusto
        $requestId = $this->generateRequestId();
        $request .= pack('N', $requestId);

        // Construir atributos base
        $baseAttributes = [
            'attributes-charset' => ['utf-8'],
            'attributes-natural-language' => ['en-us'],
            'printer-uri' => [$this->buildPrinterUri($printer)],
            'requesting-user-name' => [$this->getRequestingUser()],
        ];

        if ($jobName) {
            $baseAttributes['job-name'] = [$jobName];
        }

        $request .= $this->buildAttributesGroup(0x01, $baseAttributes); // Operation attributes

        // Atributos de trabajo (solo para print-job)
        if ($operation === 'print-job' && !empty($options)) {
            $request .= $this->buildAttributesGroup(0x02, $options); // Job attributes
        }

        // Fin de atributos
        $request .= pack('C', 0x03);

        // Datos del trabajo
        if ($data !== null) {
            $request .= $data;
        }

        return $request;
    }

    private function buildAttributesGroup(int $groupTag, array $attributes): string
    {
        $result = pack('C', $groupTag);

        foreach ($attributes as $name => $values) {
            foreach ((array)$values as $value) {
                $result .= $this->buildAttribute($name, $value);
            }
        }

        return $result;
    }

    private function buildAttribute(string $name, mixed $value): string
    {
        $tag = $this->getValueTag($value);
        $result = pack('C', $tag);

        // Nombre del atributo
        $result .= pack('n', strlen($name));
        $result .= $name;

        // Valor según su tipo
        $encodedValue = $this->encodeIppValue($tag, $value);
        $result .= pack('n', strlen($encodedValue));
        $result .= $encodedValue;

        return $result;
    }

    private function getValueTag(mixed $value): int
    {
        return match(true) {
            is_int($value) => 0x21,      // integer
            is_bool($value) => 0x22,     // boolean
            $value instanceof DateTime => 0x31, // datetime
            default => 0x44              // keyword (default)
        };
    }

    private function encodeIppValue(int $tag, mixed $value): string
    {
        return match($tag) {
            0x21 => pack('N', (int)$value),  // integer
            0x22 => pack('C', (bool)$value), // boolean
            0x31 => $this->encodeDateTime($value), // datetime
            default => (string)$value
        };
    }

    private function encodeDateTime(DateTime $date): string
    {
        return pack('nCCCCCCCC',
            $date->format('Y'),
            $date->format('m'),
            $date->format('d'),
            $date->format('H'),
            $date->format('i'),
            $date->format('s'),
            0,  // deciseconds
            0,  // UTC offset direction
            0   // UTC offset hours
        );
    }

    private function buildPrinterUri(?string $printer): string
    {
        $base = sprintf('ipp://%s:%d', $this->host, $this->port);

        if ($printer) {
            return $base . '/printers/' . rawurlencode($printer);
        }

        return $base . '/';
    }

    private function generateRequestId(): int
    {
        try {
            return random_int(1, PHP_INT_MAX & 0xFFFFFFFF);
        } catch (RandomException $e) {
            // Fallback si random_int falla
            return (int)(microtime(true) * 1000) & 0xFFFFFFFF;
        }
    }

    private function getRequestingUser(): string
    {
        // Intentar obtener el usuario actual de manera más confiable
        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $userInfo = posix_getpwuid(posix_geteuid());
            return $userInfo['name'] ?? 'php-user';
        }

        return get_current_user() ?: 'php-user';
    }

    private ?string $authCookie = null;
    private ?int $authExpiry = null;

    public function setCredentials(string $username, string $password): void
    {
        $this->username = $username;
        $this->password = $password;

        $this->authenticate();
    }

    private function authenticate(): void
    {
        if (!$this->username || !$this->password) {
            return;
        }

        // Intentar autenticación básica primero
        $authString = base64_encode("{$this->username}:{$this->password}");
        $this->client->setHeaders(['Authorization' => "Basic {$authString}"]);

        // Obtener cookie de sesión como respaldo
        if ($this->needsAuthCookie()) {
            $this->obtainAuthCookie();
        }
    }

    private function needsAuthCookie(): bool
    {
        return !$this->authCookie ||
            !$this->authExpiry ||
            time() > $this->authExpiry;
    }

    private function obtainAuthCookie(): void
    {
        $tempClient = new Client($this->host, $this->port);
        $tempClient->set([
            'timeout' => $this->timeout,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'User-Agent' => 'Satelles-CUPS-Client/1.0'
            ]
        ]);

        $postData = http_build_query([
            'org.cups.sid' => uniqid('', true),
            'OP' => 'authenticate',
            'USERNAME' => $this->username,
            'PASSWORD' => $this->password
        ]);

        $tempClient->post('/admin', $postData);

        if ($tempClient->statusCode === 200) {
            $this->extractAuthCookie($tempClient);
        }

        $tempClient->close();
    }

    private function extractAuthCookie(Client $client): void
    {
        // Parsear cookies de los headers
        $headers = $client->headers ?? [];

        foreach ($headers as $header => $value) {
            if (strtolower($header) === 'set-cookie') {
                if (preg_match('/org\.cups\.sid=([^;]+)/', $value, $matches)) {
                    $this->authCookie = $matches[1];
                    $this->authExpiry = time() + 3600; // 1 hora típica

                    // Actualizar headers del cliente principal
                    $this->client->setHeaders([
                        'Cookie' => "org.cups.sid={$this->authCookie}"
                    ]);

                    break;
                }
            }
        }
    }
    private function parseIppResponse(string $response): array
    {
        if (strlen($response) < 8) {
            throw new RuntimeException("Respuesta IPP demasiado corta: " . strlen($response) . " bytes");
        }

        $result = [
            'version' => null,
            'status-code' => null,
            'status-message' => null,
            'request-id' => null,
            'attributes' => [],
            'data' => null
        ];

        $offset = 0;
        $length = strlen($response);

        // Parsear versión
        $versionData = unpack('Cmajor/Cminor', substr($response, $offset, 2));
        $result['version'] = "{$versionData['major']}.{$versionData['minor']}";
        $offset += 2;

        // Parsear código de estado
        $statusCode = unpack('ncode', substr($response, $offset, 2))['code'];
        $result['status-code'] = $statusCode;
        $result['status-message'] = $this->getStatusMessage($statusCode);
        $offset += 2;

        // Validar código de estado
        if (!$this->isSuccessfulStatus($statusCode)) {
            throw new RuntimeException(
                sprintf("Error IPP %d: %s", $statusCode, $result['status-message'])
            );
        }

        // Request ID
        $result['request-id'] = unpack('Nid', substr($response, $offset, 4))['id'];
        $offset += 4;

        // Parsear atributos
        $result['attributes'] = $this->parseAttributes(substr($response, $offset));

        return $result;
    }

    private function parseAttributes(string $data): array
    {
        $attributes = [];
        $currentGroup = null;
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length) {
            $tag = ord($data[$offset]);
            $offset++;

            // Fin de atributos
            if ($tag === 0x03) {
                break;
            }

            // Grupo de atributos
            if ($tag >= 0x00 && $tag <= 0x0F) {
                $currentGroup = $this->getGroupName($tag);
                continue;
            }

            // Atributo individual
            $attribute = $this->parseAttribute($tag, substr($data, $offset));
            $offset += $attribute['bytes_read'];

            // Almacenar atributo
            if ($currentGroup) {
                $attributes[$currentGroup][$attribute['name']] = $attribute['value'];
            } else {
                $attributes[$attribute['name']] = $attribute['value'];
            }
        }

        return $attributes;
    }

    private function parseAttribute(int $tag, string $data): array
    {
        $offset = 0;

        // Leer nombre del atributo
        $nameLength = unpack('n', substr($data, $offset, 2))[1];
        $offset += 2;
        $name = substr($data, $offset, $nameLength);
        $offset += $nameLength;

        // Leer valor
        $valueLength = unpack('n', substr($data, $offset, 2))[1];
        $offset += 2;
        $rawValue = substr($data, $offset, $valueLength);
        $offset += $valueLength;

        // Convertir valor según el tag
        $value = $this->convertIppValue($tag, $rawValue);

        return [
            'name' => $name,
            'value' => $value,
            'bytes_read' => $offset
        ];
    }

    private function isSuccessfulStatus(int $statusCode): bool
    {
        // Códigos IPP exitosos
        return in_array($statusCode, [
            0x0000, // successful-ok
            0x0001, // successful-ok-ignored-or-substituted-attributes
            0x0002, // successful-ok-conflicting-attributes
        ]);
    }

    private function getStatusMessage(int $statusCode): string
    {
        $messages = [
            0x0000 => 'Successful OK',
            0x0001 => 'Successful OK - ignored or substituted attributes',
            0x0002 => 'Successful OK - conflicting attributes',
            0x0400 => 'Client error - bad request',
            0x0401 => 'Client error - forbidden',
            0x0402 => 'Client error - not authenticated',
            0x0403 => 'Client error - not authorized',
            0x0404 => 'Client error - not possible',
            0x0405 => 'Client error - timeout',
            0x0406 => 'Client error - not found',
            0x0407 => 'Client error - gone',
            0x0408 => 'Client error - request entity too large',
            0x0409 => 'Client error - request value too long',
            0x040A => 'Client error - document format not supported',
            0x040B => 'Client error - attributes or values not supported',
            0x0500 => 'Server error - internal error',
            0x0501 => 'Server error - operation not supported',
            0x0502 => 'Server error - service unavailable',
            0x0503 => 'Server error - version not supported',
            0x0504 => 'Server error - device error',
            0x0505 => 'Server error - temporary error',
            0x0506 => 'Server error - not accepting jobs',
            0x0507 => 'Server error - busy',
            0x0508 => 'Server error - job canceled',
            0x0509 => 'Server error - multiple document jobs not supported',
        ];

        return $messages[$statusCode] ?? "Unknown status code: 0x" . dechex($statusCode);
    }

    private function getGroupName(int $tag): string
    {
        $groups = [
            0x00 => 'operation-attributes',
            0x01 => 'job-attributes',
            0x02 => 'printer-attributes',
            0x03 => 'unsupported-attributes',
            0x04 => 'subscription-attributes',
            0x05 => 'event-notification-attributes',
            0x06 => 'resource-attributes',
            0x07 => 'document-attributes',
        ];

        return $groups[$tag] ?? "unknown-group-{$tag}";
    }

    /**
     * Obtiene el estado de una impresora específica
     */
    public function getPrinterState(string $printer): array
    {
        $this->validatePrinterName($printer);

        return $this->executeWithRetry(function() use ($printer) {
            $ippRequest = $this->buildIppRequest('get-printer-attributes', $printer);
            $path = '/printers/' . rawurlencode($printer);

            $this->client->post($path, $ippRequest);
            $this->validateResponse($this->client);

            $response = $this->parseIppResponse($this->client->body);

            return $this->extractPrinterState($response);
        });
    }

    private function extractPrinterState(array $response): array
    {
        $attrs = $response['attributes']['printer-attributes'] ?? [];

        $states = [
            3 => 'idle',
            4 => 'printing',
            5 => 'stopped',
        ];

        $reasons = [
            'none' => 'No issues',
            'other' => 'Unknown issue',
            'media-jam' => 'Paper jam',
            'media-empty' => 'Out of paper',
            'media-low' => 'Low paper',
            'toner-empty' => 'Out of toner',
            'toner-low' => 'Low toner',
            'door-open' => 'Door open',
            'offline' => 'Printer offline',
            'paused' => 'Printer paused',
        ];

        return [
            'state' => $states[$attrs['printer-state'] ?? 3] ?? 'unknown',
            'reasons' => array_map(
                fn($r) => $reasons[$r] ?? $r,
                (array)($attrs['printer-state-reasons'] ?? ['none'])
            ),
            'accepting_jobs' => (bool)($attrs['printer-is-accepting-jobs'] ?? true),
            'queued_jobs' => $attrs['queued-job-count'] ?? 0,
            'info' => $attrs['printer-info'] ?? '',
            'location' => $attrs['printer-location'] ?? '',
            'make_and_model' => $attrs['printer-make-and-model'] ?? '',
        ];
    }

    /**
     * Lista trabajos en una impresora
     */
    public function getJobs(string $printer, string $whichJobs = 'not-completed'): array
    {
        $this->validatePrinterName($printer);

        return $this->executeWithRetry(function() use ($printer, $whichJobs) {
            $ippRequest = $this->buildIppRequest('get-jobs', $printer, null, null, [
                'which-jobs' => [$whichJobs],
                'requested-attributes' => [
                    'job-id',
                    'job-name',
                    'job-state',
                    'job-originating-user-name',
                    'job-media-sheets-completed',
                    'time-at-creation'
                ]
            ]);

            $this->client->post('/printers/' . rawurlencode($printer), $ippRequest);
            $this->validateResponse($this->client);

            $response = $this->parseIppResponse($this->client->body);

            return $this->extractJobs($response);
        });
    }

    private function extractJobs(array $response): array
    {
        $jobs = [];
        $jobAttrs = $response['attributes']['job-attributes'] ?? [];

        foreach ($jobAttrs as $key => $value) {
            if (preg_match('/^job-id-(\d+)$/', $key, $matches)) {
                $jobId = $matches[1];
                $jobs[$jobId] = [
                    'id' => $jobId,
                    'name' => $jobAttrs["job-name-{$jobId}"] ?? 'Unknown',
                    'state' => $this->getJobState($jobAttrs["job-state-{$jobId}"] ?? 0),
                    'user' => $jobAttrs["job-originating-user-name-{$jobId}"] ?? 'unknown',
                    'pages' => $jobAttrs["job-media-sheets-completed-{$jobId}"] ?? 0,
                    'created' => $this->parseIppDateTime($jobAttrs["time-at-creation-{$jobId}"] ?? null),
                ];
            }
        }

        return $jobs;
    }

    private function getJobState(int $state): string
    {
        return match($state) {
            3 => 'pending',
            4 => 'held',
            5 => 'processing',
            6 => 'stopped',
            7 => 'canceled',
            8 => 'aborted',
            9 => 'completed',
            default => 'unknown'
        };
    }

    private function parseIppDateTime(?string $value): ?DateTime
    {
        if (!$value || strlen($value) < 11) {
            return null;
        }

        $data = unpack('nyear/Cmonth/Cday/Chour/Cminute/Csecond/Cdeciseconds/Cdirection/Chours_from_utc', $value);

        $date = new DateTime();
        $date->setDate($data['year'], $data['month'], $data['day']);
        $date->setTime($data['hour'], $data['minute'], $data['second']);

        return $date;
    }

    /**
     * Cancela un trabajo de impresión
     */
    public function cancelJob(string $printer, int $jobId): bool
    {
        $this->validatePrinterName($printer);

        return $this->executeWithRetry(function() use ($printer, $jobId) {
            $ippRequest = $this->buildIppRequest('cancel-job', $printer, null, null, [
                'job-id' => [$jobId]
            ]);

            $this->client->post('/printers/' . rawurlencode($printer), $ippRequest);

            return $this->client->statusCode === 200;
        });
    }

    /**
     * Health check del servidor CUPS
     */
    public function healthCheck(): array
    {
        try {
            $startTime = microtime(true);

            $version = $this->getVersion();
            $printers = $this->getPrinters();

            $responseTime = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'healthy',
                'version' => $version,
                'printers_count' => count($printers),
                'response_time_ms' => round($responseTime, 2),
                'timestamp' => date('c')
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    }

    private ?LoggerInterface $logger = null;
    private bool $debug = false;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger instanceof LoggerInterface && method_exists($this->logger, $level)) {
            $this->logger->{$level}($message, $context);
            return;
        }

        if ($this->debug) {
            error_log(sprintf(
                '[CUPS][%s] %s %s',
                strtoupper($level),
                $message,
                $context ? json_encode($context) : ''
            ));
        }
    }

    private function logRequest(string $method, string $path, $data = null): void
    {
        $this->log('debug', "Request: {$method} {$path}", [
            'data_length' => $data ? strlen($data) : 0
        ]);
    }

    private function logResponse(int $statusCode, $body = null): void
    {
        $this->log('debug', "Response: {$statusCode}", [
            'body_length' => $body ? strlen($body) : 0
        ]);
    }

#######################################################################
    public function debugAuth(): void
    {
        if(!$this->debug) {
            return;
        }
        $this->log('debug', 'Debug Auth');
        $this->log('debug', 'Client: ' . $this->client->host . ':' . $this->client->port);
        $this->log('debug', 'Username: ' . $this->username);
        $this->log('debug', 'Password: ' . $this->password);
        $this->log('debug', 'Cookies: ' . json_encode($this->client->cookies));
        $this->client->get('/admin');
        echo "Status: {$this->client->statusCode}\n";
        echo "Headers:\n";
        print_r($this->client->headers);
        echo "Body:\n";
        echo substr($this->client->body, 0, 500); // Primeros 500 caracteres
    }
    /**
     * Obtiene cookie de autenticación mediante solicitud previa
     */

    /**
     * Verifica si el cliente está conectado
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->client->connected;
    }

    /**
     * Cierra la conexión con el servidor CUPS
     */
    public function disconnect(): void
    {
        if ($this->client && $this->isConnected()) {
            $this->client->close();
        }
    }

    /**
     * Obtiene información sobre las impresoras disponibles
     *
     * @return array Lista de impresoras con sus atributos
     * @throws InvalidArgumentException
     * @throws RandomException
     * @throws RuntimeException Si hay un error al obtener la información
     */
    public function getPrinters(): array
    {
        $ippRequest = $this->buildIppRequest('get-printers');

        $this->client->post('/', $ippRequest);
        // Si recibimos 401 (Unauthorized), intentamos con autenticación
        if ($this->client->statusCode === 401 && $this->username && $this->password) {
            // Reintentar con autenticación
            $this->setCredentials($this->username, $this->password);
            $this->client->post('/', $ippRequest);
        }

        if ($this->client->statusCode !== 200) {
            $this->debugAuth();
            throw new RuntimeException("Error al obtener impresoras: " . ($this->client->body ?: 'Código de estado ' . $this->client->statusCode));
        }

        $response = $this->parseIppResponse($this->client->body);
        return $response['attributes']['printer-attributes'] ?? [];
    }

    public function getPrintersViaSystem(): array
    {
        $output = shell_exec('lpstat -a 2>&1') ?? '';

        if ($output === '' || !str_contains($output, 'accepting requests')) {
            throw new RuntimeException("No se pudo obtener la lista de impresoras: $output");
        }

        $printers = [];
        foreach (explode("\n", trim($output)) as $line) {
            if (preg_match('/^([^\s]+)\s/', $line, $matches)) {
                $printers[] = $matches[1];
            }
        }

        return $printers;
    }

    /**
     * Obtiene el código de operación IPP
     *
     * @param string $operation Nombre de la operación
     * @return int Código de operación
     * @throws InvalidArgumentException Si la operación no es válida
     */
    private function getOperationCode(string $operation): int
    {
        $operations = [
            'print-job' => 0x0002,
            'get-printers' => 0x0004,
            'get-jobs' => 0x000A,
            'get-printer-attributes' => 0x000B,
            'get-version' => 0x000B, // OJO: revisar según la operación real que quieres usar
        ];

        if (!isset($operations[$operation])) {
            throw new InvalidArgumentException("Operación IPP no válida: $operation");
        }

        return $operations[$operation];
    }

    /**
     * Convierte el valor IPP según su tipo de dato
     * @param int $tag
     * @param string $value
     * @return array|bool|mixed|string
     */
    private function convertIppValue(int $tag, string $value): mixed
    {
        switch ($tag) {
            case 0x21: // integer
                return unpack('N', $value)[1];
            case 0x22: // boolean
                return (bool)unpack('C', $value)[1];
            case 0x23: // enum
                return unpack('n', $value)[1];
            case 0x30: // octetString
            case 0x31: // dateTime
            case 0x32: // resolution
            case 0x33: // rangeOfInteger
                return $value; // Devolver como binario
            case 0x34: // textWithLanguage
            case 0x35: // nameWithLanguage
                $langLength = unpack('n', substr($value, 0, 2))[1];
                $textLength = unpack('n', substr($value, 2 + $langLength, 2))[1];
                return [
                    'language' => substr($value, 2, $langLength),
                    'value' => substr($value, 4 + $langLength, $textLength)
                ];
            case 0x41: // textWithoutLanguage
            case 0x42: // nameWithoutLanguage
            case 0x44: // keyword
            case 0x45: // uri
            case 0x46: // uriScheme
            case 0x47: // charset
            case 0x48: // naturalLanguage
                return $value; // Devolver como string
            default:
                return $value; // Valor desconocido, devolver como está
        }
    }

    /**
     * Obtiene la versión del servidor CUPS
     * @return string
     * @throws RuntimeException
     */
    public function getVersion(): string
    {
        try {
            // Intentar obtener la versión mediante IPP
            return $this->getVersionIpp();
        } catch (RuntimeException $e) {

            try {
                // Si falla, intentar obtenerla por HTTP
                return $this->getVersionHttp();
            } catch (RuntimeException $e) {
                // Si falla en ambos casos, lanzar la excepción
                throw new RuntimeException("Error al obtener la versión de CUPS: " . $e->getMessage());
            }
        }
    }

    /**
     * Obtiene la versión del servidor CUPS mediante IPP
     *
     * @return string Versión de CUPS (ej: "2.3.1")
     * @throws RuntimeException Si no se puede obtener la versión
     */
    public function getVersionIpp(): string
    {
        try {
            $ippRequest = $this->buildIppRequest('get-version');
            $this->client->post('/admin/', $ippRequest);

            if ($this->client->statusCode !== 200) {
                throw new RuntimeException("Error al obtener versión: HTTP {$this->client->statusCode}");
            }

            $response = $this->parseIppResponse($this->client->body);

            // Buscar el atributo que contiene la versión
            foreach ($response['attributes'] as $group) {
                if (isset($group['printer-version'])) {
                    $version = $group['printer-version'];
                    return $this->formatIppVersion($version);
                }
            }

            throw new RuntimeException("No se encontró la versión en la respuesta");
        } catch (Exception $e) {
            throw new RuntimeException("Error al obtener versión CUPS: " . $e->getMessage());
        }
    }

    /**
     * Obtiene la versión de CUPS mediante el endpoint HTTP
     *
     * @return string Versión de CUPS
     * @throws RuntimeException Si no se puede obtener la versión
     */
    public function getVersionHttp(): string
    {
        try {
            $this->client->get('/');

            if ($this->client->statusCode !== 200) {
                throw new RuntimeException("Error al obtener versión: HTTP {$this->client->statusCode}");
            }

            // Buscar en las cabeceras o en el body
            if (preg_match('/CUPS\/([\d.]+)/i', $this->client->body, $matches)) {
                return $matches[1];
            }

            throw new RuntimeException("No se encontró la versión en la respuesta HTTP");
        } catch (Exception $e) {
            throw new RuntimeException("Error al obtener versión CUPS via HTTP: " . $e->getMessage());
        }
    }

    /**
     * Formatea la versión IPP (16 bits) a versión legible
     * Ej: 0x020301 → "2.3.1"
     */
    private function formatIppVersion(int $ippVersion): string
    {
        $major = ($ippVersion >> 16) & 0xFF;
        $minor = ($ippVersion >> 8) & 0xFF;
        $patch = $ippVersion & 0xFF;

        return "$major.$minor.$patch";
    }

    /**
     * Destructor de la clase CupsClient
     * Cierra la conexión al servidor CUPS al destruir el objeto.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}