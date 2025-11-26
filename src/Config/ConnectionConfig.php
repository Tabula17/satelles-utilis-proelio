<?php

namespace Tabula17\Satelles\Utilis\Config;

use Tabula17\Satelles\Utilis\Exception\ExceptionDefinitions;
use Tabula17\Satelles\Utilis\Exception\InvalidArgumentException;

class ConnectionConfig extends AbstractDescriptor
{

    /**
     * The name of the connection configuration.
     * This is used to uniquely identify the connection within the application.
     * @var string $name
     */
    protected(set) string $name {
        set(string $name) {
            $this->name = $name;
            $this->configId = uniqid($name . '::', false);
        }
    }
    /**
     * The host address or IP of the database server.
     * @var string $host
     */
    protected(set) string $host = 'localhost' {
        set(string $host) {
            if (empty($host)) {
                throw new InvalidArgumentException(ExceptionDefinitions::HOST_CANNOT_BE_EMPTY->value);
            }
            if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) ||
                filter_var($host, FILTER_VALIDATE_IP)) {
                $this->host = $host;
            } else {
                throw new InvalidArgumentException(sprintf(ExceptionDefinitions::HOST_INVALID->value, $host));
            }
        }
    }
    /**
     * The port number used to connect to the database server.
     * @var int|null $port
     */
    protected(set) ?int $port {
        set(?int $port) {
            if ($port !== null && ($port < 1 || $port > 65535)) {
                throw new InvalidArgumentException(ExceptionDefinitions::PORT_INVALID->value);
            }
            $this->port = $port;
        }
    }
    /**
     * The username used to authenticate with the database server.
     * @var string|null $username
     */
    protected(set) ?string $username;
    /**
     * The password associated with the provided username.
     * @var string|null $password
     */
    protected(set) ?string $password;
    /**
     * The name of the database to connect to.
     * @var string|null $dbname
     */
    protected(set) ?string $dbname;
    /**
     * The name of the collection to use for this connection.
     * @var string|null $collection
     */
    protected(set) ?string $collection;
    /**
     * Additional options to be passed to the PDO connection.
     * @var array $options
     */
    protected(set) array $options = [];
    /**
     * The path to the Unix socket file used for local connections.
     * @var string|null $unixSocket
     */
    protected(set) ?string $unixSocket;
    /**
     * The character set used for the connection.
     * @var string|null $charset
     */
    protected(set) ?string $charset;
    /**
     * The ID of the connection configuration.
     * This is automatically generated based on the configuration name.
     * @var string $configId
     */
    protected(set) string $configId;
    /**
     * A description of the connection configuration.
     * @var string $description
     */
    protected(set) string $description;
    /**
     * The maximum number of connections allowed for this configuration when used in a pool.
     * @var int $maxConnectons
     */
    protected(set) int $maxConnections;
    protected(set) float $dealy;
    protected(set) string $lastConnectionError;

    /**
     * Checks if a connection to the specified host and port can be established.
     *
     * @return bool Returns true if the connection is successful and the driver is available, otherwise false.
     */
    public function canConnect(): bool
    {
        // Para sockets Unix
        if (isset($this->unixSocket)) {
            return file_exists($this->unixSocket) && is_readable($this->unixSocket);
        }

        // Para conexión TCP
        try {
            $context = stream_context_create([
                'socket' => ['tcp_nodelay' => true],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
            ]);
            $time = microtime(true);
            $socket = @stream_socket_client(
                "tcp://{$this->host}:{$this->port}",
                $errno,
                $errstr,
                1, // timeout
                STREAM_CLIENT_CONNECT,
                $context
            );

            if ($socket) {
                $this->dealy = microtime(true) - $time;
                fclose($socket);
                return true;
            }
            $this->lastConnectionError = 'No se pudo acceder al host: ' . $errstr . ' (' . $errno . ')';

            return false;
        } catch (\Throwable $e) {
            $this->lastConnectionError = $e->getMessage();
            return false;
        }
    }

    public function getId(): string
    {
        return $this->configId;
    }

}