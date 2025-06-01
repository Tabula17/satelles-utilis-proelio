<?php

namespace Tabula17\Satelles\Utilis\Middleware;

use Swoole\Server;
use RuntimeException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Middleware para autenticación mTLS en servidores TCP Swoole.
 *
 * Proporciona autenticación mutua TLS (mTLS) verificando:
 * - Validez del certificado del cliente
 * - Common Name autorizado
 * - Vigencia del certificado
 * - Revocación de clientes
 */
class TCPmTLSAuthMiddleware
{
    private const ERROR_TLS_REQUIRED = "Autenticación mTLS requerida";
    private const ERROR_NO_CERT = "Certificado de cliente no encontrado";
    private const ERROR_CERT_EXPIRED = "Certificado expirado o no válido aún";
    private const ERROR_NO_CN = "Certificado sin Common Name válido";
    private const ERROR_CLIENT_UNAUTHORIZED = "Cliente no autorizado";

    /**
     * Lista de clientes autorizados (Common Names o patrones)
     * @var array<string>
     */
    private array $allowedClients = [];

    /**
     * Cache de certificados ya parseados
     * @var array<string, array>
     */
    private array $certificateCache = [];

    /**
     * @var LoggerInterface Logger para registrar eventos
     */
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface|null $logger Instancia de logger (opcional)
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Agrega un cliente a la lista de autorizados
     *
     * @param string $commonName Common Name del certificado del cliente
     * @throws InvalidArgumentException Si el CN está vacío
     */
    public function allowClient(string $commonName): void
    {
        $commonName = trim($commonName);
        if (empty($commonName)) {
            throw new InvalidArgumentException("Common Name no puede estar vacío");
        }

        if (!in_array($commonName, $this->allowedClients)) {
            $this->allowedClients[] = $commonName;
            $this->logger->info("Cliente autorizado añadido", ['cn' => $commonName]);
        }
    }

    /**
     * Agrega múltiples clientes a la lista de autorizados
     *
     * @param array<string> $clients Lista de Common Names
     */
    public function allowClients(array $clients): void
    {
        foreach ($clients as $client) {
            $this->allowClient($client);
        }
    }

    /**
     * Revoca la autorización de un cliente
     *
     * @param string $commonName Common Name del certificado del cliente
     */
    public function revokeClient(string $commonName): void
    {
        $key = array_search($commonName, $this->allowedClients);
        if ($key !== false) {
            unset($this->allowedClients[$key]);
            $this->allowedClients = array_values($this->allowedClients);
            $this->logger->info("Cliente revocado", ['cn' => $commonName]);
        }
    }

    /**
     * Maneja la autenticación mTLS de una conexión
     *
     * @param Server $server Instancia del servidor Swoole
     * @param int $fd File descriptor de la conexión
     * @param string $data Datos recibidos
     * @param callable $next Callback para continuar el procesamiento
     */
    public function handle(Server $server, int $fd, string $data, callable $next): void
    {
        $clientInfo = $server->getClientInfo($fd);

        if (!$this->validateTLSConnection($clientInfo)) {
            $this->rejectConnection($server, $fd, self::ERROR_TLS_REQUIRED);
            return;
        }

        try {
            $certInfo = $this->parseCertificate($clientInfo['ssl_client_cert']);

            if (!$this->validateCertificateLifetime($certInfo)) {
                $this->rejectConnection($server, $fd, self::ERROR_CERT_EXPIRED);
                return;
            }

            $commonName = $this->extractCommonName($certInfo);
            if ($commonName === null) {
                $this->rejectConnection($server, $fd, self::ERROR_NO_CN);
                return;
            }

            if (!$this->isClientAllowed($commonName)) {
                $this->rejectConnection($server, $fd, self::ERROR_CLIENT_UNAUTHORIZED . ": $commonName");
                return;
            }

            $this->processValidConnection($server, $fd, $data, $next, $commonName, $certInfo, $clientInfo);

        } catch (RuntimeException $e) {
            $this->logger->error("Error procesando certificado", [
                'fd' => $fd,
                'error' => $e->getMessage()
            ]);
            $this->rejectConnection($server, $fd, "Error de autenticación: " . $e->getMessage());
        }
    }

    /**
     * Procesa una conexión válida
     */
    private function processValidConnection(
        Server $server,
        int $fd,
        string $data,
        callable $next,
        string $commonName,
        array $certInfo,
        array $clientInfo
    ): void {
        $this->logger->debug("Conexión mTLS autorizada", [
            'fd' => $fd,
            'client' => $commonName,
            'valid_from' => date('Y-m-d', $certInfo['validFrom_time_t']),
            'valid_to' => date('Y-m-d', $certInfo['validTo_time_t']),
        ]);

        $next($server, [
            'fd' => $fd,
            'client_cn' => $commonName,
            'cert_info' => $certInfo,
            'data' => $data,
            'client_info' => $clientInfo,
            'auth_time' => time()
        ]);
    }

    /**
     * Valida que la conexión use TLS correctamente
     */
    private function validateTLSConnection(array $clientInfo): bool
    {
        return isset($clientInfo['ssl_client_verify'])
            && $clientInfo['ssl_client_verify'] === 'SUCCESS'
            && !empty($clientInfo['ssl_client_cert']);
    }

    /**
     * Extrae el Common Name del certificado
     */
    private function extractCommonName(array $certInfo): ?string
    {
        $subject = $certInfo['subject'] ?? [];

        if (isset($subject['CN'])) {
            return $subject['CN'];
        }

        // Alternativa: buscar en subjectAltName
        $extensions = $certInfo['extensions'] ?? [];
        if (isset($extensions['subjectAltName'])) {
            if (preg_match('/DNS:([^\s,]+)/', $extensions['subjectAltName'], $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Verifica si un cliente está autorizado
     */
    private function isClientAllowed(string $commonName): bool
    {
        foreach ($this->allowedClients as $allowedPattern) {
            if (fnmatch($allowedPattern, $commonName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Valida la vigencia del certificado
     */
    private function validateCertificateLifetime(array $certInfo): bool
    {
        $now = time();
        return $now >= ($certInfo['validFrom_time_t'] ?? 0)
            && $now <= ($certInfo['validTo_time_t'] ?? PHP_INT_MAX);
    }

    /**
     * Parsea un certificado X509 con cache
     */
    private function parseCertificate(string $cert): array
    {
        $cacheKey = md5($cert);
        if (isset($this->certificateCache[$cacheKey])) {
            return $this->certificateCache[$cacheKey];
        }

        $certInfo = openssl_x509_parse($cert);
        if ($certInfo === false) {
            throw new RuntimeException("No se pudo parsear el certificado");
        }

        $this->certificateCache[$cacheKey] = $certInfo;
        return $certInfo;
    }

    /**
     * Rechaza una conexión con un mensaje de error
     */
    private function rejectConnection(Server $server, int $fd, string $message): void
    {
        $this->logger->warning("Conexión rechazada", [
            'fd' => $fd,
            'reason' => $message
        ]);

        $server->send($fd, "ERROR: $message\n");
        $server->close($fd);
    }

    /**
     * Obtiene la lista de clientes autorizados
     * @return array<string>
     */
    public function getAllowedClients(): array
    {
        return $this->allowedClients;
    }

    /**
     * Limpia la cache de certificados
     */
    public function clearCertificateCache(): void
    {
        $this->certificateCache = [];
    }
}