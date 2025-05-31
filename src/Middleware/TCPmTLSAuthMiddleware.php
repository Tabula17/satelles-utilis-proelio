<?php

namespace Tabula17\Satelles\Utilis\Middleware;

use Swoole\Server;
use RuntimeException;

/**
 * Middleware para autenticación mTLS en servidores TCP Swoole.
 */
class TCPmTLSAuthMiddleware
{
    /**
     * Lista de clientes autorizados (Common Names)
     *
     * @var array<string>
     */
    private array $allowedClients = [];

    /**
     * Agrega un cliente a la lista de autorizados
     *
     * @param string $commonName Common Name del certificado del cliente
     * @return void
     */
    public function allowClient(string $commonName): void
    {
        if (!in_array($commonName, $this->allowedClients)) {
            $this->allowedClients[] = $commonName;
        }
    }

    /**
     * Revoca la autorización de un cliente
     *
     * @param string $commonName Common Name del certificado del cliente
     * @return void
     */
    public function revokeClient(string $commonName): void
    {
        $key = array_search($commonName, $this->allowedClients);
        if ($key !== false) {
            unset($this->allowedClients[$key]);
            $this->allowedClients = array_values($this->allowedClients); // Reindexar array
        }
    }

    /**
     * Obtiene la lista de clientes autorizados
     *
     * @return array<string>
     */
    public function getAllowedClients(): array
    {
        return $this->allowedClients;
    }

    /**
     * Maneja la autenticación mTLS de una conexión
     *
     * @param Server $server Servidor Swoole
     * @param int $fd File descriptor de la conexión
     * @param string $data Datos recibidos
     * @param callable $next Siguiente middleware o handler
     * @throws RuntimeException Si no se puede procesar el certificado
     * @return void
     */
    public function handle(Server $server, int $fd, string $data, callable $next): void
    {
        $clientInfo = $server->getClientInfo($fd);

        if (!$this->validateTLSConnection($clientInfo)) {
            $this->rejectConnection($server, $fd, "Autenticación mTLS requerida");
            return;
        }

        $clientCert = $clientInfo['ssl_client_cert'] ?? '';
        if (empty($clientCert)) {
            $this->rejectConnection($server, $fd, "Certificado de cliente no encontrado");
            return;
        }

        try {
            $certInfo = $this->parseCertificate($clientCert);
            $commonName = $certInfo['subject']['CN'] ?? '';

            if (empty($commonName)) {
                $this->rejectConnection($server, $fd, "Certificado sin Common Name");
                return;
            }

            if (!$this->isClientAllowed($commonName)) {
                $this->rejectConnection($server, $fd, "Cliente no autorizado: {$commonName}");
                return;
            }

            // Preparar contexto con información validada
            $context = [
                'fd' => $fd,
                'client_cn' => $commonName,
                'cert_info' => $certInfo,
                'data' => $data,
                'client_info' => $clientInfo
            ];

            $next($server, $context);

        } catch (RuntimeException $e) {
            $this->rejectConnection($server, $fd, "Error procesando certificado: " . $e->getMessage());
        }
    }

    /**
     * Valida que la conexión use TLS correctamente
     *
     * @param array $clientInfo
     * @return bool
     */
    private function validateTLSConnection(array $clientInfo): bool
    {
        return !empty($clientInfo['ssl_client_verify']) 
            && $clientInfo['ssl_client_verify'] === 'SUCCESS';
    }

    /**
     * Verifica si un cliente está autorizado
     *
     * @param string $commonName
     * @return bool
     */
    private function isClientAllowed(string $commonName): bool
    {
        return in_array($commonName, $this->allowedClients);
    }

    /**
     * Parsea un certificado X509
     *
     * @param string $cert
     * @return array
     * @throws RuntimeException
     */
    private function parseCertificate(string $cert): array
    {
        $certInfo = openssl_x509_parse($cert);
        if ($certInfo === false) {
            throw new RuntimeException("No se pudo parsear el certificado");
        }
        return $certInfo;
    }

    /**
     * Rechaza una conexión con un mensaje de error
     *
     * @param Server $server
     * @param int $fd
     * @param string $message
     * @return void
     */
    private function rejectConnection(Server $server, int $fd, string $message): void
    {
        $server->send($fd, "ERROR: {$message}\n");
        $server->close($fd);
    }
}

/*
// Ejemplo de uso:
$middleware = new TCPmTLSAuthMiddleware();

// Agregar clientes autorizados
$middleware->allowClient('client1.example.com');
$middleware->allowClient('client2.example.com');

// Configurar el servidor Swoole
$server->set([
    'ssl_cert_file' => '/path/to/server.crt',
    'ssl_key_file' => '/path/to/server.key',
    'ssl_ca_file' => '/path/to/ca.crt',
    'ssl_verify_peer' => true,
    'ssl_client_cert_file' => true,
]);

// Manejar conexiones
$server->on('receive', function (Server $server, int $fd, int $reactorId, string $data) use ($middleware) {
    $middleware->handle($server, $fd, $data, function ($server, $context) {
        // Lógica de negocio aquí
        $response = sprintf(
            "Cliente %s autenticado correctamente.\nMensaje recibido: %s\n",
            $context['client_cn'],
            trim($context['data'])
        );
        $server->send($context['fd'], $response);
    });
});
*/
