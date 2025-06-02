<?php

namespace Tabula17\Satelles\Utilis\Print;

use Exception;
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

    /**
     * Constructor de la clase CupsClient
     * Inicializa el cliente HTTP para conectarse al servidor CUPS.
     *
     *
     * @param string $host Dirección del servidor CUPS (por defecto 'localhost')
     * @param int $port Puerto del servidor CUPS (por defecto 631)
     * @param float $timeout Tiempo de espera para la conexión (por defecto 5.0 segundos)
     */
    public function __construct(string  $host = 'localhost', int $port = 631, float $timeout = 5.0,
                                ?string $username = null,
                                ?string $password = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->username = $username;
        $this->password = $password;


        // Inicializamos el cliente pero no conectamos aún
        $this->client = new Client($this->host, $this->port);
        $this->client->set([
            'timeout' => $timeout,
            'headers' => [
                'Content-Type' => 'application/ipp',
                'Accept' => 'application/ipp'
            ]
        ]);
        // Configurar autenticación básica si hay credenciales
        if ($this->username && $this->password) {
            $this->setCredentials($this->username, $this->password);
        }

    }
    public function debugAuth(): void
    {
        $this->client->get('/admin');
        echo "Status: {$this->client->statusCode}\n";
        echo "Headers:\n";
        print_r($this->client->headers);
        echo "Body:\n";
        echo substr($this->client->body, 0, 500); // Primeros 500 caracteres
    }

    public function setCredentials(string $username, string $password): void
    {
        $this->username = $username;
        $this->password = $password;
        // Actualizar las cabeceras del cliente
        if ($this->username && $this->password) {
            $authString = base64_encode("{$this->username}:{$this->password}");
            $this->client->setHeaders([
                'Authorization' => "Basic $authString",
                'Cookie' => $this->getAuthCookie() // Intenta con cookie si Basic Auth falla
            ]);
        } else {
            // Si no hay credenciales, eliminar la cabecera de autorización
            $this->client->setHeaders([
                'Authorization' => null
            ]);
        }
    }

    /**
     * Obtiene cookie de autenticación mediante solicitud previa
     */
    private function getAuthCookie(): string
    {
        $tempClient = new Client($this->host, $this->port);
        $tempClient->set([
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$this->username}:{$this->password}"),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);

        // Simulamos login como lo hace la interfaz web
        $tempClient->post('/admin', [
            'org.cups.sid' => '1',
            'OP' => 'login',
            'USERID' => $this->username,
            'PASSWD' => $this->password
        ]);

        $cookie = $tempClient->set_cookie_headers['org.cups.sid'] ?? '';
        $tempClient->close();

        return $cookie;
    }

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
     * Envía un trabajo de impresión al servidor CUPS
     *
     * @param string $printer Nombre de la impresora
     * @param string $data Datos a imprimir
     * @param string $jobName Nombre del trabajo (opcional)
     * @param array $options Opciones de impresión (opcional)
     * @return array Respuesta del servidor
     * @throws RuntimeException Si hay un error al enviar el trabajo
     */
    public function printJob(string $printer, string $data, string $jobName = 'PHP Job', array $options = []): array
    {
        // Construir la solicitud IPP
        $ippRequest = $this->buildIppRequest(
            'print-job',
            $printer,
            $jobName,
            $data,
            $options
        );

        // La conexión se establece automáticamente aquí
        $this->client->post('/printers/' . rawurlencode($printer), $ippRequest);

        if ($this->client->statusCode !== 200) {
            throw new RuntimeException("Error al enviar trabajo de impresión: " .
                ($this->client->body ?: 'Código de estado ' . $this->client->statusCode));
        }

        return $this->parseIppResponse($this->client->body);
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

        return $this->parseIppResponse($this->client->body);
    }

    public function getPrintersViaSystem(): array
    {
        $output = shell_exec('lpstat -a 2>&1');

        if (strpos($output, 'accepting requests') === false) {
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
     * Construye una solicitud IPP
     *
     * @param string $operation Operación IPP
     * @param string|null $printer Nombre de la impresora (opcional)
     * @param string|null $jobName Nombre del trabajo (opcional)
     * @param string|null $data Datos a imprimir (opcional)
     * @param array $options Opciones adicionales (opcional)
     * @return string Solicitud IPP binaria
     * @throws RandomException|InvalidArgumentException
     */
    private function buildIppRequest(string $operation, ?string $printer = null, ?string $jobName = null, ?string $data = null, array $options = []): string
    {
        // Versión IPP (1.1)
        $request = pack('CC', 0x01, 0x01);

        // Operación (print-job, get-printers, etc.)
        $operationCode = $this->getOperationCode($operation);
        $request .= pack('n', $operationCode);

        // Request ID (único para esta solicitud)
        $requestId = random_int(1, 65535);
        $request .= pack('N', $requestId);

        // Atributos de operación
        $request .= $this->buildAttributes([
            'attributes-charset' => ['utf-8'],
            'attributes-natural-language' => ['en'],
            'printer-uri' => ['ipp://' . $this->host . ':' . $this->port . '/printers/' . ($printer ?? '')],
            'requesting-user-name' => [get_current_user()],
            'job-name' => [$jobName ?? 'PHP Job']
        ]);

        // Opciones adicionales
        if (!empty($options)) {
            $request .= $this->buildAttributes($options);
        }

        // End of attributes
        $request .= pack('C', 0x03);

        // Datos del trabajo (si se proporcionan)
        if ($data !== null) {
            $request .= $data;
        }

        return $request;
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
        ];

        if (!isset($operations[$operation])) {
            throw new InvalidArgumentException("Operación IPP no válida: $operation");
        }

        return $operations[$operation];
    }

    /**
     * Construye atributos IPP
     *
     * @param array $attributes Atributos a incluir
     * @return string Cadena binaria con los atributos
     */
    private function buildAttributes(array $attributes): string
    {
        $result = '';

        foreach ($attributes as $name => $values) {
            foreach ((array)$values as $value) {
                // Attribute tag (0x47 = keyword)
                $result .= pack('C', 0x47);

                // Name length
                $result .= pack('n', strlen($name));
                $result .= $name;

                // Value length
                $result .= pack('n', strlen($value));
                $result .= $value;
            }
        }

        return $result;
    }

    /**
     * Parsea la respuesta IPP
     *
     * @param string $response Respuesta binaria IPP
     * @return array Datos parseados
     * @throws RuntimeException Si la respuesta es demasiado corta o no es válida
     */
    private function parseIppResponse(string $response): array
    {
        $result = [
            'version' => null,
            'status-code' => null,
            'request-id' => null,
            'attributes' => [],
            'data' => null
        ];

        $offset = 0;
        $length = strlen($response);

        // 1. Parsear encabezado (8 bytes)
        if ($length < 8) {
            throw new RuntimeException("Respuesta IPP demasiado corta");
        }

        // Versión (2 bytes)
        $result['version'] = unpack('Cmajor/Cminor', substr($response, $offset, 2));
        $offset += 2;

        // Código de estado (2 bytes)
        $result['status-code'] = unpack('ncode', substr($response, $offset, 2))['code'];
        $offset += 2;

        // Request ID (4 bytes)
        $result['request-id'] = unpack('Nid', substr($response, $offset, 4))['id'];
        $offset += 4;

        // 2. Parsear atributos
        $currentGroup = null;

        while ($offset < $length) {
            $tag = ord($response[$offset]);
            $offset++;

            // Fin de atributos
            if ($tag === 0x03) {
                break;
            }

            // Grupo de atributos
            if ($tag === 0x04) {
                $nameLength = unpack('n', substr($response, $offset, 2))[1];
                $offset += 2;
                $currentGroup = substr($response, $offset, $nameLength);
                $offset += $nameLength;
                continue;
            }

            // Atributo normal
            $nameLength = unpack('n', substr($response, $offset, 2))[1];
            $offset += 2;
            $name = substr($response, $offset, $nameLength);
            $offset += $nameLength;

            $valueLength = unpack('n', substr($response, $offset, 2))[1];
            $offset += 2;
            $value = substr($response, $offset, $valueLength);
            $offset += $valueLength;

            // Convertir según el tipo de dato
            $value = $this->convertIppValue($tag, $value);

            // Almacenar el atributo
            if ($currentGroup) {
                $result['attributes'][$currentGroup][$name] = $value;
            } else {
                $result['attributes'][$name] = $value;
            }
        }

        // 3. Los bytes restantes son los datos (si existen)
        if ($offset < $length) {
            $result['data'] = substr($response, $offset);
        }

        return $result;
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